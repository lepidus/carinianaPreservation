<?php

namespace APP\plugins\generic\carinianaPreservation\classes;

require __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class PreservedJournalSpreadsheet
{
    private $journals;
    private $locale;

    public function __construct(array $journals, string $locale)
    {
        $this->journals = $journals;
        $this->locale = $locale;
    }

    private function getHeaders(): array
    {
        return [
            __('plugins.generic.carinianaPreservation.headers.publisherOrInstitution', [], $this->locale),
            __('plugins.generic.carinianaPreservation.headers.title', [], $this->locale),
            __('plugins.generic.carinianaPreservation.headers.issn', [], $this->locale),
            __('plugins.generic.carinianaPreservation.headers.eIssn', [], $this->locale),
            __('plugins.generic.carinianaPreservation.headers.baseUrl', [], $this->locale),
            __('plugins.generic.carinianaPreservation.headers.journalPath', [], $this->locale),
            __('plugins.generic.carinianaPreservation.headers.availableYears', [], $this->locale),
            __('plugins.generic.carinianaPreservation.headers.issuesVolumes', [], $this->locale),
            __('plugins.generic.carinianaPreservation.headers.notesAndComments', [], $this->locale),
            __('admin.systemVersion', [], $this->locale)
        ];
    }

    private function writeRowOnSpreadSheet($worksheet, $data, $rowIndex)
    {
        for ($columnIndex = 1; $columnIndex <= count($data); $columnIndex++) {
            $worksheet->setCellValueByColumnAndRow($columnIndex, $rowIndex, $data[$columnIndex - 1]);
        }
    }

    public function createSpreadsheet(string $filePath)
    {
        $spreadsheet = new Spreadsheet();
        $worksheet = $spreadsheet->getActiveSheet();

        $this->writeRowOnSpreadSheet($worksheet, $this->getHeaders(), 1);

        $rowIndex = 2;
        foreach ($this->journals as $journal) {
            $this->writeRowOnSpreadSheet($worksheet, $journal->asRecord(), $rowIndex);
            $rowIndex++;
        }

        $writer = new Xlsx($spreadsheet);
        $writer->save($filePath);
    }
}
