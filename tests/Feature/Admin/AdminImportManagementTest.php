<?php

namespace Tests\Feature\Admin;

use App\Domain\Store\Models\Store;
use App\Domain\User\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use App\Domain\Import\Jobs\ProcessStoreImportJob;
use App\Domain\Import\Jobs\ProcessProductImportJob;
use Tests\TestCase;
use ZipArchive;

class AdminImportManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $customer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->admin = User::factory()->create()->assignRole('admin');
        $this->customer = User::factory()->create()->assignRole('customer');
    }

    // ===========================
    // Template Download Tests
    // ===========================

    public function test_admin_can_download_store_template()
    {
        $response = $this->actingAs($this->admin)->getJson('/api/v1/admin/imports/stores/template');

        $response->assertStatus(200)
            ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

    public function test_admin_can_download_product_template()
    {
        $response = $this->actingAs($this->admin)->getJson('/api/v1/admin/imports/products/template');

        $response->assertStatus(200)
            ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

    public function test_customer_cannot_download_store_template()
    {
        $response = $this->actingAs($this->customer)->getJson('/api/v1/admin/imports/stores/template');

        $response->assertStatus(403);
    }

    // ===========================
    // Store Import Tests
    // ===========================

    public function test_admin_can_queue_store_import()
    {
        Queue::fake();
        Storage::fake('local');

        $zipPath = $this->createTestZip('stores');

        $response = $this->actingAs($this->admin)->postJson('/api/v1/admin/imports/stores', [
            'file' => new UploadedFile($zipPath, 'stores.zip', 'application/zip', null, true),
        ]);

        $response->assertStatus(202)
            ->assertJsonPath('message', 'Store import has been queued for processing. You will receive a notification when the import is complete.');

        Queue::assertPushed(ProcessStoreImportJob::class);
    }

    public function test_store_import_rejects_non_zip_file()
    {
        $response = $this->actingAs($this->admin)->postJson('/api/v1/admin/imports/stores', [
            'file' => UploadedFile::fake()->create('stores.txt', 100, 'text/plain'),
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['file']);
    }

    public function test_customer_cannot_import_stores()
    {
        $zipPath = $this->createTestZip('stores');

        $response = $this->actingAs($this->customer)->postJson('/api/v1/admin/imports/stores', [
            'file' => new UploadedFile($zipPath, 'stores.zip', 'application/zip', null, true),
        ]);

        $response->assertStatus(403);
    }

    // ===========================
    // Product Import Tests
    // ===========================

    public function test_admin_can_queue_product_import()
    {
        Queue::fake();
        Storage::fake('local');

        $store = Store::factory()->create();
        $zipPath = $this->createTestZip('products');

        $response = $this->actingAs($this->admin)->postJson('/api/v1/admin/imports/products', [
            'file' => new UploadedFile($zipPath, 'products.zip', 'application/zip', null, true),
            'store_id' => $store->id,
        ]);

        $response->assertStatus(202)
            ->assertJsonPath('message', 'Product import has been queued for processing. You will receive a notification when the import is complete.');

        Queue::assertPushed(ProcessProductImportJob::class);
    }

    public function test_product_import_requires_store_id()
    {
        $zipPath = $this->createTestZip('products');

        $response = $this->actingAs($this->admin)->postJson('/api/v1/admin/imports/products', [
            'file' => new UploadedFile($zipPath, 'products.zip', 'application/zip', null, true),
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['store_id']);
    }

    public function test_product_import_rejects_invalid_store_id()
    {
        $zipPath = $this->createTestZip('products');

        $response = $this->actingAs($this->admin)->postJson('/api/v1/admin/imports/products', [
            'file' => new UploadedFile($zipPath, 'products.zip', 'application/zip', null, true),
            'store_id' => 'non-existent-uuid',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['store_id']);
    }

    public function test_customer_cannot_import_products()
    {
        $store = Store::factory()->create();
        $zipPath = $this->createTestZip('products');

        $response = $this->actingAs($this->customer)->postJson('/api/v1/admin/imports/products', [
            'file' => new UploadedFile($zipPath, 'products.zip', 'application/zip', null, true),
            'store_id' => $store->id,
        ]);

        $response->assertStatus(403);
    }

    // ===========================
    // Helpers
    // ===========================

    /**
     * Create a minimal test ZIP file with a data.xlsx.
     */
    private function createTestZip(string $type): string
    {
        $tempDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'test_import_' . uniqid();
        mkdir($tempDir, 0755, true);

        // Create a minimal xlsx using PhpSpreadsheet
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        if ($type === 'stores') {
            // Stores Sheet
            $sheet->setTitle('Stores');
            $sheet->setCellValue('A1', 'reference_id');
            $sheet->setCellValue('B1', 'name');
            $sheet->setCellValue('C1', 'description');
            $sheet->setCellValue('D1', 'email');
            
            $sheet->setCellValue('A2', 'store_1');
            $sheet->setCellValue('B2', 'Test Store');
            $sheet->setCellValue('C2', 'A test store');
            $sheet->setCellValue('D2', 'test@example.com');

            // Branches Sheet
            $branchesSheet = $spreadsheet->createSheet();
            $branchesSheet->setTitle('Branches');
            $branchesSheet->setCellValue('A1', 'reference_id');
            $branchesSheet->setCellValue('B1', 'store_reference_id');
            $branchesSheet->setCellValue('C1', 'city');
            
            $branchesSheet->setCellValue('A2', 'branch_1');
            $branchesSheet->setCellValue('B2', 'store_1');
            $branchesSheet->setCellValue('C2', 'Cairo');

            // Employees Sheet
            $employeesSheet = $spreadsheet->createSheet();
            $employeesSheet->setTitle('Employees');
            $employeesSheet->setCellValue('A1', 'store_reference_id');
            $employeesSheet->setCellValue('B1', 'user_email');
            $employeesSheet->setCellValue('C1', 'role');
            
            $employeesSheet->setCellValue('A2', 'store_1');
            $employeesSheet->setCellValue('B2', 'employee@example.com');
            $employeesSheet->setCellValue('C2', 'branch_manager');

            // Hours Sheet
            $hoursSheet = $spreadsheet->createSheet();
            $hoursSheet->setTitle('Hours');
            $hoursSheet->setCellValue('A1', 'store_reference_id');
            $hoursSheet->setCellValue('B1', 'day_of_week');
            $hoursSheet->setCellValue('C1', 'is_closed');
            
            $hoursSheet->setCellValue('A2', 'store_1');
            $hoursSheet->setCellValue('B2', '0');
            $hoursSheet->setCellValue('C2', '1');
            
            $spreadsheet->setActiveSheetIndex(0);
        } else {
            // Products Sheet
            $sheet->setTitle('Products');
            $sheet->setCellValue('A1', 'reference_id');
            $sheet->setCellValue('B1', 'title');
            $sheet->setCellValue('C1', 'base_price');
            $sheet->setCellValue('D1', 'categories');
            
            $sheet->setCellValue('A2', 'prod_1');
            $sheet->setCellValue('B2', 'Test Product');
            $sheet->setCellValue('C2', '99.99');
            $sheet->setCellValue('D2', 'electronics');

            // Variants Sheet
            $variantsSheet = $spreadsheet->createSheet();
            $variantsSheet->setTitle('Variants');
            $variantsSheet->setCellValue('A1', 'reference_id');
            $variantsSheet->setCellValue('B1', 'product_reference_id');
            $variantsSheet->setCellValue('C1', 'title');
            $variantsSheet->setCellValue('D1', 'price');
            
            $variantsSheet->setCellValue('A2', 'var_1');
            $variantsSheet->setCellValue('B2', 'prod_1');
            $variantsSheet->setCellValue('C2', 'Red Variant');
            $variantsSheet->setCellValue('D2', '109.99');

            // Attributes Sheet
            $attributesSheet = $spreadsheet->createSheet();
            $attributesSheet->setTitle('Attributes');
            $attributesSheet->setCellValue('A1', 'variant_reference_id');
            $attributesSheet->setCellValue('B1', 'attribute_name');
            $attributesSheet->setCellValue('C1', 'attribute_value');
            
            $attributesSheet->setCellValue('A2', 'var_1');
            $attributesSheet->setCellValue('B2', 'Color');
            $attributesSheet->setCellValue('C2', 'Red');

            // Offers Sheet
            $offersSheet = $spreadsheet->createSheet();
            $offersSheet->setTitle('Offers');
            $offersSheet->setCellValue('A1', 'product_reference_id');
            $offersSheet->setCellValue('B1', 'type');
            $offersSheet->setCellValue('C1', 'percentage_value');
            $offersSheet->setCellValue('D1', 'target_buy_variants');
            
            $offersSheet->setCellValue('A2', 'prod_1');
            $offersSheet->setCellValue('B2', 'percentage');
            $offersSheet->setCellValue('C2', '20');
            $offersSheet->setCellValue('D2', 'var_1');
            
            $spreadsheet->setActiveSheetIndex(0);
        }

        $xlsxPath = $tempDir . DIRECTORY_SEPARATOR . 'data.xlsx';
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save($xlsxPath);

        // Create ZIP
        $zipPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $type . '_test_' . uniqid() . '.zip';
        $zip = new ZipArchive();
        $zip->open($zipPath, ZipArchive::CREATE);
        $zip->addFile($xlsxPath, 'data.xlsx');
        $zip->close();

        // Cleanup temp dir
        unlink($xlsxPath);
        rmdir($tempDir);

        return $zipPath;
    }
}
