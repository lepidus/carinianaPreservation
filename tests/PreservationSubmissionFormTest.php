<?php

import('lib.pkp.tests.DatabaseTestCase');
import('plugins.generic.carinianaPreservation.CarinianaPreservationPlugin');
import('plugins.generic.carinianaPreservation.classes.form.PreservationSubmissionForm');
import('classes.journal.Journal');
import('classes.journal.JournalDAO');
import('lib.pkp.classes.file.PrivateFileManager');

class PreservationSubmissionFormTest extends DatabaseTestCase
{
    public const JOURNAL_WITH_LOCKSS_ID = 880022;
    public const JOURNAL_WITHOUT_LOCKSS_ID = 880055;

    private $plugin;
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
        $journalsById = [];
        foreach ($journals as $j) {
            $journalsById[$j->getId()] = $j;
        }
        $mockJournalDao = $this->getMockBuilder('JournalDAO')
            ->onlyMethods(['getById'])
            ->getMock();
        $mockJournalDao->method('getById')
            ->willReturnCallback(function ($id) use ($journalsById) {
                return $journalsById[$id] ?? null;
            });
        DAORegistry::registerDAO('JournalDAO', $mockJournalDao);

        $request = Application::get()->getRequest();
        if (!$request->getRouter()) {
            import('lib.pkp.classes.core.PKPRouter');
            $request->setRouter(new PKPRouter());
        }

        $this->insertPublishedIssue(self::JOURNAL_WITH_LOCKSS_ID);
        $this->insertPublishedIssue(self::JOURNAL_WITHOUT_LOCKSS_ID);
        $this->createStatementFileForFirstPreservation();
        $this->plugin->updateSetting(self::JOURNAL_WITHOUT_LOCKSS_ID, 'statementFile', json_encode(['fileName' => 'dummy.pdf']));
    }

    private function buildJournals(): array
    {
        $with = new Journal();
        $with->setId(self::JOURNAL_WITH_LOCKSS_ID);
        $with->setData('publisherInstitution', 'PKP');
        $with->setData('name', 'Revista Teste', 'pt_BR');
        $with->setData('printIssn', '1234-1234');
        $with->setData('onlineIssn', '0101-1010');
        $with->setData('urlPath', 'revistateste');
        $with->setData('acronym', 'RT', 'pt_BR');
        $with->setData('contactEmail', 'contato@revistateste.org');
        $with->setData('enableLockss', true);

        $without = new Journal();
        $without->setId(self::JOURNAL_WITHOUT_LOCKSS_ID);
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
        $dir = $base . '/carinianaPreservation/' . self::JOURNAL_WITH_LOCKSS_ID;
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
        $this->plugin->updateSetting(self::JOURNAL_WITH_LOCKSS_ID, 'statementFile', $data);
    }

    public function testFirstPreservationRemovesStatementFile(): void
    {
        HookRegistry::register('Mail::send', function () {
            return true;
        });

        $form = new PreservationSubmissionForm($this->plugin, self::JOURNAL_WITH_LOCKSS_ID);
        $form->setData('notesAndComments', 'Notas iniciais');

        $fileMgr = new PrivateFileManager();
        $base = rtrim($fileMgr->getBasePath(), '/');
        $path = $base . '/carinianaPreservation/' . self::JOURNAL_WITH_LOCKSS_ID . '/' . $this->statementFileName;
        $this->assertFileExists($path);
        $this->assertNotEmpty($this->plugin->getSetting(self::JOURNAL_WITH_LOCKSS_ID, 'statementFile'));
        $this->assertEmpty($this->plugin->getSetting(self::JOURNAL_WITH_LOCKSS_ID, 'lastPreservationTimestamp'));

        $form->execute();

        $this->assertFileDoesNotExist($path);
        $this->assertEmpty($this->plugin->getSetting(self::JOURNAL_WITH_LOCKSS_ID, 'statementFile'));
        $this->assertNotEmpty($this->plugin->getSetting(self::JOURNAL_WITH_LOCKSS_ID, 'lastPreservationTimestamp'));
    }

    public function testValidationFailsWhenLockssDisabledOnFirstPreservation(): void
    {
        $form = new PreservationSubmissionForm($this->plugin, self::JOURNAL_WITHOUT_LOCKSS_ID);
        $form->setData('notesAndComments', 'Notas');
        $valid = $form->validate();
        $this->assertFalse($valid);
        $this->assertEmpty($this->plugin->getSetting(self::JOURNAL_WITHOUT_LOCKSS_ID, 'lastPreservationTimestamp'));
    }

    public function testValidationBlocksUpdateWhenLockssDisabled(): void
    {
        $this->plugin->updateSetting(self::JOURNAL_WITHOUT_LOCKSS_ID, 'lastPreservationTimestamp', time() - 3600);
        $this->plugin->updateSetting(self::JOURNAL_WITHOUT_LOCKSS_ID, 'preservedXMLcontent', '<xml>anterior</xml>');

        $form = new PreservationSubmissionForm($this->plugin, self::JOURNAL_WITHOUT_LOCKSS_ID);

        $valid = $form->validate();
        $this->assertFalse($valid);
        $errors = $form->getErrorsArray();
        $this->assertArrayHasKey('preservationSubmission', $errors);
        $this->assertMatchesRegularExpression('/lockss/i', $errors['preservationSubmission']);
    }

    protected function tearDown(): void
    {
        HookRegistry::clear('Mail::send');
        foreach ([self::JOURNAL_WITH_LOCKSS_ID, self::JOURNAL_WITHOUT_LOCKSS_ID] as $jid) {
            $this->plugin->updateSetting($jid, 'statementFile', null);
            $this->plugin->updateSetting($jid, 'lastPreservationTimestamp', null);
            $this->plugin->updateSetting($jid, 'preservedXMLcontent', null);
        }
        $fileMgr = new PrivateFileManager();
        $base = rtrim($fileMgr->getBasePath(), '/');
        $dir = $base . '/carinianaPreservation/' . self::JOURNAL_WITH_LOCKSS_ID;
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
