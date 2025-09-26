<?php

namespace APP\plugins\generic\carinianaPreservation\tests;

use APP\plugins\generic\carinianaPreservation\classes\PreservedJournalFactory;
use PKP\db\DAORegistry;
use PKP\tests\DatabaseTestCase;

class PreservedJournalFactoryTest extends DatabaseTestCase
{
    use CarinianaTestFixtureTrait;
    private $journal;
    private $preservedJournalFactory;
    private $journalId;
    private $locale = 'pt_BR';
    private $publisherOrInstitution = 'PKP';
    private $title = 'PKP Journal n18';
    private $issn = '1234-1234';
    private $eIssn = '0101-1010';
    private $baseUrl = 'https://pkp-journal-18.test/';
    private $journalPath = 'pkpjournal18';
    private $journalPathPersisted;
    private $firstIssueYear = '2018';
    private $lastIssueYear = '2022';
    private $firstIssueVolume = 1;
    private $secondIssueVolume = 2;
    private $notesAndComments = 'We are the 18th PKP journal';
    private $ojsVersion;

    public function setUp(): void
    {
        parent::setUp();
        $this->journalPathPersisted = $this->journalPath . '_' . uniqid();
        $this->journal = $this->buildAndPersistJournal([
            'publisherInstitution' => $this->publisherOrInstitution,
            'name' => $this->title,
            'printIssn' => $this->issn,
            'onlineIssn' => $this->eIssn,
            'urlPath' => $this->journalPathPersisted,
            'primaryLocale' => $this->locale,
        ]);
        $this->journalId = $this->journal->getId();
        $this->preservedJournalFactory = new PreservedJournalFactory();
        $this->persistIssue($this->journal, ['year' => $this->firstIssueYear, 'volume' => $this->firstIssueVolume]);
        $this->persistIssue($this->journal, ['year' => $this->lastIssueYear, 'volume' => $this->secondIssueVolume]);
        /** @var \PKP\site\VersionDAO $versionDao */
        $versionDao = DAORegistry::getDAO('VersionDAO');
        $currentVersion = $versionDao->getCurrentVersion();
        $this->ojsVersion = $currentVersion->getVersionString();
    }

    protected function getAffectedTables()
    {
        return ['issues', 'issue_settings'];
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
            $this->journalPathPersisted,
            '2018; 2022',
            '1; 2',
            'We are the 18th PKP journal',
            $this->ojsVersion
        ];
        $this->assertEquals($expectedRecord, $preservedJournal->asRecord());
    }

    public function testFactoryBuildsPreservedJournalWithEmptyNotesAndComments(): void
    {
        $preservedJournal = $this->preservedJournalFactory->buildPreservedJournal($this->journal, $this->baseUrl, '', $this->locale);

        $expectedRecord = [
            'PKP',
            'PKP Journal n18',
            '1234-1234',
            '0101-1010',
            'https://pkp-journal-18.test/',
            $this->journalPathPersisted,
            '2018; 2022',
            '1; 2',
            '',
            $this->ojsVersion
        ];
        $this->assertEquals($expectedRecord, $preservedJournal->asRecord());
    }

    public function testVolumesAreNormalizedAndUnique(): void
    {
        $journal = $this->buildAndPersistJournal([
            'publisherInstitution' => $this->publisherOrInstitution,
            'name' => $this->title,
            'printIssn' => $this->issn,
            'onlineIssn' => $this->eIssn,
            'urlPath' => $this->journalPath . '_' . uniqid(),
            'primaryLocale' => $this->locale,
        ]);
        $this->persistIssue($journal, ['datePublished' => '2020-01-01', 'volume' => 15]);
        $this->persistIssue($journal, ['datePublished' => '2020-06-01', 'volume' => 15]);
        $this->persistIssue($journal, ['datePublished' => '2021-01-01', 'volume' => 16]);
        $this->persistIssue($journal, ['datePublished' => '2021-06-01', 'volume' => 16]);
        $this->persistIssue($journal, ['datePublished' => '2022-01-01', 'volume' => 19]);
        $this->persistIssue($journal, ['datePublished' => '2022-06-01', 'volume' => 19]);
        $this->persistIssue($journal, ['datePublished' => '2023-01-01', 'volume' => 37]);
        $this->persistIssue($journal, ['datePublished' => '2023-06-01', 'volume' => 0]);

        $factory = new PreservedJournalFactory();
        $preservedJournal = $factory->buildPreservedJournal($journal, $this->baseUrl, $this->notesAndComments, $this->locale);

        $record = $preservedJournal->asRecord();
        $issuesVolumes = $record[7];

        $this->assertEquals('15; 16; 19; 37', $issuesVolumes);
    }

    public function testVolumesSkipZeroAndEmpty(): void
    {
        $journal = $this->buildAndPersistJournal([
            'publisherInstitution' => $this->publisherOrInstitution,
            'name' => $this->title,
            'printIssn' => $this->issn,
            'onlineIssn' => $this->eIssn,
            'urlPath' => $this->journalPath . '_' . uniqid(),
            'primaryLocale' => $this->locale,
        ]);
        $this->persistIssue($journal, ['datePublished' => '2021-01-01', 'volume' => 0]);
        $this->persistIssue($journal, ['datePublished' => '2021-02-01', 'volume' => 0]);
        $this->persistIssue($journal, ['datePublished' => '2021-03-01', 'volume' => 0]);
        $this->persistIssue($journal, ['datePublished' => '2021-04-01', 'volume' => 55]);
        $this->persistIssue($journal, ['datePublished' => '2021-05-01', 'volume' => 55]);

        $factory = new PreservedJournalFactory();
        $preservedJournal = $factory->buildPreservedJournal($journal, $this->baseUrl, $this->notesAndComments, $this->locale);
        $issuesVolumes = $preservedJournal->asRecord()[7];

        $this->assertEquals('55', $issuesVolumes);
    }

    public function testYearsPreferIssueYearWithDatePublishedFallback(): void
    {
        $journal = $this->buildAndPersistJournal([
            'publisherInstitution' => $this->publisherOrInstitution,
            'name' => $this->title,
            'printIssn' => $this->issn,
            'onlineIssn' => $this->eIssn,
            'urlPath' => $this->journalPath . '_' . uniqid(),
            'primaryLocale' => $this->locale,
        ]);

        $this->persistIssue($journal, ['datePublished' => '2024-07-10', 'volume' => 12]);
        $this->persistIssue($journal, ['year' => '2025']);

        $factory = new PreservedJournalFactory();
        $preservedJournal = $factory->buildPreservedJournal($journal, $this->baseUrl, $this->notesAndComments, $this->locale);
        $availableYears = $preservedJournal->asRecord()[6];

        $this->assertEquals('2024; 2025', $availableYears);
    }
}
