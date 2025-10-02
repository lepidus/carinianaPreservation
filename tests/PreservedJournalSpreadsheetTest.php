<?php

namespace APP\plugins\generic\carinianaPreservation\tests;

use APP\plugins\generic\carinianaPreservation\classes\PreservedJournal;
use APP\plugins\generic\carinianaPreservation\classes\PreservedJournalSpreadsheet;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PHPUnit\Framework\TestCase;

class PreservedJournalSpreadsheetTest extends TestCase
{
    private $spreadsheet;
    private $filePath = '/tmp/test_spreadsheet.xlsx';
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
        $reader = IOFactory::createReader('Xlsx');
        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($this->filePath);
        return $spreadsheet->getActiveSheet();
    }

    public function testGeneratedSpreadsheetHasHeaders(): void
    {
        $this->spreadsheet->createSpreadsheet($this->filePath);
        $worksheet = $this->getWorksheet();
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

        $firstRow = $worksheet->toArray()[0];

        $this->assertEquals($expectedHeaders, $firstRow);
    }

    public function testGeneratedSpreadsheetHasJournals(): void
    {
        $this->spreadsheet->createSpreadsheet($this->filePath);
        $worksheet = $this->getWorksheet();

        $expectedJournalData = ['PKP', 'PKP Journal n18', '1234-1234', '0101-1010', 'https://pkp-journal-18.test/', 'pkpjournal18', '2018; 2022', '1; 2; 12; 18', 'We are the 18th PKP journal', '3.3.0.20'];
        $secondRow = $worksheet->toArray()[1];

        $this->assertEquals($expectedJournalData, $secondRow);
    }
}
