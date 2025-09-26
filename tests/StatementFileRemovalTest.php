<?php

namespace APP\plugins\generic\carinianaPreservation\tests;

use APP\journal\Journal;
use APP\plugins\generic\carinianaPreservation\CarinianaPreservationPlugin;
use PKP\file\PrivateFileManager;
use PKP\tests\DatabaseTestCase;

class StatementFileRemovalTest extends DatabaseTestCase
{
    private $journalId = 888888;
    private $statementFileName = 'responsabilityStatement.pdf';
    private $statementOriginalFileName = 'Termos_responsabilidade_cariniana.pdf';

    public function setUp(): void
    {
        parent::setUp();
        $this->createJournal();
        $this->createStatementFileSetting();
    }

    protected function tearDown(): void
    {
        $fileMgr = new PrivateFileManager();
        $base = rtrim($fileMgr->getBasePath(), '/');
        $dir = $base . '/carinianaPreservation/' . $this->journalId;
        $path = $dir . '/' . $this->statementFileName;
        if (is_file($path)) {
            unlink($path);
        }
        if (is_dir($dir)) {
            @rmdir($dir);
        }
        parent::tearDown();
    }

    protected function getAffectedTables()
    {
        return ['plugin_settings'];
    }

    private function createJournal(): void
    {
        $journal = new Journal();
        $journal->setId($this->journalId);
    }

    private function createStatementFileSetting(): void
    {
        $plugin = new CarinianaPreservationPlugin();
        $fileMgr = new PrivateFileManager();
        $base = rtrim($fileMgr->getBasePath(), '/');
        $dir = $base . '/carinianaPreservation/' . $this->journalId;
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        $fullPath = $dir . '/' . $this->statementFileName;
        file_put_contents($fullPath, 'PDF');
        $statementFileData = json_encode([
            'originalFileName' => $this->statementOriginalFileName,
            'fileName' => $this->statementFileName,
            'fileType' => 'application/pdf',
        ]);
        $plugin->updateSetting($this->journalId, 'statementFile', $statementFileData);
    }

    public function testRemoveStatementFile(): void
    {
        $plugin = new CarinianaPreservationPlugin();
        $plugin->removeStatementFile($this->journalId);
        $fileMgr = new PrivateFileManager();
        $base = rtrim($fileMgr->getBasePath(), '/');
        $path = $base . '/carinianaPreservation/' . $this->journalId . '/' . $this->statementFileName;
        $this->assertFileDoesNotExist($path);
        $this->assertEmpty($plugin->getSetting($this->journalId, 'statementFile'));
    }
}
