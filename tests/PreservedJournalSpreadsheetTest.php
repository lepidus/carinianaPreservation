<?php

require __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;
use PHPUnit\Framework\TestCase;

import('classes.submission.Submission');
import('plugins.generic.carinianaPreservation.classes.PreservedJournalSpreadsheet');

class PreservedJournalSpreadsheetTest extends TestCase
{
    private $spreadsheet;
    private $filePath = '/tmp/test_spreadsheet.xlsx';

    public function setUp(): void
    {
        $journals = [
            new PreservedJournal(
                'SciELO',
                'SciELO Journal n18',
                '1234-1234',
                '0101-1010',
                'https://scielo-journal-18.com.br/',
                'scielojournal18',
                '2018; 2022',
                '1; 2; 12; 18',
                'We are the 18th SciELO journal'
            )
        ];

        $this->spreadsheet = new PreservedJournalSpreadsheet($journals);
    }

    public function tearDown(): void
    {
        if (file_exists(($this->filePath))) {
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
        ];

        $firstRow = $worksheet->toArray()[0];

        $this->assertEquals($expectedHeaders, $firstRow);
    }

    public function testGeneratedSpreadsheetHasJournals(): void
    {
        $this->spreadsheet->createSpreadsheet($this->filePath);
        $worksheet = $this->getWorksheet();

        $expectedJournalData = ['SciELO', 'SciELO Journal n18', '1234-1234', '0101-1010', 'https://scielo-journal-18.com.br/', 'scielojournal18', '2018; 2022', '1; 2; 12; 18', 'We are the 18th SciELO journal'];
        $secondRow = $worksheet->toArray()[1];

        $this->assertEquals($expectedJournalData, $secondRow);
    }
}
