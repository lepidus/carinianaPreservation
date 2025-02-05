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
    private $baseUrl = 'https://scielo-journal-18.com.br/';
    private $journalPath = 'scielojournal18';
    private $availableYears = '2018; 2022';
    private $notesAndComments = 'We are the 18th SciELO journal';
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
            'SciELO',
            'SciELO Journal n18',
            '1234-1234',
            '0101-1010',
            'https://scielo-journal-18.com.br/',
            'scielojournal18',
            '2018; 2022',
            '1; 2; 12; 18',
            'We are the 18th SciELO journal',
            '3.3.0.20'
        ];
        $this->assertEquals($expectedRecord, $this->preservedJournal->asRecord());
    }
}
