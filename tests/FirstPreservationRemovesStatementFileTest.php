<?php

import('lib.pkp.tests.DatabaseTestCase');
import('plugins.generic.carinianaPreservation.CarinianaPreservationPlugin');
import('plugins.generic.carinianaPreservation.classes.form.PreservationSubmissionForm');
import('classes.journal.Journal');
import('classes.issue.Issue');
import('lib.pkp.classes.file.PrivateFileManager');

class TestJournalDaoStub
{
    private $journal;

    public function __construct(Journal $journal)
    {
        $this->journal = $journal;
    }

    public function getById($id)
    {
        return $id == $this->journal->getId() ? $this->journal : null;
    }
}

class NullRouterStub
{
    public function __call($name, $arguments)
    {
        return null;
    }
}

class FirstPreservationRemovesStatementFileTest extends DatabaseTestCase
{
    private $journalId = 880022;
    private $statementFileName = 'responsabilityStatement.pdf';
    private $statementOriginalFileName = 'TermoResponsabilidade.pdf';
    private $plugin;

    protected function getAffectedTables()
    {
        return ['plugin_settings', 'issues', 'issue_settings'];
    }

    public function setUp(): void
    {
        parent::setUp();
        $this->plugin = new CarinianaPreservationPlugin();
        $this->registerStubJournalDao();
        $this->createStatementFileSettingAndFile();
    }

    private function registerStubJournalDao(): void
    {
        $journal = new Journal();
        $journal->setId($this->journalId);
        $journal->setData('publisherInstitution', 'PKP');
        $journal->setData('name', 'Revista Teste', 'pt_BR');
        $journal->setData('printIssn', '1234-1234');
        $journal->setData('onlineIssn', '0101-1010');
        $journal->setData('urlPath', 'revistateste');
        $journal->setData('acronym', 'RT', 'pt_BR');
        $journal->setData('contactEmail', 'contato@revistateste.org');
        $journal->setData('enableLockss', true);

        $issue = new Issue();
        $issue->setData('journalId', $this->journalId);
        $issue->setData('datePublished', '2024-01-01');
        $issue->setData('year', '2024');
        $issue->setData('published', 1);
        $issueDao = DAORegistry::getDAO('IssueDAO'); /** @var IssueDAO $issueDao */
        $issueDao->insertObject($issue);

        $stub = new TestJournalDaoStub($journal);
        DAORegistry::registerDAO('JournalDAO', $stub);
    }

    private function createStatementFileSettingAndFile(): void
    {
        $fileMgr = new PrivateFileManager();
        $base = rtrim($fileMgr->getBasePath(), '/');
        $dir = $base . '/carinianaPreservation/' . $this->journalId;
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        $fullPath = $dir . '/' . $this->statementFileName;
        file_put_contents($fullPath, 'PDFTEST');
        $data = json_encode([
            'originalFileName' => $this->statementOriginalFileName,
            'fileName' => $this->statementFileName,
            'fileType' => 'application/pdf'
        ]);
        $this->plugin->updateSetting($this->journalId, 'statementFile', $data);
    }

    public function testFirstPreservationRemovesStatementFile(): void
    {
        $request = Application::get()->getRequest();
        $routerStub = new NullRouterStub();
        $request->setRouter($routerStub);

        $form = new PreservationSubmissionForm($this->plugin, $this->journalId);
        $form->setData('notesAndComments', 'Notas iniciais');

        $fileMgr = new PrivateFileManager();
        $base = rtrim($fileMgr->getBasePath(), '/');
        $path = $base . '/carinianaPreservation/' . $this->journalId . '/' . $this->statementFileName;
        $this->assertFileExists($path);
        $this->assertNotEmpty($this->plugin->getSetting($this->journalId, 'statementFile'));
        $this->assertEmpty($this->plugin->getSetting($this->journalId, 'lastPreservationTimestamp'));

        HookRegistry::register('Mail::send', function () {
            return true;
        });
        $form->execute();

        $this->assertFileDoesNotExist($path);
        $this->assertEmpty($this->plugin->getSetting($this->journalId, 'statementFile'));
        $this->assertNotEmpty($this->plugin->getSetting($this->journalId, 'lastPreservationTimestamp'));
    }

    protected function tearDown(): void
    {
        HookRegistry::clear('Mail::send');
        $request = Application::get()->getRequest();
        $request->setRouter(null);
        $this->plugin->updateSetting($this->journalId, 'statementFile', null);
        $this->plugin->updateSetting($this->journalId, 'lastPreservationTimestamp', null);
        $this->plugin->updateSetting($this->journalId, 'preservedXMLcontent', null);
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
}
