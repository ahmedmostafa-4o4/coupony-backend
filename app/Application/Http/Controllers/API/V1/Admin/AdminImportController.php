<?php

namespace App\Application\Http\Controllers\API\V1\Admin;

use App\Application\Http\Controllers\Controller;
use App\Application\Http\Requests\Admin\ImportProductsRequest;
use App\Application\Http\Requests\Admin\ImportStoresRequest;
use App\Domain\Import\Exports\ProductTemplateExport;
use App\Domain\Import\Exports\StoreTemplateExport;
use App\Domain\Import\Jobs\ProcessProductImportJob;
use App\Domain\Import\Jobs\ProcessStoreImportJob;
use Illuminate\Support\Str;

class AdminImportController extends Controller
{
    /**
     * POST /api/v1/admin/imports/stores
     *
     * Upload a ZIP file containing a data.xlsx and an images/ folder
     * to bulk-import stores.
     */
    public function importStores(ImportStoresRequest $request)
    {
        $file = $request->file('file');
        $storedPath = $file->storeAs(
            'imports/uploads',
            'stores_' . Str::uuid() . '.zip',
            'local'
        );

        ProcessStoreImportJob::dispatch($storedPath, $request->user());

        return response()->json([
            'message' => 'Store import has been queued for processing. You will receive a notification when the import is complete.',
        ], 202);
    }

    /**
     * POST /api/v1/admin/imports/products
     *
     * Upload a ZIP file containing a data.xlsx and an images/ folder
     * to bulk-import products linked to a specific store.
     */
    public function importProducts(ImportProductsRequest $request)
    {
        $file = $request->file('file');
        $storedPath = $file->storeAs(
            'imports/uploads',
            'products_' . Str::uuid() . '.zip',
            'local'
        );

        ProcessProductImportJob::dispatch(
            $storedPath,
            $request->input('store_id'),
            $request->user()
        );

        return response()->json([
            'message' => 'Product import has been queued for processing. You will receive a notification when the import is complete.',
        ], 202);
    }

    /**
     * GET /api/v1/admin/imports/stores/template
     *
     * Download an empty Excel template for store imports.
     */
    public function storeTemplate()
    {
        $export = new StoreTemplateExport();
        $tempPath = $export->generate();

        return response()->download($tempPath, 'store_import_template.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    /**
     * GET /api/v1/admin/imports/products/template
     *
     * Download an empty Excel template for product imports.
     */
    public function productTemplate()
    {
        $export = new ProductTemplateExport();
        $tempPath = $export->generate();

        return response()->download($tempPath, 'product_import_template.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }
}
