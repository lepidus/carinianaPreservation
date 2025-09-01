<?php

use PHPUnit\Framework\TestCase;

import('lib.pkp.tests.DatabaseTestCase');
import('classes.journal.Journal');
import('classes.issue.Issue');
import('plugins.generic.carinianaPreservation.classes.PreservationEmailBuilder');
import('plugins.generic.carinianaPreservation.CarinianaPreservationPlugin');
import('lib.pkp.classes.file.PrivateFileManager');

class PreservationEmailBuilderTest extends DatabaseTestCase
{
    private $preservationEmailBuilder;
    private $email;
    private $journal;
    private const ATTACHMENT_INDEX_SPREADSHEET = 0;
    private const ATTACHMENT_INDEX_STATEMENT = 1;
    private const ATTACHMENT_INDEX_XML = 2;
    private $journalId = 9999;
    private $locale = 'pt_BR';
    private $journalAcronym = 'RBRB';
    private $journalContactEmail = 'contact@rbrb.com.br';
    private $extraCopyEmail = 'extra.contact@rbrb.com.br';
    private $publisherOrInstitution = 'PKP';
    private $title = 'PKP Journal n18';
    private $issn = '1234-1234';
    private $eIssn = '0101-1010';
    private $baseUrl = 'https://pkp-journal-18.test/';
    private $journalPath = 'pkpjournal18';
    private $firstIssueYear = '2018';
    private $lastIssueYear = '2022';
    private $notesAndComments = 'We are the 18th PKP journal';
    private $statementOriginalFileName = 'Termos_responsabilidade_cariniana.pdf';
    private $statementFileName = 'responsabilityStatement.pdf';

    public function setUp(): void
    {
        parent::setUp();
        $this->createTestJournal();
        $this->preservationEmailBuilder = new PreservationEmailBuilder();
        $this->createTestIssue($this->firstIssueYear);
        $this->createTestIssue($this->lastIssueYear);
        $this->createStatementFileSetting();
        $this->email = $this->preservationEmailBuilder->buildPreservationEmail($this->journal, $this->baseUrl, $this->notesAndComments, $this->locale);
    }

    protected function getAffectedTables()
    {
        return ['issues', 'issue_settings', 'plugin_settings'];
    }

    private function createTestJournal(): void
    {
        $this->journal = new Journal();
        $this->journal->setId($this->journalId);
        $this->journal->setData('publisherInstitution', $this->publisherOrInstitution);
        $this->journal->setData('name', $this->title, $this->locale);
        $this->journal->setData('printIssn', $this->issn);
        $this->journal->setData('onlineIssn', $this->eIssn);
        $this->journal->setData('urlPath', $this->journalPath);
        $this->journal->setData('acronym', $this->journalAcronym, $this->locale);
        $this->journal->setData('contactEmail', $this->journalContactEmail);
    }

