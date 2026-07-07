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

    public function testRemoveStatementFileIgnoresUnsafeStoredPath(): void
    {
        $plugin = new CarinianaPreservationPlugin();
        $journalId = $this->journal->getId();
        $outsidePath = tempnam(sys_get_temp_dir(), 'cariniana_outside_');
        file_put_contents($outsidePath, 'DO NOT DELETE');
        $plugin->updateSetting($journalId, 'statementFile', json_encode([
            'originalFileName' => 'outside.pdf',
            'fileName' => '../' . basename($outsidePath),
            'fileType' => 'application/pdf',
        ]));

        $plugin->removeStatementFile($journalId);

        $this->assertFileExists($outsidePath);
        $this->assertSame('DO NOT DELETE', file_get_contents($outsidePath));
        $this->assertEmpty($plugin->getSetting($journalId, 'statementFile'));
        @unlink($outsidePath);
    }
}
