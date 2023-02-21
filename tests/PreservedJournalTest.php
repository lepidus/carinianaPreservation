<?php

use PHPUnit\Framework\TestCase;

import('classes.submission.Submission');
import('plugins.generic.carinianaPreservation.classes.PreservedJournal');

class PreservedJournalTest extends TestCase
{
    private $preservedJournal;
    private $publisherOrInstitution = 'SciELO';
    private $title = 'SciELO Journal n18';
    private $issn = '1234-1234';
    private $eIssn = '0101-1010';
    private $journalPath = 'scielojournal18';
    private $availableYears = '2018-2022';
    private $notesAndComments = 'We are the 18th SciELO journal';

    public function setUp(): void
    {
        $this->preservedJournal = new PreservedJournal($this->publisherOrInstitution, $this->title, $this->issn, $this->eIssn, $this->journalPath, $this->availableYears, $this->notesAndComments);
    }

    public function testJournalRecord(): void
    {
        $expectedRecord = [
            'SciELO',
            'SciELO Journal n18',
            '1234-1234',
            '0101-1010',
            'scielojournal18',
            '2018-2022',
            'We are the 18th SciELO journal'
        ];
        $this->assertEquals($expectedRecord, $this->preservedJournal->asRecord());
    }
}