    private function createTestIssue($issueYear): void
    {
        $issueDatePublished = $issueYear.'-01-01';

        $issue = new Issue();
        $issue->setData('journalId', $this->journalId);
        $issue->setData('datePublished', $issueDatePublished);
        $issue->setData('year', $issueYear);
        $issue->setData('published', 1);

        $issueDao = DAORegistry::getDAO('IssueDAO');
        $issueDao->insertObject($issue);
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

    private function createStatementFileSetting(): void
    {
        $plugin = new CarinianaPreservationPlugin();
        $fileMgr = new PrivateFileManager();
        $base = rtrim($fileMgr->getBasePath(), '/');
        $dir = $base . '/carinianaPreservation/' . $this->journalId;
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        $generatedName = $this->statementFileName;
        $fullPath = $dir . '/' . $generatedName;
        file_put_contents($fullPath, 'PDF');
        $statementFileData = json_encode([
            'originalFileName' => $this->statementOriginalFileName,
            'fileName' => $generatedName,
            'fileType' => 'application/pdf',
        ]);
        $plugin->updateSetting($this->journalId, 'statementFile', $statementFileData);
    }

    public function testBuiltPreservationEmailFrom(): void
    {
        $expectedFrom = ['name' => $this->journalAcronym, 'email' => $this->journalContactEmail];
        $this->assertEquals($expectedFrom, $this->email->getData('from'));
    }

    public function testBuiltPreservationEmailRecipient(): void
    {
        $expectedRecipient = ['name' => CARINIANA_NAME, 'email' => CARINIANA_EMAIL];
        $this->assertEquals($expectedRecipient, $this->email->getData('recipients')[0]);
    }

    public function testBuiltPreservationEmailCarbonCopies(): void
    {
        $expectedCarbonCopies = [
            ['name' => $this->journalAcronym, 'email' => $this->journalContactEmail]
        ];
        $this->assertEquals($expectedCarbonCopies, $this->email->getData('ccs'));
    }

    public function testBuiltPreservationEmailCarbonCopiesWithExtra(): void
    {
        $plugin = new CarinianaPreservationPlugin();
        $plugin->updateSetting($this->journalId, 'extraCopyEmail', $this->extraCopyEmail);
        $this->email = $this->preservationEmailBuilder->buildPreservationEmail($this->journal, $this->baseUrl, $this->notesAndComments, $this->locale);

        $expectedCarbonCopies = [
            ['name' => $this->journalAcronym, 'email' => $this->journalContactEmail],
            ['name' => '', 'email' => $this->extraCopyEmail]
        ];
        $this->assertEquals($expectedCarbonCopies, $this->email->getData('ccs'));
    }

    public function testBuiltPreservationEmailSubject(): void
    {
        $expectedSubject = __('plugins.generic.carinianaPreservation.preservationEmail.subject', ['journalAcronym' => $this->journalAcronym], $this->locale);
        $this->assertEquals($expectedSubject, $this->email->getData('subject'));
    }

    public function testBuiltPreservationEmailBody(): void
    {
        $expectedBody = __('plugins.generic.carinianaPreservation.preservationEmail.body', ['journalAcronym' => $this->journalAcronym], $this->locale);
        $this->assertEquals($expectedBody, $this->email->getData('body'));
    }

    public function testBuiltPreservationEmailSpreadsheet(): void
    {
        $expectedFileName = "planilha_preservacao_{$this->journalAcronym}.xlsx";
        $expectedFilePath = "/tmp/$expectedFileName";
        $xlsxContentType = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
        $expectedAttachment = ['path' => $expectedFilePath, 'filename' => $expectedFileName, 'content-type' => $xlsxContentType];
        $this->assertEquals($expectedAttachment, $this->email->getData('attachments')[self::ATTACHMENT_INDEX_SPREADSHEET]);
    }

    public function testBuiltPreservationEmailStatement(): void
    {
        $attachment = $this->email->getData('attachments')[self::ATTACHMENT_INDEX_STATEMENT];
        $this->assertEquals($this->statementOriginalFileName, $attachment['filename']);
        $this->assertEquals('application/pdf', $attachment['content-type']);
        $this->assertFalse(str_starts_with($attachment['path'], 'public/'));
        $expectedDirPrefix = 'files/carinianaPreservation/' . $this->journalId . '/';
        $this->assertTrue(str_starts_with($attachment['path'], $expectedDirPrefix));
        $fileName = basename($attachment['path']);
        $this->assertEquals($this->statementFileName, $fileName);
        $this->assertFileExists($attachment['path']);
    }

    public function testBuiltPreservationEmailXml(): void
    {
        $expectedFileName = "marcacoes_preservacao_{$this->journalAcronym}.xml";
        $expectedFilePath = "/tmp/$expectedFileName";
        $xmlContentType = 'text/xml';
        $expectedAttachment = ['path' => $expectedFilePath, 'filename' => $expectedFileName, 'content-type' => $xmlContentType];
        $this->assertEquals($expectedAttachment, $this->email->getData('attachments')[self::ATTACHMENT_INDEX_XML]);
    }

    public function testXmlContentIsPersistedOnFirstPreservation(): void
    {
        $plugin = new CarinianaPreservationPlugin();
        $xmlSettingContent = $plugin->getSetting($this->journalId, 'preservedXMLcontent');
        $this->assertNotEmpty($xmlSettingContent, 'Expected persisted XML content in preservedXMLcontent');
        $xmlAttachment = $this->email->getData('attachments')[self::ATTACHMENT_INDEX_XML];
        $this->assertFileExists($xmlAttachment['path']);
        $expectedContent = file_get_contents($xmlAttachment['path']);
        $this->assertEquals($expectedContent, $xmlSettingContent, 'Persisted XML content differs from sent XML');
    }

    public function testNoDiffAttachmentOnFirstPreservation(): void
    {
        $attachments = $this->email->getData('attachments');
        $diffFound = false;
        foreach ($attachments as $attachment) {
            if (substr($attachment['filename'], -5) === '.diff') {
                $diffFound = true;
                break;
            }
        }
        $this->assertFalse($diffFound, 'Diff attachment should not be present on first preservation email');
    }
}
