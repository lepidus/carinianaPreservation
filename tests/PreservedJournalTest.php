<?php

namespace APP\plugins\generic\carinianaPreservation\tests;

use PHPUnit\Framework\TestCase;
use APP\plugins\generic\carinianaPreservation\classes\PreservedJournal;

class PreservedJournalTest extends TestCase
{
    private $preservedJournal;
    private $publisherOrInstitution = 'PKP';
    private $title = 'PKP Journal n18';
    private $issn = '1234-1234';
    private $eIssn = '0101-1010';
    private $baseUrl = 'https://pkp-journal-18.test/';
    private $journalPath = 'pkpjournal18';
    private $availableYears = '2018; 2022';
    private $notesAndComments = 'We are the 18th PKP journal';
    private $issuesVolumes = '1; 2; 12; 18';
    private $ojsVersion = '3.3.0.20';

    public function setUp(): void
    {
        $this->preservedJournal = new PreservedJournal(
            $this->publisherOrInstitution,
            $this->title,
            $this->issn,
            $this->eIssn,
            $this->baseUrl,
            $this->journalPath,
            $this->availableYears,
            $this->issuesVolumes,
            $this->notesAndComments,
            $this->ojsVersion,
        );
    }

    public function testJournalRecord(): void
    {
        $expectedRecord = [
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
        ];
        $this->assertEquals($expectedRecord, $this->preservedJournal->asRecord());
    }
}
