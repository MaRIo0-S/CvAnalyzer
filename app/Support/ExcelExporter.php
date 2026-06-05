<?php

namespace App\Support;

use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\XLSX\Writer;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExcelExporter
{
    /**
     * @param  array<int, array{name: string, headers: array<int, string>, rows: iterable<array<int, string|int|float|null>>}>  $sheets
     */
    public static function download(string $filename, array $sheets): StreamedResponse
    {
        return response()->streamDownload(function () use ($sheets) {
            $path = tempnam(sys_get_temp_dir(), 'xlsx_');
            if ($path === false) {
                throw new \RuntimeException('Impossible de créer un fichier temporaire pour l\'export.');
            }

            $headerStyle = new Style(
                fontBold: true,
                fontColor: 'FFFFFF',
                backgroundColor: '4472C4',
            );

            $writer = new Writer;
            $writer->openToFile($path);

            foreach ($sheets as $index => $sheet) {
                if ($index > 0) {
                    $writer->addNewSheetAndMakeItCurrent();
                }
                $writer->getCurrentSheet()->setName(self::sheetName($sheet['name'] ?? 'Feuille'));

                $headers = $sheet['headers'] ?? [];
                if ($headers !== []) {
                    $writer->addRow(Row::fromValuesWithStyle($headers, $headerStyle));
                }

                foreach ($sheet['rows'] as $row) {
                    $writer->addRow(Row::fromValues(
                        array_map(fn ($cell) => self::cellValue($cell), $row)
                    ));
                }
            }

            $writer->close();
            readfile($path);
            @unlink($path);
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    private static function sheetName(string $name): string
    {
        $clean = preg_replace('/[\\\\\\/\\?\\*\\[\\]:]/', '', $name) ?? 'Feuille';
        $clean = trim($clean) ?: 'Feuille';

        return mb_substr($clean, 0, 31);
    }

    private static function cellValue(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        $text = (string) $value;

        if (preg_match('/^\+?\d{9,15}$/', str_replace([' ', '.', '-'], '', $text))) {
            return "'".$text;
        }

        return $text;
    }
}
