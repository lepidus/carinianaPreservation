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
        $this->createStatementFileSetting();
    }

    protected function tearDown(): void
    {
        $this->cleanupStatementDir($this->journal->getId(), $this->statementFileName);
        $this->cleanupJournal($this->journal);
        parent::tearDown();
    }

    private function createStatementFileSetting(): void
    {
        $plugin = new CarinianaPreservationPlugin();
        $fileMgr = new PrivateFileManager();
        $base = rtrim($fileMgr->getBasePath(), '/');
        $dir = $base . '/carinianaPreservation/' . $this->journal->getId();
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
        $plugin->updateSetting($this->journal->getId(), 'statementFile', $statementFileData);
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
