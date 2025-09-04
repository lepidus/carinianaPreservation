<?php

import('lib.pkp.tests.DatabaseTestCase');
import('plugins.generic.carinianaPreservation.CarinianaPreservationPlugin');
import('plugins.generic.carinianaPreservation.classes.form.PreservationSubmissionForm');
import('classes.journal.Journal');
import('classes.issue.Issue');
import('lib.pkp.classes.file.PrivateFileManager');

class JournalDaoMultiStub
{
    private $journals = [];

    public function __construct(array $journals)
    {
        foreach ($journals as $j) {
            $this->journals[$j->getId()] = $j;
        }
    }

    public function getById($id)
    {
        return $this->journals[$id] ?? null;
    }
}

class CarinianaNullRouterStub
{
    public function __call($name, $arguments)
    {
        return null;
    }
}

class PreservationSubmissionFormTest extends DatabaseTestCase
{
    private $plugin;
    private $journalIdWithLockss = 880022;
    private $journalIdWithoutLockss = 880055;
    private $statementFileName = 'responsabilityStatement.pdf';
    private $statementOriginalFileName = 'TermoResponsabilidade.pdf';
    private $originalJournalDao;

    protected function getAffectedTables()
    {
        return ['plugin_settings', 'issues', 'issue_settings'];
    }

    public function setUp(): void
    {
        parent::setUp();
        $this->plugin = new CarinianaPreservationPlugin();
        $this->originalJournalDao = DAORegistry::getDAO('JournalDAO');
        $journals = $this->buildJournals();
        DAORegistry::registerDAO('JournalDAO', new JournalDaoMultiStub($journals));
        $this->insertPublishedIssue($this->journalIdWithLockss);
        $this->insertPublishedIssue($this->journalIdWithoutLockss);
        $this->createStatementFileForFirstPreservation();
        $this->plugin->updateSetting($this->journalIdWithoutLockss, 'statementFile', json_encode(['fileName' => 'dummy.pdf']));
    }

    private function buildJournals(): array
    {
        $with = new Journal();
        $with->setId($this->journalIdWithLockss);
        $with->setData('publisherInstitution', 'PKP');
        $with->setData('name', 'Revista Teste', 'pt_BR');
        $with->setData('printIssn', '1234-1234');
        $with->setData('onlineIssn', '0101-1010');
        $with->setData('urlPath', 'revistateste');
        $with->setData('acronym', 'RT', 'pt_BR');
        $with->setData('contactEmail', 'contato@revistateste.org');
        $with->setData('enableLockss', true);

        $without = new Journal();
        $without->setId($this->journalIdWithoutLockss);
        $without->setData('publisherInstitution', 'PKP');
        $without->setData('name', 'Revista Teste LOCKSS', 'pt_BR');
        $without->setData('printIssn', '2222-2222');
        $without->setData('onlineIssn', '3333-3333');
        $without->setData('urlPath', 'revista-lockss');
        $without->setData('acronym', 'RL', 'pt_BR');
        $without->setData('contactEmail', 'contato@rl.org');
        $without->setData('enableLockss', false);

        return [$with, $without];
    }

    private function insertPublishedIssue(int $journalId): void
    {
        $issueDao = DAORegistry::getDAO('IssueDAO');
        $issueDao->update(
            'INSERT INTO issues (journal_id, date_published, year, published) VALUES (?, ?, ?, 1)',
            [$journalId, '2024-01-01', 2024]
        );
    }

    private function createStatementFileForFirstPreservation(): void
    {
        $fileMgr = new PrivateFileManager();
        $base = rtrim($fileMgr->getBasePath(), '/');
        $dir = $base . '/carinianaPreservation/' . $this->journalIdWithLockss;
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
        $this->plugin->updateSetting($this->journalIdWithLockss, 'statementFile', $data);
    }

    public function testFirstPreservationRemovesStatementFile(): void
    {
        $request = Application::get()->getRequest();
        $request->setRouter(new CarinianaNullRouterStub());
        HookRegistry::register('Mail::send', function () {
            return true;
        });

        $form = new PreservationSubmissionForm($this->plugin, $this->journalIdWithLockss);
        $form->setData('notesAndComments', 'Notas iniciais');

        $fileMgr = new PrivateFileManager();
        $base = rtrim($fileMgr->getBasePath(), '/');
        $path = $base . '/carinianaPreservation/' . $this->journalIdWithLockss . '/' . $this->statementFileName;
        $this->assertFileExists($path);
        $this->assertNotEmpty($this->plugin->getSetting($this->journalIdWithLockss, 'statementFile'));
        $this->assertEmpty($this->plugin->getSetting($this->journalIdWithLockss, 'lastPreservationTimestamp'));

        $form->execute();

        $this->assertFileDoesNotExist($path);
        $this->assertEmpty($this->plugin->getSetting($this->journalIdWithLockss, 'statementFile'));
        $this->assertNotEmpty($this->plugin->getSetting($this->journalIdWithLockss, 'lastPreservationTimestamp'));
    }

    public function testValidationFailsWhenLockssDisabledOnFirstPreservation(): void
    {
        $request = Application::get()->getRequest();
        $request->setRouter(new CarinianaNullRouterStub());
        $form = new PreservationSubmissionForm($this->plugin, $this->journalIdWithoutLockss);
        $form->setData('notesAndComments', 'Notas');
        $valid = $form->validate();
        $this->assertFalse($valid);
        $this->assertEmpty($this->plugin->getSetting($this->journalIdWithoutLockss, 'lastPreservationTimestamp'));
    }

    public function testValidationBlocksUpdateWhenLockssDisabled(): void
    {
        $this->plugin->updateSetting($this->journalIdWithoutLockss, 'lastPreservationTimestamp', time() - 3600);
        $this->plugin->updateSetting($this->journalIdWithoutLockss, 'preservedXMLcontent', '<xml>anterior</xml>');

        $request = Application::get()->getRequest();
        $request->setRouter(new CarinianaNullRouterStub());
        $form = new PreservationSubmissionForm($this->plugin, $this->journalIdWithoutLockss);

        $valid = $form->validate();
        $this->assertFalse($valid);
        $errors = $form->getErrorsArray();
        $this->assertArrayHasKey('preservationSubmission', $errors);
        $this->assertMatchesRegularExpression('/lockss/i', $errors['preservationSubmission']);
    }

    protected function tearDown(): void
    {
        HookRegistry::clear('Mail::send');
        foreach ([$this->journalIdWithLockss, $this->journalIdWithoutLockss] as $jid) {
            $this->plugin->updateSetting($jid, 'statementFile', null);
            $this->plugin->updateSetting($jid, 'lastPreservationTimestamp', null);
            $this->plugin->updateSetting($jid, 'preservedXMLcontent', null);
        }
        $fileMgr = new PrivateFileManager();
        $base = rtrim($fileMgr->getBasePath(), '/');
        $dir = $base . '/carinianaPreservation/' . $this->journalIdWithLockss;
        $path = $dir . '/' . $this->statementFileName;
        if (is_file($path)) {
            @unlink($path);
        }
        if (is_dir($dir)) {
            @rmdir($dir);
        }
        if ($this->originalJournalDao) {
            DAORegistry::registerDAO('JournalDAO', $this->originalJournalDao);
        }
        parent::tearDown();
    }
}
