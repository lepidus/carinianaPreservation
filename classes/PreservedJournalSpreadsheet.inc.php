<?php

require __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class PreservedJournalSpreadsheet
{
    private $journals;

    public function __construct(array $journals)
    {
        $this->journals = $journals;
    }

    private function getHeaders(): array
    {
        return [
            __("plugins.generic.carinianaPreservation.headers.publisherOrInstitution"),
            __("plugins.generic.carinianaPreservation.headers.title"),
            __("plugins.generic.carinianaPreservation.headers.issn"),
            __("plugins.generic.carinianaPreservation.headers.eIssn"),
            __("plugins.generic.carinianaPreservation.headers.baseUrl"),
            __("plugins.generic.carinianaPreservation.headers.journalPath"),
            __("plugins.generic.carinianaPreservation.headers.availableYears"),
            __("plugins.generic.carinianaPreservation.headers.notesAndComments")
        ];
    }

    private function writeRowOnSpreadSheet($worksheet, $data, $rowIndex)
    {
        for($columnIndex = 1; $columnIndex <= count($data); $columnIndex++) {
            $worksheet->setCellValueByColumnAndRow($columnIndex, $rowIndex, $data[$columnIndex-1]);
        }
    }

    public function createSpreadsheet(string $filePath)
    {
        $spreadsheet = new Spreadsheet();
        $worksheet = $spreadsheet->getActiveSheet();

        $this->writeRowOnSpreadSheet($worksheet, $this->getHeaders(), 1);

        $rowIndex = 2;
        foreach($this->journals as $journal) {
            $this->writeRowOnSpreadSheet($worksheet, $journal->asRecord(), $rowIndex);
            $rowIndex++;
        }

        $writer = new Xlsx($spreadsheet);
        $writer->save($filePath);
    }
}