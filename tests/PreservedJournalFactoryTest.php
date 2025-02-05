<?php

import('lib.pkp.tests.DatabaseTestCase');
import('classes.journal.Journal');
import('classes.issue.Issue');
import('plugins.generic.carinianaPreservation.classes.PreservedJournalFactory');

class PreservedJournalFactoryTest extends DatabaseTestCase
{
    private $journal;
    private $preservedJournalFactory;
    private $journalId = 2;
    private $locale = 'pt_BR';
    private $publisherOrInstitution = 'SciELO';
    private $title = 'SciELO Journal n18';
    private $issn = '1234-1234';
    private $eIssn = '0101-1010';
    private $baseUrl = 'https://scielo-journal-18.com.br/';
    private $journalPath = 'scielojournal18';
    private $firstIssueYear = '2018';
    private $lastIssueYear = '2022';
    private $firstIssueVolume = 1;
    private $secondIssueVolume = 2;
    private $notesAndComments = 'We are the 18th SciELO journal';
    private $ojsVersion;

    public function setUp(): void
    {
        parent::setUp();
        $this->createTestJournal();
        $this->preservedJournalFactory = new PreservedJournalFactory();
        $this->createTestIssue($this->firstIssueYear, $this->firstIssueVolume);
        $this->createTestIssue($this->lastIssueYear, $this->secondIssueVolume);
        $versionDao = DAORegistry::getDAO('VersionDAO'); /* @var $versionDao VersionDAO */
		$currentVersion = $versionDao->getCurrentVersion();
        $this->ojsVersion = $currentVersion->getVersionString();
    }

    protected function getAffectedTables()
    {
        return ['issues', 'issue_settings'];
    }

    private function createTestJournal(): void
    {
        $this->journal = new Journal();
        $this->journal->setId($this->journalId);
        $this->journal->setData('publisherInstitution', $this->publisherOrInstitution);
        $this->journal->setData('name', $this->title, $this->locale);
        $this->journal->setData('printIssn', $this->issn);
        $this->journal->setData('onlineIssn', $this->eIssn);
        $this->journal->setData('urlPath', $this->journalPath);
    }

    private function createTestIssue($issueYear, $issueVolume): void
    {
        $issueDatePublished = $issueYear.'-01-01';

        $issue = new Issue();
        $issue->setData('journalId', $this->journalId);
        $issue->setData('datePublished', $issueDatePublished);
        $issue->setVolume($issueVolume);
        $issue->setPublished(true);

        $issueDao = DAORegistry::getDAO('IssueDAO');
        $issueDao->insertObject($issue);
    }

    public function testFactoryBuildsPreservedJournal(): void
    {
        $preservedJournal = $this->preservedJournalFactory->buildPreservedJournal($this->journal, $this->baseUrl, $this->notesAndComments, $this->locale);

        $expectedRecord = [
            'SciELO',
            'SciELO Journal n18',
            '1234-1234',
            '0101-1010',
            'https://scielo-journal-18.com.br/',
            'scielojournal18',
            '2018; 2022',
            '1; 2',
            'We are the 18th SciELO journal',
            $this->ojsVersion
        ];
        $this->assertEquals($expectedRecord, $preservedJournal->asRecord());
    }

    public function testFactoryBuildsPreservedJournalWithEmptyNotesAndComments(): void
    {
        $preservedJournal = $this->preservedJournalFactory->buildPreservedJournal($this->journal, $this->baseUrl, "", $this->locale);

        $expectedRecord = [
            'SciELO',
            'SciELO Journal n18',
            '1234-1234',
            '0101-1010',
            'https://scielo-journal-18.com.br/',
            'scielojournal18',
            '2018; 2022',
            '1; 2',
            '',
            $this->ojsVersion
        ];
        $this->assertEquals($expectedRecord, $preservedJournal->asRecord());
    }
}
