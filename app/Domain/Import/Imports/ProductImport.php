<?php

namespace App\Domain\Import\Imports;

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ProductImport
{
    /**
     * Read the Excel file and return an array containing data for each sheet.
     * Expected sheets: 'Products', 'Variants', 'Attributes', 'Offers'.
     *
     * @param string $filePath Absolute path to the .xlsx file.
     * @return array<string, array<int, array<string, mixed>>>
     */
    public function toArray(string $filePath): array
    {
        $spreadsheet = IOFactory::load($filePath);
        
        $data = [
            'Products' => [],
            'Variants' => [],
            'Attributes' => [],
            'Offers' => [],
        ];

        foreach (array_keys($data) as $sheetName) {
            $sheet = $spreadsheet->getSheetByName($sheetName);
            
            // If sheet is missing, fallback to empty array
            if ($sheet === null) {
                // If it's the first sheet and named differently, try to grab index 0 for Products
                if ($sheetName === 'Products' && $spreadsheet->getSheetCount() > 0) {
                    $sheet = $spreadsheet->getSheet(0);
                } else {
                    continue;
                }
            }

            $data[$sheetName] = $this->parseSheet($sheet);
        }

        return $data;
    }

    private function parseSheet(Worksheet $sheet): array
    {
        $rows = $sheet->toArray(null, true, true, false);

        if (empty($rows)) {
            return [];
        }

        // First row is the header
        $headers = array_map(function ($header) {
            return strtolower(trim((string) $header));
        }, array_shift($rows));

        $result = [];
        foreach ($rows as $row) {
            // Skip entirely empty rows
            if (count(array_filter($row, fn($v) => $v !== null && $v !== '')) === 0) {
                continue;
            }

            // Ensure row length matches headers length
            $paddedRow = array_pad($row, count($headers), null);
            $paddedRow = array_slice($paddedRow, 0, count($headers));

            $result[] = array_combine($headers, $paddedRow);
        }

        return $result;
    }
}
