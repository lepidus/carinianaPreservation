<?php

namespace APP\plugins\generic\carinianaPreservation\tests;

use APP\plugins\generic\carinianaPreservation\classes\PreservedJournal;
use APP\plugins\generic\carinianaPreservation\classes\PreservedJournalSpreadsheet;
use PHPUnit\Framework\TestCase;

class PreservedJournalSpreadsheetTest extends TestCase
{
    private $spreadsheet;
    private $filePath = '/tmp/test_spreadsheet.csv';
    private $locale = 'pt_BR';

    protected function setUp(): void
    {
        $journals = [
            new PreservedJournal(
                'PKP',
                'PKP Journal n18',
                '1234-1234',
                '0101-1010',
                'https://pkp-journal-18.test/',
                'pkpjournal18',
                '2018; 2022',
                '1; 2; 12; 18',
                'We are the 18th PKP journal',
                '3.3.0.20'
            )
        ];

        $this->spreadsheet = new PreservedJournalSpreadsheet($journals, $this->locale);
    }

    protected function tearDown(): void
    {
        if (file_exists($this->filePath)) {
            unlink($this->filePath);
        }
    }

    private function getWorksheet()
    {
        $file = fopen($this->filePath, 'r');
        if ($file === false) {
            throw new \RuntimeException("Failed to open file for reading: {$this->filePath}");
        }

        $rows = [];
        while (($row = fgetcsv($file)) !== false) {
            $rows[] = $row;
        }
        fclose($file);

        return $rows;
    }

    public function testGeneratedSpreadsheetHasHeaders(): void
    {
        $this->spreadsheet->createSpreadsheet($this->filePath);
        $rows = $this->getWorksheet();
        $expectedHeaders = [
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

        $firstRow = $rows[0];
        $this->assertEquals($expectedHeaders, $firstRow);
    }

    public function testGeneratedSpreadsheetHasJournals(): void
    {
        $this->spreadsheet->createSpreadsheet($this->filePath);
        $rows = $this->getWorksheet();

        $expectedJournalData = ['PKP', 'PKP Journal n18', '1234-1234', '0101-1010', 'https://pkp-journal-18.test/', 'pkpjournal18', '2018; 2022', '1; 2; 12; 18', 'We are the 18th PKP journal', '3.3.0.20'];

        $this->assertEquals($expectedJournalData, $rows[1]);
    }
}
