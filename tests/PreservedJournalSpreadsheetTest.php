<?php

namespace APP\plugins\generic\carinianaPreservation\tests;

use PhpOffice\PhpSpreadsheet\IOFactory;
use PHPUnit\Framework\TestCase;
use APP\plugins\generic\carinianaPreservation\classes\PreservedJournalSpreadsheet;
use APP\plugins\generic\carinianaPreservation\classes\PreservedJournal;

class PreservedJournalSpreadsheetTest extends TestCase
{
    private $spreadsheet;
    private $filePath = '/tmp/test_spreadsheet.xlsx';

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

        $this->spreadsheet = new PreservedJournalSpreadsheet($journals);
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
            __("plugins.generic.carinianaPreservation.headers.publisherOrInstitution"),
            __("plugins.generic.carinianaPreservation.headers.title"),
            __("plugins.generic.carinianaPreservation.headers.issn"),
            __("plugins.generic.carinianaPreservation.headers.eIssn"),
            __("plugins.generic.carinianaPreservation.headers.baseUrl"),
            __("plugins.generic.carinianaPreservation.headers.journalPath"),
            __("plugins.generic.carinianaPreservation.headers.availableYears"),
            __("plugins.generic.carinianaPreservation.headers.issuesVolumes"),
            __("plugins.generic.carinianaPreservation.headers.notesAndComments"),
            __("admin.systemVersion")
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
