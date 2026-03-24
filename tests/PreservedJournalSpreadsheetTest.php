<?php

use PHPUnit\Framework\TestCase;

import('classes.submission.Submission');
import('plugins.generic.carinianaPreservation.classes.PreservedJournalSpreadsheet');
import('plugins.generic.carinianaPreservation.classes.PreservedJournal');

class PreservedJournalSpreadsheetTest extends TestCase
{
    private $spreadsheet;
    private $filePath = '/tmp/test_spreadsheet.csv';

    protected function setUp(): void
    {
        $preservedJournal = new PreservedJournal(
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
        );

        $this->spreadsheet = new PreservedJournalSpreadsheet($preservedJournal);
    }

    protected function tearDown(): void
    {
        if (file_exists($this->filePath)) {
            unlink($this->filePath);
        }
    }

    private function getCsvData(): array
    {
        $handle = fopen($this->filePath, 'r');
        $data = [];
        while (($row = fgetcsv($handle)) !== false) {
            $data[] = $row;
        }
        fclose($handle);
        return $data;
    }

    public function testGeneratedCsvHasHeaders(): void
    {
        $this->spreadsheet->createCsv($this->filePath);
        $data = $this->getCsvData();

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

        $this->assertEquals($expectedHeaders, $data[0]);
    }

    public function testGeneratedCsvHasJournalData(): void
    {
        $this->spreadsheet->createCsv($this->filePath);
        $data = $this->getCsvData();

        $expectedJournalData = ['PKP', 'PKP Journal n18', '1234-1234', '0101-1010', 'https://pkp-journal-18.test/', 'pkpjournal18', '2018; 2022', '1; 2; 12; 18', 'We are the 18th PKP journal', '3.3.0.20'];
        $this->assertEquals($expectedJournalData, $data[1]);
    }
}
