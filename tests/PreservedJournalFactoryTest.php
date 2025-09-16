<?php

namespace APP\plugins\generic\carinianaPreservation\tests;

use PKP\tests\DatabaseTestCase;
use APP\journal\Journal;
use APP\issue\Issue;
use APP\plugins\generic\carinianaPreservation\classes\PreservedJournalFactory;
use APP\facades\Repo;
use PKP\db\DAORegistry;

class PreservedJournalFactoryTest extends DatabaseTestCase
{
    private $journal;
    private $preservedJournalFactory;
    private $journalId = 77777;
    private $locale = 'pt_BR';
    private $publisherOrInstitution = 'PKP';
    private $title = 'PKP Journal n18';
    private $issn = '1234-1234';
    private $eIssn = '0101-1010';
    private $baseUrl = 'https://pkp-journal-18.test/';
    private $journalPath = 'pkpjournal18';
    private $firstIssueYear = '2018';
    private $lastIssueYear = '2022';
    private $firstIssueVolume = 1;
    private $secondIssueVolume = 2;
    private $notesAndComments = 'We are the 18th PKP journal';
    private $ojsVersion;

    public function setUp(): void
    {
        parent::setUp();
        $this->createTestJournal();
        $this->preservedJournalFactory = new PreservedJournalFactory();
        $this->createTestIssue($this->firstIssueYear, $this->firstIssueVolume);
        $this->createTestIssue($this->lastIssueYear, $this->secondIssueVolume);
        /** @var \PKP\site\VersionDAO $versionDao */
        $versionDao = DAORegistry::getDAO('VersionDAO');
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

        Repo::issue()->add($issue);
    }

    public function testFactoryBuildsPreservedJournal(): void
    {
        $preservedJournal = $this->preservedJournalFactory->buildPreservedJournal($this->journal, $this->baseUrl, $this->notesAndComments, $this->locale);

        $expectedRecord = [
            'PKP',
            'PKP Journal n18',
            '1234-1234',
            '0101-1010',
            'https://pkp-journal-18.test/',
            'pkpjournal18',
            '2018; 2022',
            '1; 2',
            'We are the 18th PKP journal',
            $this->ojsVersion
        ];
        $this->assertEquals($expectedRecord, $preservedJournal->asRecord());
    }

    public function testFactoryBuildsPreservedJournalWithEmptyNotesAndComments(): void
    {
        $preservedJournal = $this->preservedJournalFactory->buildPreservedJournal($this->journal, $this->baseUrl, "", $this->locale);

        $expectedRecord = [
            'PKP',
            'PKP Journal n18',
            '1234-1234',
            '0101-1010',
            'https://pkp-journal-18.test/',
            'pkpjournal18',
            '2018; 2022',
            '1; 2',
            '',
            $this->ojsVersion
        ];
        $this->assertEquals($expectedRecord, $preservedJournal->asRecord());
    }

    public function testVolumesAreNormalizedAndUnique(): void
    {
        $journalId = 88881;
        $journal = new Journal();
        $journal->setId($journalId);
        $journal->setData('publisherInstitution', $this->publisherOrInstitution);
        $journal->setData('name', $this->title, $this->locale);
        $journal->setData('printIssn', $this->issn);
        $journal->setData('onlineIssn', $this->eIssn);
        $journal->setData('urlPath', $this->journalPath);

        $this->createIssueForJournal($journalId, '2020-01-01', 15);
        $this->createIssueForJournal($journalId, '2020-06-01', 15);
        $this->createIssueForJournal($journalId, '2021-01-01', 16);
        $this->createIssueForJournal($journalId, '2021-06-01', 16);
        $this->createIssueForJournal($journalId, '2022-01-01', 19);
        $this->createIssueForJournal($journalId, '2022-06-01', 19);
        $this->createIssueForJournal($journalId, '2023-01-01', 37);
        $this->createIssueForJournal($journalId, '2023-06-01', 0);

        $factory = new PreservedJournalFactory();
        $preservedJournal = $factory->buildPreservedJournal($journal, $this->baseUrl, $this->notesAndComments, $this->locale);

        $record = $preservedJournal->asRecord();
        $issuesVolumes = $record[7];

        $this->assertEquals('15; 16; 19; 37', $issuesVolumes);
    }

    public function testVolumesSkipZeroAndEmpty(): void
    {
        $journalId = 88882;
        $journal = new Journal();
        $journal->setId($journalId);
        $journal->setData('publisherInstitution', $this->publisherOrInstitution);
        $journal->setData('name', $this->title, $this->locale);
        $journal->setData('printIssn', $this->issn);
        $journal->setData('onlineIssn', $this->eIssn);
        $journal->setData('urlPath', $this->journalPath);

        $this->createIssueForJournal($journalId, '2021-01-01', 0);
        $this->createIssueForJournal($journalId, '2021-02-01', 0);
        $this->createIssueForJournal($journalId, '2021-03-01', 0);
        $this->createIssueForJournal($journalId, '2021-04-01', 55);
        $this->createIssueForJournal($journalId, '2021-05-01', 55);

        $factory = new PreservedJournalFactory();
        $preservedJournal = $factory->buildPreservedJournal($journal, $this->baseUrl, $this->notesAndComments, $this->locale);
        $issuesVolumes = $preservedJournal->asRecord()[7];

        $this->assertEquals('55', $issuesVolumes);
    }

    public function testYearsPreferIssueYearWithDatePublishedFallback(): void
    {
        $journalId = 88883;
        $journal = new Journal();
        $journal->setId($journalId);
        $journal->setData('publisherInstitution', $this->publisherOrInstitution);
        $journal->setData('name', $this->title, $this->locale);
        $journal->setData('printIssn', $this->issn);
        $journal->setData('onlineIssn', $this->eIssn);
        $journal->setData('urlPath', $this->journalPath);

        $this->createIssueForJournal($journalId, '2024-07-10', 12);

        $issue = new Issue();
        $issue->setData('journalId', $journalId);
        $issue->setData('year', '2025');
        $issue->setPublished(true);
        Repo::issue()->add($issue);

        $factory = new PreservedJournalFactory();
        $preservedJournal = $factory->buildPreservedJournal($journal, $this->baseUrl, $this->notesAndComments, $this->locale);
        $availableYears = $preservedJournal->asRecord()[6];

        $this->assertEquals('2024; 2025', $availableYears);
    }

    private function createIssueForJournal(int $journalId, string $datePublished, int $volume): void
    {
        $issue = new Issue();
        $issue->setData('journalId', $journalId);
        $issue->setData('datePublished', $datePublished);
        $issue->setVolume($volume);
        $issue->setPublished(true);

        Repo::issue()->add($issue);
    }
}
