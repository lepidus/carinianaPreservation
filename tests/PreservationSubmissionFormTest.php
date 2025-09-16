<?php

namespace APP\plugins\generic\carinianaPreservation\tests;

use PKP\tests\DatabaseTestCase;
use APP\plugins\generic\carinianaPreservation\CarinianaPreservationPlugin;
use APP\plugins\generic\carinianaPreservation\classes\form\PreservationSubmissionForm;
use APP\journal\Journal;
use PKP\file\PrivateFileManager;
use PKP\plugins\Hook;
use APP\core\Application;
use APP\plugins\generic\carinianaPreservation\classes\PreservationXmlBuilder;
use APP\facades\Repo;

class PreservationSubmissionFormTest extends DatabaseTestCase
{
    use CarinianaTestFixtureTrait;
    public const JOURNAL_WITH_LOCKSS_ID = 880022;
    public const JOURNAL_WITHOUT_LOCKSS_ID = 880055;

    private $plugin;
    private $statementFileName = 'responsabilityStatement.pdf';
    private $statementOriginalFileName = 'TermoResponsabilidade.pdf';
    private $journalsById = [];
    private $journalDaoMock;

    protected function getAffectedTables()
    {
        return ['plugin_settings', 'issues', 'issue_settings'];
    }

    public function setUp(): void
    {
        parent::setUp();
        $this->plugin = new CarinianaPreservationPlugin();
        $this->persistTestJournals();
        $this->ensureRouter();
        $this->seedPublishedIssues();
        $this->seedStatementFile();
        $this->plugin->updateSetting(self::JOURNAL_WITHOUT_LOCKSS_ID, 'statementFile', json_encode(['fileName' => 'dummy.pdf']));
    }

