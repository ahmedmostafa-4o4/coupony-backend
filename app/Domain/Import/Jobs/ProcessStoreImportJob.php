<?php

namespace App\Domain\Import\Jobs;

use App\Domain\Notification\Services\NotificationService;
use App\Domain\Store\Models\Store;
use App\Domain\Store\Models\StoreCategory;
use App\Domain\Store\Models\StoreEmployee;
use App\Domain\Store\Models\StoreHours;
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
use App\Domain\Import\Imports\StoreImport;
use ZipArchive;

class ProcessStoreImportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;
    public int $timeout = 300;

    public function __construct(
        protected string $zipPath,
        protected User $admin,
    ) {}

    public function handle(): void
    {
        $extractDir = storage_path('app/private/imports/stores/' . Str::uuid());
        $errors = [];
        $importedCount = 0;

        try {
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
            $sheetsData = (new StoreImport())->toArray($excelFile);
            
            $stores = $sheetsData['Stores'] ?? [];
            $branches = $sheetsData['Branches'] ?? [];
            $employees = $sheetsData['Employees'] ?? [];
            $hours = $sheetsData['Hours'] ?? [];

            if (empty($stores)) {
                $this->notifyFailure(['The Stores sheet is empty or has no data rows.']);
                $this->cleanup($extractDir);
                return;
            }

            // Step 4: Validate ALL sheets before inserting
            $errors = array_merge(
                $errors,
                $this->validateStores($stores, $extractDir),
                $this->validateBranches($branches, $stores),
                $this->validateEmployees($employees, $stores, $branches),
                $this->validateHours($hours, $stores)
            );

            if (!empty($errors)) {
                $this->notifyFailure($errors);
                $this->cleanup($extractDir);
                return;
            }

            // Step 5: Import all rows in a transaction
            DB::transaction(function () use ($stores, $branches, $employees, $hours, $extractDir, &$importedCount) {
                
                $storeMap = []; // reference_id => Store model
                $branchIdMap = []; // reference_id => Address ID

                // 1. Insert Stores & Categories
                foreach ($stores as $row) {
                    $store = $this->importStore($row, $extractDir);
                    $storeMap[$row['reference_id']] = $store;
                    $importedCount++;

                    if (!empty($row['categories'])) {
                        $slugs = array_filter(array_map('trim', explode(',', $row['categories'])));
                        if (!empty($slugs)) {
                            $categoryIds = StoreCategory::whereIn('slug', $slugs)->pluck('id')->toArray();
                            $store->categories()->sync($categoryIds);
                        }
                    }
                }

                // 2. Insert Branches
                foreach ($branches as $row) {
                    $store = $storeMap[$row['store_reference_id']];
                    
                    $address = $store->addBranchAddress([
                        'first_name' => $row['first_name'] ?? null,
                        'last_name' => $row['last_name'] ?? null,
                        'phone_number' => $row['phone_number'] ?? null,
                        'address_line1' => $row['address_line1'] ?? null,
                        'address_line2' => $row['address_line2'] ?? null,
                        'city' => $row['city'] ?? null,
                        'state_province' => $row['state_province'] ?? null,
                        'postal_code' => $row['postal_code'] ?? null,
                        'country_code' => $row['country_code'] ?? null,
                        'latitude' => $row['latitude'] ?: null,
                        'longitude' => $row['longitude'] ?: null,
                    ]);

                    if (!empty($row['reference_id'])) {
                        $branchIdMap[$row['reference_id']] = $address->id;
                    }
                }

                // 3. Insert Employees
                foreach ($employees as $row) {
                    $store = $storeMap[$row['store_reference_id']];
                    $user = User::where('email', $row['user_email'])->first();

                    if ($user) {
                        $addressId = null;
                        if (!empty($row['branch_reference_id']) && isset($branchIdMap[$row['branch_reference_id']])) {
                            $addressId = $branchIdMap[$row['branch_reference_id']];
                        }

                        $permissions = [];
                        if (!empty($row['permissions'])) {
                            $permissions = array_filter(array_map('trim', explode(',', $row['permissions'])));
                        }

                        StoreEmployee::create([
                            'store_id' => $store->id,
                            'user_id' => $user->id,
                            'address_id' => $addressId,
                            'role' => $row['role'] ?? 'store_employee',
                            'permissions' => !empty($permissions) ? $permissions : null,
                        ]);
                    }
                }

                // 4. Insert Hours
                foreach ($hours as $row) {
                    $store = $storeMap[$row['store_reference_id']];
                    
                    StoreHours::create([
                        'store_id' => $store->id,
                        'day_of_week' => (int)$row['day_of_week'],
                        'open_time' => !empty($row['open_time']) ? $row['open_time'] : '00:00:00',
                        'close_time' => !empty($row['close_time']) ? $row['close_time'] : '00:00:00',
                        'is_closed' => !empty($row['is_closed']),
                    ]);
                }
            });

            // Step 6: Notify success
            $this->notifySuccess($importedCount);

        } catch (\Throwable $e) {
            Log::error('Store Import Job Failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            $this->notifyFailure(['An unexpected error occurred: ' . $e->getMessage()]);
        } finally {
            $this->cleanup($extractDir);
            Storage::disk('local')->delete($this->zipPath);
        }
    }

    protected function validateStores(array $stores, string $extractDir): array
    {
        $errors = [];
        $references = [];

        foreach ($stores as $index => $row) {
            $rowNumber = $index + 2;

            if (empty($row['reference_id'])) {
                $errors[] = "Stores Row {$rowNumber}: 'reference_id' is required.";
            } elseif (in_array($row['reference_id'], $references)) {
                $errors[] = "Stores Row {$rowNumber}: 'reference_id' ({$row['reference_id']}) must be unique.";
            } else {
                $references[] = $row['reference_id'];
            }

            if (empty($row['name'] ?? null)) {
                $errors[] = "Stores Row {$rowNumber}: 'name' is required.";
            }

            if (!empty($row['email'] ?? null) && !filter_var($row['email'], FILTER_VALIDATE_EMAIL)) {
                $errors[] = "Stores Row {$rowNumber}: '{$row['email']}' is not a valid email address.";
            }

            if (!empty($row['owner_email'] ?? null)) {
                $owner = User::where('email', $row['owner_email'])->first();
                if (!$owner) {
                    $errors[] = "Stores Row {$rowNumber}: Owner with email '{$row['owner_email']}' does not exist.";
                }
            }

            if (!empty($row['categories'])) {
                $slugs = array_filter(array_map('trim', explode(',', $row['categories'])));
                foreach ($slugs as $slug) {
                    if (!StoreCategory::where('slug', $slug)->exists()) {
                        $errors[] = "Stores Row {$rowNumber}: Category slug '{$slug}' does not exist in the database.";
                    }
                }
            }

            $imagesDir = $this->findImagesDir($extractDir);
            foreach (['logo_image', 'banner_image'] as $imageField) {
                if (!empty($row[$imageField] ?? null) && $imagesDir) {
                    $imagePath = $imagesDir . DIRECTORY_SEPARATOR . $row[$imageField];
                    if (!file_exists($imagePath)) {
                        $errors[] = "Stores Row {$rowNumber}: Image file '{$row[$imageField]}' not found in the images folder.";
                    }
                }
            }
        }
        return $errors;
    }

    protected function validateBranches(array $branches, array $stores): array
    {
        $errors = [];
        $storeReferences = array_column($stores, 'reference_id');
        $branchReferences = [];

        foreach ($branches as $index => $row) {
            $rowNumber = $index + 2;

            if (empty($row['store_reference_id']) || !in_array($row['store_reference_id'], $storeReferences)) {
                $errors[] = "Branches Row {$rowNumber}: Invalid 'store_reference_id'. Must match a store reference_id.";
            }

            if (!empty($row['reference_id'])) {
                if (in_array($row['reference_id'], $branchReferences)) {
                    $errors[] = "Branches Row {$rowNumber}: 'reference_id' ({$row['reference_id']}) must be unique.";
                } else {
                    $branchReferences[] = $row['reference_id'];
                }
            }
        }
        return $errors;
    }

    protected function validateEmployees(array $employees, array $stores, array $branches): array
    {
        $errors = [];
        $storeReferences = array_column($stores, 'reference_id');
        $branchReferences = array_filter(array_column($branches, 'reference_id'));

        foreach ($employees as $index => $row) {
            $rowNumber = $index + 2;

            if (empty($row['store_reference_id']) || !in_array($row['store_reference_id'], $storeReferences)) {
                $errors[] = "Employees Row {$rowNumber}: Invalid 'store_reference_id'. Must match a store reference_id.";
            }

            if (!empty($row['branch_reference_id']) && !in_array($row['branch_reference_id'], $branchReferences)) {
                $errors[] = "Employees Row {$rowNumber}: Invalid 'branch_reference_id' ({$row['branch_reference_id']}). Must match a branch reference_id.";
            }

            if (empty($row['user_email'])) {
                $errors[] = "Employees Row {$rowNumber}: 'user_email' is required.";
            } elseif (!filter_var($row['user_email'], FILTER_VALIDATE_EMAIL)) {
                $errors[] = "Employees Row {$rowNumber}: '{$row['user_email']}' is not a valid email address.";
            } else {
                if (!User::where('email', $row['user_email'])->exists()) {
                    $errors[] = "Employees Row {$rowNumber}: User with email '{$row['user_email']}' does not exist in the system.";
                }
            }
        }
        return $errors;
    }

    protected function validateHours(array $hours, array $stores): array
    {
        $errors = [];
        $storeReferences = array_column($stores, 'reference_id');

        foreach ($hours as $index => $row) {
            $rowNumber = $index + 2;

            if (empty($row['store_reference_id']) || !in_array($row['store_reference_id'], $storeReferences)) {
                $errors[] = "Hours Row {$rowNumber}: Invalid 'store_reference_id'. Must match a store reference_id.";
            }

            if (!isset($row['day_of_week']) || $row['day_of_week'] === '' || $row['day_of_week'] < 0 || $row['day_of_week'] > 6) {
                $errors[] = "Hours Row {$rowNumber}: 'day_of_week' must be between 0 (Sunday) and 6 (Saturday).";
            }
        }
        return $errors;
    }

    protected function importStore(array $row, string $extractDir): Store
    {
        $ownerUserId = null;
        if (!empty($row['owner_email'] ?? null)) {
            $owner = User::where('email', $row['owner_email'])->first();
            $ownerUserId = $owner?->id;
        }

        $logoUrl = null;
        $bannerUrl = null;
        $imagesDir = $this->findImagesDir($extractDir);

        if ($imagesDir) {
            if (!empty($row['logo_image'] ?? null)) {
                $logoUrl = $this->storeImage($imagesDir, $row['logo_image'], 'stores/logos');
            }
            if (!empty($row['banner_image'] ?? null)) {
                $bannerUrl = $this->storeImage($imagesDir, $row['banner_image'], 'stores/banners');
            }
        }

        return Store::create([
            'owner_user_id' => $ownerUserId,
            'name' => $row['name'],
            'description' => $row['description'] ?? null,
            'logo_url' => $logoUrl,
            'banner_url' => $bannerUrl,
            'email' => $row['email'] ?? null,
            'phone' => $row['phone'] ?? null,
            'tax_id' => $row['tax_id'] ?? null,
            'commission_rate' => isset($row['commission_rate']) && $row['commission_rate'] !== '' ? $row['commission_rate'] : 0.1500,
            'status' => $row['status'] ?? 'pending',
        ]);
    }

    protected function findExcelFile(string $extractDir): ?string
    {
        $possibleNames = ['data.xlsx', 'Data.xlsx', 'stores.xlsx', 'Stores.xlsx'];

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

    protected function notifySuccess(int $count): void
    {
        app(NotificationService::class)->send(
            $this->admin,
            'import_completed',
            'Store Import Completed',
            "Successfully imported {$count} store(s) from the uploaded Excel file.",
            'in_app',
            ['type' => 'stores', 'imported_count' => $count]
        );
    }

    protected function notifyFailure(array $errors): void
    {
        $errorSummary = implode("\n", array_slice($errors, 0, 10));
        $remaining = count($errors) - 10;

        $message = "Store import failed with the following errors:\n{$errorSummary}";
        if ($remaining > 0) {
            $message .= "\n...and {$remaining} more error(s).";
        }

        app(NotificationService::class)->send(
            $this->admin,
            'import_failed',
            'Store Import Failed',
            $message,
            'in_app',
            ['type' => 'stores', 'errors' => $errors]
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
