<?php

namespace APP\plugins\generic\carinianaPreservation\tests;

use PKP\db\DAORegistry;
use APP\journal\Journal;
use APP\facades\Repo;
use APP\issue\Issue;
use PKP\file\PrivateFileManager;

trait CarinianaTestFixtureTrait
{
    protected function buildAndPersistJournal(array $overrides = []): Journal
    {
        /** @var \APP\journal\JournalDAO $journalDao */
        $journalDao = DAORegistry::getDAO('JournalDAO');
        $journal = $journalDao->newDataObject();

        $locale = $overrides['primaryLocale'] ?? 'en';
        $journal->setData('primaryLocale', $locale);
        $journal->setData('urlPath', $overrides['urlPath'] ?? ('journal' . uniqid()));
        $journal->setData('enabled', 1);
        $journal->setData('seq', 1);
        $journal->setData('name', $overrides['name'] ?? 'Test Journal', $locale);
        $journal->setData('acronym', $overrides['acronym'] ?? 'TJ', $locale);
        $journal->setData('contactEmail', $overrides['contactEmail'] ?? 'contact@example.test');
        $journal->setData('publisherInstitution', $overrides['publisherInstitution'] ?? 'PKP');
        if (isset($overrides['printIssn'])) {
            $journal->setData('printIssn', $overrides['printIssn']);
        }
        if (isset($overrides['onlineIssn'])) {
            $journal->setData('onlineIssn', $overrides['onlineIssn']);
        }
        if (isset($overrides['enableLockss'])) {
            $journal->setData('enableLockss', $overrides['enableLockss']);
        }

        $journalDao->insertObject($journal);
        return $journal;
    }

    protected function persistIssue(Journal $journal, array $overrides = []): Issue
    {
        $issue = Repo::issue()->newDataObject();
        $year = $overrides['year'] ?? date('Y');
        $issue->setData('journalId', $journal->getId());
        $issue->setData('datePublished', $overrides['datePublished'] ?? ($year . '-01-01'));
        if (isset($overrides['year'])) {
            $issue->setData('year', $year);
        }
        if (isset($overrides['volume'])) {
            $issue->setVolume($overrides['volume']);
        }
        if (isset($overrides['number'])) {
            $issue->setNumber($overrides['number']);
        }
        $issue->setPublished(true);
        Repo::issue()->add($issue);
        return $issue;
    }

    protected function createStatementFile(int $journalId, string $generatedName, string $content = 'PDF'): void
    {
        $fileMgr = new PrivateFileManager();
        $base = rtrim($fileMgr->getBasePath(), '/');
        $dir = $base . '/carinianaPreservation/' . $journalId;
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        file_put_contents($dir . '/' . $generatedName, $content);
    }

    protected function cleanupStatementDir(int $journalId, ?string $fileName = null): void
    {
    $fileMgr = new PrivateFileManager();
        $base = rtrim($fileMgr->getBasePath(), '/');
        $dir = $base . '/carinianaPreservation/' . $journalId;
        if ($fileName && is_file($dir . '/' . $fileName)) {
            @unlink($dir . '/' . $fileName);
        }
        if (is_dir($dir)) {
            @rmdir($dir);
        }
    }
}