    protected function tearDown(): void
    {
        Hook::clear('Mail::send');
        foreach ([self::JOURNAL_WITH_LOCKSS_ID, self::JOURNAL_WITHOUT_LOCKSS_ID] as $jid) {
            $this->plugin->updateSetting($jid, 'statementFile', null);
            $this->plugin->updateSetting($jid, 'lastPreservationTimestamp', null);
            $this->plugin->updateSetting($jid, 'preservedXMLcontent', null);
            $this->plugin->updateSetting($jid, 'preservedXMLmd5', null);
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
        foreach (glob('/tmp/marcacoes_preservacao_*_check.xml') ?: [] as $tmp) {
            @unlink($tmp);
        }
        parent::tearDown();
    }

    private function persistTestJournals(): void
    {
        $with = $this->buildAndPersistJournal([
            'publisherInstitution' => 'PKP',
            'name' => 'Revista Teste',
            'printIssn' => '1234-1234',
            'onlineIssn' => '0101-1010',
            'urlPath' => 'revistateste_' . uniqid(),
            'primaryLocale' => 'pt_BR',
            'acronym' => 'RT',
            'contactEmail' => 'contato@revistateste.org',
            'enableLockss' => true,
        ]);
        $this->journalsById[self::JOURNAL_WITH_LOCKSS_ID] = $with; // map expected id to persisted object

        $without = $this->buildAndPersistJournal([
            'publisherInstitution' => 'PKP',
            'name' => 'Revista Teste LOCKSS',
            'printIssn' => '2222-2222',
            'onlineIssn' => '3333-3333',
            'urlPath' => 'revista-lockss_' . uniqid(),
            'primaryLocale' => 'pt_BR',
            'acronym' => 'RL',
            'contactEmail' => 'contato@rl.org',
            'enableLockss' => false,
        ]);
        $this->journalsById[self::JOURNAL_WITHOUT_LOCKSS_ID] = $without;

        $this->journalDaoMock = new class($this) {
            private $outer;
            public function __construct($outer){$this->outer = $outer;}
            public function getById($id){return $this->outer->journalsById[$id] ?? null;}
        };
    }

    private function ensureRouter(): void
    {
        // Router is available in OJS 3.4 test bootstrap; no action needed.
    }

    private function getJournal(int $id): ?Journal
    {
        return $this->journalsById[$id] ?? null;
    }

    private function seedPublishedIssues(): void
    {
        $this->insertPublishedIssue(self::JOURNAL_WITH_LOCKSS_ID);
        $this->insertPublishedIssue(self::JOURNAL_WITHOUT_LOCKSS_ID);
    }

    private function seedStatementFile(): void
    {
        $this->createStatementFileForFirstPreservation();
    }

    private function insertPublishedIssue(int $journalId): void
    {
        $journal = $this->getJournal($journalId);
        if ($journal) {
            $this->persistIssue($journal, ['year' => 2024, 'datePublished' => '2024-01-01']);
        }
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
        Hook::add('Mail::send', function () {
            return true;
        });

        $form = new PreservationSubmissionForm($this->plugin, self::JOURNAL_WITH_LOCKSS_ID, $this->journalDaoMock);
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
        $form = new PreservationSubmissionForm($this->plugin, self::JOURNAL_WITHOUT_LOCKSS_ID, $this->journalDaoMock);
        $form->setData('notesAndComments', 'Notas');
        $valid = $form->validate();
        $this->assertFalse($valid);
        $this->assertEmpty($this->plugin->getSetting(self::JOURNAL_WITHOUT_LOCKSS_ID, 'lastPreservationTimestamp'));
    }

    public function testValidationBlocksUpdateWhenLockssDisabled(): void
    {
        $this->plugin->updateSetting(self::JOURNAL_WITHOUT_LOCKSS_ID, 'lastPreservationTimestamp', time() - 3600);
        $this->plugin->updateSetting(self::JOURNAL_WITHOUT_LOCKSS_ID, 'preservedXMLcontent', '<xml>anterior</xml>');

        $form = new PreservationSubmissionForm($this->plugin, self::JOURNAL_WITHOUT_LOCKSS_ID, $this->journalDaoMock);

        $valid = $form->validate();
        $this->assertFalse($valid);
        $errors = $form->getErrorsArray();
        $this->assertArrayHasKey('preservationSubmission', $errors);
        $this->assertMatchesRegularExpression('/lockss/i', $errors['preservationSubmission']);
    }

    public function testFirstPreservationMissingStatementFile(): void
    {
        $this->plugin->updateSetting(self::JOURNAL_WITH_LOCKSS_ID, 'statementFile', null);
        $form = new PreservationSubmissionForm($this->plugin, self::JOURNAL_WITH_LOCKSS_ID, $this->journalDaoMock);
        $form->setData('notesAndComments', 'Notas');
        $valid = $form->validate();
        $this->assertFalse($valid);
        $errors = $form->getErrorsArray();
        $this->assertStringContainsString('missingResponsabilityStatement', implode(' ', $errors));
    }

    public function testFirstPreservationHappyPathValidate(): void
    {
        $this->assertEmpty($this->plugin->getSetting(self::JOURNAL_WITH_LOCKSS_ID, 'lastPreservationTimestamp'));
        $this->assertNotEmpty($this->plugin->getSetting(self::JOURNAL_WITH_LOCKSS_ID, 'statementFile'));
        $form = new PreservationSubmissionForm($this->plugin, self::JOURNAL_WITH_LOCKSS_ID, $this->journalDaoMock);
        $form->setData('notesAndComments', 'Notas iniciais');
        $valid = $form->validate();
        $this->assertTrue($valid);
        $this->assertEmpty($form->getErrorsArray());
    }

    public function testUpdateWithNoChangesLockssEnabled(): void
    {
        $this->createBaselineNoChanges(self::JOURNAL_WITH_LOCKSS_ID);
        $form = new PreservationSubmissionForm($this->plugin, self::JOURNAL_WITH_LOCKSS_ID, $this->journalDaoMock);
        $valid = $form->validate();
        $this->assertFalse($valid);
        $this->assertMatchesRegularExpression('/noChanges/i', implode(' ', $form->getErrorsArray()));
    }

    public function testUpdateWithChangesLockssEnabled(): void
    {
        $this->createBaselineNoChanges(self::JOURNAL_WITH_LOCKSS_ID);
        $this->journalsById[self::JOURNAL_WITH_LOCKSS_ID]->setData('publisherInstitution', 'PKP Alterado');
        $form = new PreservationSubmissionForm($this->plugin, self::JOURNAL_WITH_LOCKSS_ID, $this->journalDaoMock);
        $valid = $form->validate();
        $this->assertTrue($valid);
        $errorsText = implode(' ', $form->getErrorsArray());
        $this->assertDoesNotMatchRegularExpression('/noChanges/i', $errorsText);
    }

    public function testMissingRequirementsBlocksFirstPreservation(): void
    {
        $journalIncomplete = $this->buildAndPersistJournal([
            'publisherInstitution' => null,
            'urlPath' => 'incomplete_' . uniqid(),
            'primaryLocale' => 'pt_BR',
            'enableLockss' => true,
        ]);
        $mockDao = new class($journalIncomplete) { private $j; public function __construct($j){$this->j=$j;} public function getById($id){return $this->j;} };

        $this->plugin->updateSetting(self::JOURNAL_WITH_LOCKSS_ID, 'statementFile', json_encode(['fileName' => 'dummy.pdf']));
        $form = new PreservationSubmissionForm($this->plugin, self::JOURNAL_WITH_LOCKSS_ID, $mockDao);
        $form->setData('notesAndComments', 'Notas');
        $valid = $form->validate();
        $this->assertFalse($valid);
        $errors = $form->getErrorsArray();
        $this->assertMatchesRegularExpression('/missingRequirements/i', implode(' ', $errors));
    }

    private function createBaselineNoChanges(int $journalId): void
    {
        $this->plugin->updateSetting($journalId, 'lastPreservationTimestamp', time() - 3600);
    $journal = $this->getJournal($journalId);
    $this->assertNotNull($journal, 'Baseline journal not available');
        $baseUrl = Application::get()->getRequest()->getBaseUrl();
        $builder = new PreservationXmlBuilder($journal, $baseUrl);
        $acronym = $journal->getLocalizedData('acronym', $journal->getPrimaryLocale());
        $tempPath = "/tmp/marcacoes_preservacao_{$acronym}_check.xml";
        if (file_exists($tempPath)) {
            @unlink($tempPath);
        }
        $builder->createPreservationXml($tempPath);
        $xmlContent = file_get_contents($tempPath);
        $this->plugin->updateSetting($journalId, 'preservedXMLcontent', $xmlContent);
        $this->plugin->updateSetting($journalId, 'preservedXMLmd5', md5($xmlContent));
        @unlink($tempPath);
    }
}
