<?php

namespace App\Domain\Import\Exports;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ProductTemplateExport
{
    /**
     * Generate the product import template and return the absolute path to the temp file.
     */
    public function generate(): string
    {
        $spreadsheet = new Spreadsheet();

        // 1. Products Sheet
        $productsSheet = $spreadsheet->getActiveSheet();
        $productsSheet->setTitle('Products');
        $this->setupProductsSheet($productsSheet);

        // 2. Variants Sheet
        $variantsSheet = $spreadsheet->createSheet();
        $variantsSheet->setTitle('Variants');
        $this->setupVariantsSheet($variantsSheet);

        // 3. Attributes Sheet
        $attributesSheet = $spreadsheet->createSheet();
        $attributesSheet->setTitle('Attributes');
        $this->setupAttributesSheet($attributesSheet);

        // 4. Offers Sheet
        $offersSheet = $spreadsheet->createSheet();
        $offersSheet->setTitle('Offers');
        $this->setupOffersSheet($offersSheet);

        // Set first sheet as active
        $spreadsheet->setActiveSheetIndex(0);

        // Write to temp file
        $tempPath = tempnam(sys_get_temp_dir(), 'product_template_') . '.xlsx';
        $writer = new Xlsx($spreadsheet);
        $writer->save($tempPath);

        return $tempPath;
    }

    private function setupProductsSheet($sheet)
    {
        $headers = [
            'A' => 'reference_id',
            'B' => 'title',
            'C' => 'short_description',
            'D' => 'description',
            'E' => 'base_price',
            'F' => 'compare_at_price',
            'G' => 'currency',
            'H' => 'sku',
            'I' => 'categories',
            'J' => 'image',
        ];

        foreach ($headers as $col => $header) {
            $sheet->getCell("{$col}1")->setValue($header);
        }

        $this->applyHeaderStyle($sheet, 'J1');

        $exampleData = [
            'A' => 'prod_1',
            'B' => 'Wireless Headphones',
            'C' => 'High-quality wireless headphones.',
            'D' => 'Premium noise-cancelling wireless headphones with 30-hour battery life.',
            'E' => '99.99',
            'F' => '129.99',
            'G' => 'EGP',
            'H' => 'SKU-12345',
            'I' => 'electronics,accessories',
            'J' => 'product_image.jpg',
        ];

        foreach ($exampleData as $col => $value) {
            $sheet->getCell("{$col}2")->setValue($value);
        }

        foreach (array_keys($headers) as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
    }

    private function setupVariantsSheet($sheet)
    {
        $headers = [
            'A' => 'reference_id',
            'B' => 'product_reference_id',
            'C' => 'title',
            'D' => 'option_summary',
            'E' => 'sku',
            'F' => 'barcode',
            'G' => 'price',
            'H' => 'compare_at_price',
            'I' => 'stock_qty',
            'J' => 'is_default',
        ];

        foreach ($headers as $col => $header) {
            $sheet->getCell("{$col}1")->setValue($header);
        }

        $this->applyHeaderStyle($sheet, 'J1');

        // Variant 1
        $exampleData1 = [
            'A' => 'var_1',
            'B' => 'prod_1',
            'C' => 'Black',
            'D' => 'Color: Black',
            'E' => 'SKU-WH-BLK',
            'F' => '1234567890123',
            'G' => '199.99',
            'H' => '299.99',
            'I' => '50',
            'J' => '1',
        ];

        foreach ($exampleData1 as $col => $value) {
            $sheet->getCell("{$col}2")->setValue($value);
        }

        // Variant 2
        $exampleData2 = [
            'A' => 'var_2',
            'B' => 'prod_1',
            'C' => 'White',
            'D' => 'Color: White',
            'E' => 'SKU-WH-WHT',
            'F' => '1234567890124',
            'G' => '209.99',
            'H' => '309.99',
            'I' => '30',
            'J' => '0',
        ];

        foreach ($exampleData2 as $col => $value) {
            $sheet->getCell("{$col}3")->setValue($value);
        }

        foreach (array_keys($headers) as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
    }

    private function setupAttributesSheet($sheet)
    {
        $headers = [
            'A' => 'variant_reference_id',
            'B' => 'attribute_name',
            'C' => 'attribute_value',
        ];

        foreach ($headers as $col => $header) {
            $sheet->getCell("{$col}1")->setValue($header);
        }

        $this->applyHeaderStyle($sheet, 'C1');

        // Attribute 1
        $exampleData1 = [
            'A' => 'var_1',
            'B' => 'Color',
            'C' => 'Black',
        ];

        foreach ($exampleData1 as $col => $value) {
            $sheet->getCell("{$col}2")->setValue($value);
        }

        // Attribute 2
        $exampleData2 = [
            'A' => 'var_2',
            'B' => 'Color',
            'C' => 'White',
        ];

        foreach ($exampleData2 as $col => $value) {
            $sheet->getCell("{$col}3")->setValue($value);
        }

        foreach (array_keys($headers) as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
    }

    private function setupOffersSheet($sheet)
    {
        $headers = [
            'A' => 'product_reference_id',
            'B' => 'type',
            'C' => 'label',
            'D' => 'percentage_value',
            'E' => 'fixed_amount',
            'F' => 'buy_qty',
            'G' => 'get_qty',
            'H' => 'starts_at',
            'I' => 'ends_at',
            'J' => 'target_buy_variants',
            'K' => 'target_reward_variants',
        ];

        foreach ($headers as $col => $header) {
            $sheet->getCell("{$col}1")->setValue($header);
        }

        $this->applyHeaderStyle($sheet, 'K1');

        $exampleData = [
            'A' => 'prod_1',
            'B' => 'percentage',
            'C' => 'Summer Sale 20%',
            'D' => '20.00',
            'E' => '',
            'F' => '',
            'G' => '',
            'H' => '2026-06-01 00:00:00',
            'I' => '2026-06-30 23:59:59',
            'J' => 'var_1,var_2',
            'K' => '',
        ];

        foreach ($exampleData as $col => $value) {
            $sheet->getCell("{$col}2")->setValue($value);
        }

        foreach (array_keys($headers) as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
    }

    private function applyHeaderStyle($sheet, string $endCell)
    {
        $sheet->getStyle("A1:{$endCell}")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '4472C4'],
            ],
        ]);
    }
}
