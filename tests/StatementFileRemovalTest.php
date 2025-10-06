<?php

namespace APP\plugins\generic\carinianaPreservation\tests;

use APP\journal\Journal;
use APP\plugins\generic\carinianaPreservation\CarinianaPreservationPlugin;
use PKP\file\PrivateFileManager;
use PKP\tests\PKPTestCase;

class StatementFileRemovalTest extends PKPTestCase
{
    use CarinianaTestFixtureTrait;

    private $journal;
    private $statementFileName = 'responsabilityStatement.pdf';
    private $statementOriginalFileName = 'Termos_responsabilidade_cariniana.pdf';

    public function setUp(): void
    {
        parent::setUp();
        $this->journal = $this->buildAndPersistJournal();
        $this->createStatementFileWithSettings(
            $this->journal->getId(),
            $this->statementFileName,
            $this->statementOriginalFileName
        );
    }

    protected function tearDown(): void
    {
        $this->cleanupStatementDir($this->journal->getId(), $this->statementFileName);
        $this->cleanupJournal($this->journal);
        parent::tearDown();
    }

    public function testRemoveStatementFile(): void
    {
        $plugin = new CarinianaPreservationPlugin();
        $plugin->removeStatementFile($this->journal->getId());
        $fileMgr = new PrivateFileManager();
        $base = rtrim($fileMgr->getBasePath(), '/');
        $path = $base . '/carinianaPreservation/' . $this->journal->getId() . '/' . $this->statementFileName;
        $this->assertFileDoesNotExist($path);
        $this->assertEmpty($plugin->getSetting($this->journal->getId(), 'statementFile'));
    }
}
