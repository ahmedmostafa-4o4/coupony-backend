<?php

namespace App\Domain\Import\Jobs;

use App\Domain\Notification\Services\NotificationService;
use App\Domain\Product\Models\Product;
use App\Domain\Product\Models\ProductOffer;
use App\Domain\Product\Models\ProductVariant;
use App\Domain\Store\Models\Store;
use App\Domain\User\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Domain\Import\Imports\ProductImport;
use ZipArchive;
use App\Domain\Product\Enums\ProductOfferTargetRole;

class ProcessProductImportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;
    public int $timeout = 300;

    public function __construct(
        protected string $zipPath,
        protected string $storeId,
        protected User $admin,
    ) {}

    public function handle(): void
    {
        $extractDir = storage_path('app/private/imports/products/' . Str::uuid());
        $errors = [];
        $importedCount = 0;

        try {
            // Verify store exists
            $store = Store::find($this->storeId);
            if (!$store) {
                $this->notifyFailure(["Store with ID '{$this->storeId}' not found."]);
                return;
            }

            // Step 1: Extract ZIP
            $zip = new ZipArchive();
            $zipFullPath = Storage::disk('local')->path($this->zipPath);

            if ($zip->open($zipFullPath) !== true) {
                $this->notifyFailure(['The ZIP file could not be opened.']);
                return;
            }

            $zip->extractTo($extractDir);
            $zip->close();

            // Step 2: Find the Excel file
            $excelFile = $this->findExcelFile($extractDir);
            if (!$excelFile) {
                $this->notifyFailure(['No data.xlsx file found inside the ZIP archive.']);
                $this->cleanup($extractDir);
                return;
            }

            // Step 3: Parse the Excel file (all sheets)
            $sheetsData = (new ProductImport())->toArray($excelFile);
            
            $products = $sheetsData['Products'] ?? [];
            $variants = $sheetsData['Variants'] ?? [];
            $attributes = $sheetsData['Attributes'] ?? [];
            $offers = $sheetsData['Offers'] ?? [];

            if (empty($products)) {
                $this->notifyFailure(['The Products sheet is empty or has no data rows.']);
                $this->cleanup($extractDir);
                return;
            }

            // Step 4: Validate ALL sheets before inserting
            $errors = array_merge(
                $errors,
                $this->validateProducts($products, $extractDir, $store),
                $this->validateVariants($variants, $products),
                $this->validateAttributes($attributes, $variants),
                $this->validateOffers($offers, $products, $variants)
            );

            if (!empty($errors)) {
                $this->notifyFailure($errors);
                $this->cleanup($extractDir);
                return;
            }

            // Step 5: Import all rows in a transaction
            DB::transaction(function () use ($products, $variants, $attributes, $offers, $extractDir, $store, &$importedCount) {
                
                $productIdMap = []; // reference_id => actual UUID
                $variantIdMap = []; // reference_id => actual UUID

                // 1. Insert Products
                foreach ($products as $row) {
                    $product = $this->importProduct($row, $extractDir, $store);
                    
                    if (!empty($row['categories'])) {
                        $categorySlugs = array_filter(array_map('trim', explode(',', $row['categories'])));
                        $categoryIds = \App\Domain\Product\Models\Category::whereIn('slug', $categorySlugs)->pluck('id');
                        $product->categories()->sync($categoryIds);
                    }

                    $productIdMap[$row['reference_id']] = $product->id;
                    $importedCount++;

                    // Find variants for this product
                    $productVariants = array_filter($variants, fn($v) => ($v['product_reference_id'] ?? null) === $row['reference_id']);
                    
                    if (empty($productVariants)) {
                        // Create default variant if none provided
                        $product->variants()->create([
                            'title' => 'Default',
                            'price' => $product->base_price,
                            'original_price' => $product->base_price,
                            'compare_at_price' => $product->compare_at_price,
                            'sku' => $product->sku,
                            'is_default' => true,
                            'is_active' => true,
                            'stock_qty' => null, // Unlimited
                            'inventory_mode' => \App\Domain\Product\Enums\InventoryMode::UNLIMITED,
                        ]);
                    }
                }

                // 2. Insert Variants
                foreach ($variants as $row) {
                    $productId = $productIdMap[$row['product_reference_id']];
                    $variant = ProductVariant::create([
                        'product_id' => $productId,
                        'title' => $row['title'],
                        'option_summary' => $row['option_summary'] ?? null,
                        'sku' => $row['sku'] ?? null,
                        'barcode' => $row['barcode'] ?? null,
                        'price' => isset($row['price']) && $row['price'] !== '' ? $row['price'] : 0,
                        'original_price' => isset($row['price']) && $row['price'] !== '' ? $row['price'] : 0,
                        'compare_at_price' => isset($row['compare_at_price']) && $row['compare_at_price'] !== '' ? $row['compare_at_price'] : null,
                        'stock_qty' => ($row['stock_qty'] ?? null) !== null && $row['stock_qty'] !== '' ? (int)$row['stock_qty'] : null,
                        'inventory_mode' => (($row['stock_qty'] ?? null) !== null && $row['stock_qty'] !== '') 
                                            ? \App\Domain\Product\Enums\InventoryMode::TRACKED 
                                            : \App\Domain\Product\Enums\InventoryMode::UNLIMITED,
                        'is_default' => !empty($row['is_default']),
                        'is_active' => true,
                    ]);
                    $variantIdMap[$row['reference_id']] = $variant->id;
                }

                // 3. Insert Attributes
                foreach ($attributes as $row) {
                    $variantId = $variantIdMap[$row['variant_reference_id']];
                    \App\Domain\Product\Models\ProductVariantAttribute::create([
                        'variant_id' => $variantId,
                        'attribute_name' => $row['attribute_name'],
                        'attribute_value' => $row['attribute_value'],
                    ]);
                }

                // 4. Insert Offers
                foreach ($offers as $row) {
                    $productId = $productIdMap[$row['product_reference_id']];
                    
                    $offer = ProductOffer::create([
                        'product_id' => $productId,
                        'type' => $row['type'],
                        'label' => $row['label'] ?? null,
                        'percentage_value' => $row['percentage_value'] ?: null,
                        'fixed_amount' => $row['fixed_amount'] ?: null,
                        'buy_qty' => $row['buy_qty'] ?: null,
                        'get_qty' => $row['get_qty'] ?: null,
                        'starts_at' => $row['starts_at'] ?: null,
                        'ends_at' => $row['ends_at'] ?: null,
                    ]);

                    // Handle Variant Targets
                    $buyVariants = array_filter(array_map('trim', explode(',', $row['target_buy_variants'] ?? '')));
                    $rewardVariants = array_filter(array_map('trim', explode(',', $row['target_reward_variants'] ?? '')));

                    foreach ($buyVariants as $variantRef) {
                        if (isset($variantIdMap[$variantRef])) {
                            $offer->targets()->create([
                                'variant_id' => $variantIdMap[$variantRef],
                                'role' => ProductOfferTargetRole::BUY,
                            ]);
                        }
                    }

                    foreach ($rewardVariants as $variantRef) {
                        if (isset($variantIdMap[$variantRef])) {
                            $offer->targets()->create([
                                'variant_id' => $variantIdMap[$variantRef],
                                'role' => ProductOfferTargetRole::REWARD,
                            ]);
                        }
                    }
                }
            });

            // Step 6: Notify success
            $this->notifySuccess($importedCount, $store);

        } catch (\Throwable $e) {
            Log::error('Product Import Job Failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            $this->notifyFailure(['An unexpected error occurred: ' . $e->getMessage()]);
        } finally {
            $this->cleanup($extractDir);
            Storage::disk('local')->delete($this->zipPath);
        }
    }

    protected function validateProducts(array $products, string $extractDir, Store $store): array
    {
        $errors = [];
        $references = [];

        foreach ($products as $index => $row) {
            $rowNumber = $index + 2;

            if (empty($row['reference_id'])) {
                $errors[] = "Products Row {$rowNumber}: 'reference_id' is required.";
            } elseif (in_array($row['reference_id'], $references)) {
                $errors[] = "Products Row {$rowNumber}: 'reference_id' ({$row['reference_id']}) must be unique.";
            } else {
                $references[] = $row['reference_id'];
            }

            if (empty($row['title'])) {
                $errors[] = "Products Row {$rowNumber}: 'title' is required.";
            }

            if (empty($row['base_price'])) {
                $errors[] = "Products Row {$rowNumber}: 'base_price' is required.";
            } elseif (!is_numeric($row['base_price']) || $row['base_price'] < 0) {
                $errors[] = "Products Row {$rowNumber}: 'base_price' must be a positive number.";
            }

            if (!empty($row['sku'])) {
                $exists = Product::where('store_id', $store->id)->where('sku', $row['sku'])->exists();
                if ($exists) {
                    $errors[] = "Products Row {$rowNumber}: SKU '{$row['sku']}' already exists for this store.";
                }
            }

            $imagesDir = $this->findImagesDir($extractDir);
            if (!empty($row['image']) && $imagesDir) {
                $imagePath = $imagesDir . DIRECTORY_SEPARATOR . $row['image'];
                if (!file_exists($imagePath)) {
                    $errors[] = "Products Row {$rowNumber}: Image file '{$row['image']}' not found in the images folder.";
                }
            }

            if (!empty($row['categories'])) {
                $categorySlugs = array_filter(array_map('trim', explode(',', $row['categories'])));
                foreach ($categorySlugs as $slug) {
                    if (!\App\Domain\Product\Models\Category::where('slug', $slug)->exists()) {
                        $errors[] = "Products Row {$rowNumber}: Category slug '{$slug}' does not exist in the database.";
                    }
                }
            }
        }
        return $errors;
    }

    protected function validateVariants(array $variants, array $products): array
    {
        $errors = [];
        $productReferences = array_column($products, 'reference_id');
        $variantReferences = [];

        foreach ($variants as $index => $row) {
            $rowNumber = $index + 2;

            if (empty($row['reference_id'])) {
                $errors[] = "Variants Row {$rowNumber}: 'reference_id' is required.";
            } elseif (in_array($row['reference_id'], $variantReferences)) {
                $errors[] = "Variants Row {$rowNumber}: 'reference_id' ({$row['reference_id']}) must be unique.";
            } else {
                $variantReferences[] = $row['reference_id'];
            }

            if (empty($row['product_reference_id']) || !in_array($row['product_reference_id'], $productReferences)) {
                $errors[] = "Variants Row {$rowNumber}: Invalid 'product_reference_id'. Must match a product reference_id.";
            }

            if (empty($row['title'])) {
                $errors[] = "Variants Row {$rowNumber}: 'title' is required.";
            }

            if ((isset($row['price']) && $row['price'] !== '') && (!is_numeric($row['price']) || $row['price'] < 0)) {
                $errors[] = "Variants Row {$rowNumber}: 'price' must be a positive number.";
            }
        }
        return $errors;
    }

    protected function validateAttributes(array $attributes, array $variants): array
    {
        $errors = [];
        $variantReferences = array_column($variants, 'reference_id');

        foreach ($attributes as $index => $row) {
            $rowNumber = $index + 2;

            if (empty($row['variant_reference_id']) || !in_array($row['variant_reference_id'], $variantReferences)) {
                $errors[] = "Attributes Row {$rowNumber}: Invalid 'variant_reference_id'. Must match a variant reference_id.";
            }

            if (empty($row['attribute_name'])) {
                $errors[] = "Attributes Row {$rowNumber}: 'attribute_name' is required.";
            }

            if (empty($row['attribute_value'])) {
                $errors[] = "Attributes Row {$rowNumber}: 'attribute_value' is required.";
            }
        }
        return $errors;
    }

    protected function validateOffers(array $offers, array $products, array $variants): array
    {
        $errors = [];
        $productReferences = array_column($products, 'reference_id');
        $variantReferences = array_column($variants, 'reference_id');

        foreach ($offers as $index => $row) {
            $rowNumber = $index + 2;

            if (empty($row['product_reference_id']) || !in_array($row['product_reference_id'], $productReferences)) {
                $errors[] = "Offers Row {$rowNumber}: Invalid 'product_reference_id'. Must match a product reference_id.";
            }

            if (empty($row['type'])) {
                $errors[] = "Offers Row {$rowNumber}: 'type' is required.";
            }

            $buyVariants = array_filter(array_map('trim', explode(',', $row['target_buy_variants'] ?? '')));
            foreach ($buyVariants as $ref) {
                if (!in_array($ref, $variantReferences)) {
                    $errors[] = "Offers Row {$rowNumber}: Invalid 'target_buy_variants' reference: {$ref}.";
                }
            }

            $rewardVariants = array_filter(array_map('trim', explode(',', $row['target_reward_variants'] ?? '')));
            foreach ($rewardVariants as $ref) {
                if (!in_array($ref, $variantReferences)) {
                    $errors[] = "Offers Row {$rowNumber}: Invalid 'target_reward_variants' reference: {$ref}.";
                }
            }
        }
        return $errors;
    }

    protected function importProduct(array $row, string $extractDir, Store $store): Product
    {
        $slug = Str::slug($row['title']);
        $originalSlug = $slug;
        $counter = 1;
        while (Product::where('store_id', $store->id)->where('slug', $slug)->exists()) {
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }

        $product = Product::create([
            'store_id' => $store->id,
            'title' => $row['title'],
            'slug' => $slug,
            'short_description' => $row['short_description'] ?? null,
            'description' => $row['description'] ?? null,
            'base_price' => $row['base_price'],
            'compare_at_price' => isset($row['compare_at_price']) && $row['compare_at_price'] !== '' ? $row['compare_at_price'] : null,
            'currency' => $row['currency'] ?? 'EGP',
            'sku' => $row['sku'] ?? null,
            'status' => \App\Domain\Product\Enums\ProductStatus::ACTIVE,
            'approval_status' => \App\Domain\Product\Enums\ProductApprovalStatus::APPROVED,
            'approved_at' => now(),
            'approved_by' => $this->admin->id,
        ]);

        $imagesDir = $this->findImagesDir($extractDir);
        if ($imagesDir && !empty($row['image'])) {
            $imageUrl = $this->storeImage($imagesDir, $row['image'], 'products/' . $product->id);
            if ($imageUrl) {
                $product->images()->create([
                    'image_url' => $imageUrl,
                    'sort_order' => 0,
                ]);
            }
        }

        return $product;
    }

    protected function findExcelFile(string $extractDir): ?string
    {
        $possibleNames = ['data.xlsx', 'Data.xlsx', 'products.xlsx', 'Products.xlsx'];

        foreach ($possibleNames as $name) {
            $path = $extractDir . DIRECTORY_SEPARATOR . $name;
            if (file_exists($path)) {
                return $path;
            }
        }

        $dirs = glob($extractDir . DIRECTORY_SEPARATOR . '*', GLOB_ONLYDIR);
        foreach ($dirs as $dir) {
            foreach ($possibleNames as $name) {
                $path = $dir . DIRECTORY_SEPARATOR . $name;
                if (file_exists($path)) {
                    return $path;
                }
            }
        }

        return null;
    }

    protected function findImagesDir(string $extractDir): ?string
    {
        $possibleNames = ['images', 'Images', 'media', 'Media'];

        foreach ($possibleNames as $name) {
            $path = $extractDir . DIRECTORY_SEPARATOR . $name;
            if (is_dir($path)) {
                return $path;
            }
        }

        $dirs = glob($extractDir . DIRECTORY_SEPARATOR . '*', GLOB_ONLYDIR);
        foreach ($dirs as $dir) {
            foreach ($possibleNames as $name) {
                $path = $dir . DIRECTORY_SEPARATOR . $name;
                if (is_dir($path)) {
                    return $path;
                }
            }
        }

        return null;
    }

    protected function storeImage(string $imagesDir, string $filename, string $storagePath): ?string
    {
        $sourcePath = $imagesDir . DIRECTORY_SEPARATOR . $filename;

        if (!file_exists($sourcePath)) {
            return null;
        }

        $extension = pathinfo($filename, PATHINFO_EXTENSION);
        $storedName = Str::uuid() . '.' . $extension;

        Storage::disk('public')->putFileAs($storagePath, new \Illuminate\Http\File($sourcePath), $storedName);

        return Storage::disk('public')->url($storagePath . '/' . $storedName);
    }

    protected function notifySuccess(int $count, Store $store): void
    {
        app(NotificationService::class)->send(
            $this->admin,
            'import_completed',
            'Product Import Completed',
            "Successfully imported {$count} product(s) for store '{$store->name}'.",
            'in_app',
            ['type' => 'products', 'imported_count' => $count, 'store_id' => $store->id]
        );
    }

    protected function notifyFailure(array $errors): void
    {
        $errorSummary = implode("\n", array_slice($errors, 0, 10));
        $remaining = count($errors) - 10;

        $message = "Product import failed with the following errors:\n{$errorSummary}";
        if ($remaining > 0) {
            $message .= "\n...and {$remaining} more error(s).";
        }

        app(NotificationService::class)->send(
            $this->admin,
            'import_failed',
            'Product Import Failed',
            $message,
            'in_app',
            ['type' => 'products', 'errors' => $errors]
        );
    }

    protected function cleanup(string $extractDir): void
    {
        if (is_dir($extractDir)) {
            $this->deleteDirectory($extractDir);
        }
    }

    protected function deleteDirectory(string $dir): void
    {
        $items = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($items as $item) {
            $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
        }

        rmdir($dir);
    }
}
