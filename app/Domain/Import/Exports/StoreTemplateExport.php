<?php

namespace App\Domain\Import\Exports;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class StoreTemplateExport
{
    /**
     * Generate the store import template and return the absolute path to the temp file.
     */
    public function generate(): string
    {
        $spreadsheet = new Spreadsheet();

        // 1. Stores Sheet
        $storesSheet = $spreadsheet->getActiveSheet();
        $storesSheet->setTitle('Stores');
        $this->setupStoresSheet($storesSheet);

        // 2. Branches Sheet
        $branchesSheet = $spreadsheet->createSheet();
        $branchesSheet->setTitle('Branches');
        $this->setupBranchesSheet($branchesSheet);

        // 3. Employees Sheet
        $employeesSheet = $spreadsheet->createSheet();
        $employeesSheet->setTitle('Employees');
        $this->setupEmployeesSheet($employeesSheet);

        // 4. Hours Sheet
        $hoursSheet = $spreadsheet->createSheet();
        $hoursSheet->setTitle('Hours');
        $this->setupHoursSheet($hoursSheet);

        // Set first sheet as active
        $spreadsheet->setActiveSheetIndex(0);

        // Write to temp file
        $tempPath = tempnam(sys_get_temp_dir(), 'store_template_') . '.xlsx';
        $writer = new Xlsx($spreadsheet);
        $writer->save($tempPath);

        return $tempPath;
    }

    private function setupStoresSheet($sheet)
    {
        $headers = [
            'A' => 'reference_id',
            'B' => 'name',
            'C' => 'description',
            'D' => 'email',
            'E' => 'phone',
            'F' => 'tax_id',
            'G' => 'commission_rate',
            'H' => 'status',
            'I' => 'owner_email',
            'J' => 'categories',
            'K' => 'logo_image',
            'L' => 'banner_image',
        ];

        foreach ($headers as $col => $header) {
            $sheet->getCell("{$col}1")->setValue($header);
        }

        $this->applyHeaderStyle($sheet, 'L1');

        $exampleData = [
            'A' => 'store_1',
            'B' => 'My Store',
            'C' => 'A description of the store.',
            'D' => 'store@example.com',
            'E' => '+201234567890',
            'F' => '123-456-789',
            'G' => '0.1500',
            'H' => 'pending',
            'I' => 'owner@example.com',
            'J' => 'electronics,clothing',
            'K' => 'logo.png',
            'L' => 'banner.jpg',
        ];

        foreach ($exampleData as $col => $value) {
            $sheet->getCell("{$col}2")->setValue($value);
        }

        foreach (array_keys($headers) as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
    }

    private function setupBranchesSheet($sheet)
    {
        $headers = [
            'A' => 'reference_id',
            'B' => 'store_reference_id',
            'C' => 'first_name',
            'D' => 'last_name',
            'E' => 'phone_number',
            'F' => 'address_line1',
            'G' => 'address_line2',
            'H' => 'city',
            'I' => 'state_province',
            'J' => 'postal_code',
            'K' => 'country_code',
            'L' => 'latitude',
            'M' => 'longitude',
        ];

        foreach ($headers as $col => $header) {
            $sheet->getCell("{$col}1")->setValue($header);
        }

        $this->applyHeaderStyle($sheet, 'M1');

        $exampleData = [
            'A' => 'branch_1',
            'B' => 'store_1',
            'C' => 'Branch',
            'D' => 'Manager',
            'E' => '+201112223333',
            'F' => '123 Main St',
            'G' => 'Suite 100',
            'H' => 'Cairo',
            'I' => 'Cairo',
            'J' => '11511',
            'K' => 'EG',
            'L' => '30.0444',
            'M' => '31.2357',
        ];

        foreach ($exampleData as $col => $value) {
            $sheet->getCell("{$col}2")->setValue($value);
        }

        foreach (array_keys($headers) as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
    }

    private function setupEmployeesSheet($sheet)
    {
        $headers = [
            'A' => 'store_reference_id',
            'B' => 'branch_reference_id',
            'C' => 'user_email',
            'D' => 'role',
            'E' => 'permissions',
        ];

        foreach ($headers as $col => $header) {
            $sheet->getCell("{$col}1")->setValue($header);
        }

        $this->applyHeaderStyle($sheet, 'E1');

        $exampleData = [
            'A' => 'store_1',
            'B' => 'branch_1',
            'C' => 'employee@example.com',
            'D' => 'branch_manager',
            'E' => 'claims:manage,orders:view',
        ];

        foreach ($exampleData as $col => $value) {
            $sheet->getCell("{$col}2")->setValue($value);
        }

        foreach (array_keys($headers) as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
    }

    private function setupHoursSheet($sheet)
    {
        $headers = [
            'A' => 'store_reference_id',
            'B' => 'day_of_week',
            'C' => 'open_time',
            'D' => 'close_time',
            'E' => 'is_closed',
        ];

        foreach ($headers as $col => $header) {
            $sheet->getCell("{$col}1")->setValue($header);
        }

        $this->applyHeaderStyle($sheet, 'E1');

        // Monday (1)
        $exampleData1 = [
            'A' => 'store_1',
            'B' => '1',
            'C' => '09:00',
            'D' => '17:00',
            'E' => '0',
        ];

        foreach ($exampleData1 as $col => $value) {
            $sheet->getCell("{$col}2")->setValue($value);
        }

        // Sunday (0) closed
        $exampleData2 = [
            'A' => 'store_1',
            'B' => '0',
            'C' => '',
            'D' => '',
            'E' => '1',
        ];

        foreach ($exampleData2 as $col => $value) {
            $sheet->getCell("{$col}3")->setValue($value);
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
