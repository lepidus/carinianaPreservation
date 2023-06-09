<?php

use PHPUnit\Framework\TestCase;

import('lib.pkp.tests.DatabaseTestCase');
import('classes.journal.Journal');
import('classes.issue.Issue');
import('plugins.generic.carinianaPreservation.classes.PreservationEmailBuilder');
import('plugins.generic.carinianaPreservation.CarinianaPreservationPlugin');

class PreservationEmailBuilderTest extends DatabaseTestCase
{
    private $preservationEmailBuilder;
    private $email;
    private $journal;
    private $journalId = 2;
    private $locale = 'pt_BR';
    private $journalAcronym = 'RBRB';
    private $journalContactEmail = 'contact@rbrb.com.br';
    private $extraCopyEmail = 'extra.contact@rbrb.com.br';
    private $publisherOrInstitution = 'SciELO';
    private $title = 'SciELO Journal n18';
    private $issn = '1234-1234';
    private $eIssn = '0101-1010';
    private $baseUrl = 'https://scielo-journal-18.com.br/';
    private $journalPath = 'scielojournal18';
    private $firstIssueYear = '2018';
    private $lastIssueYear = '2022';
    private $notesAndComments = 'We are the 18th SciELO journal';
    private $statementOriginalFileName = 'Termos_responsabilidade_cariniana.pdf';
    private $statementFileName = 'carinianapreservationplugin_responsabilityStatement.pdf';

    const CARINIANA_NAME = 'Rede Cariniana';
    const CARINIANA_EMAIL = 'cariniana@ibict.br';

    public function setUp(): void
    {
        parent::setUp();
        $this->createTestJournal();
        $this->preservationEmailBuilder = new PreservationEmailBuilder();
        $this->createTestIssue($this->firstIssueYear);
        $this->createTestIssue($this->lastIssueYear);
        $this->createStatementFileSetting();
        $this->email = $this->preservationEmailBuilder->buildPreservationEmail($this->journal, $this->baseUrl, self::CARINIANA_NAME, self::CARINIANA_EMAIL, $this->locale);
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
        $this->journal->setData('description', $this->notesAndComments, $this->locale);
        $this->journal->setData('acronym', $this->journalAcronym, $this->locale);
        $this->journal->setData('contactEmail', $this->journalContactEmail);
    }

    private function createTestIssue($issueYear): void
    {
        $issueDatePublished = $issueYear.'-01-01';

        $issue = new Issue();
        $issue->setData('journalId', $this->journalId);
        $issue->setData('datePublished', $issueDatePublished);

        $issueDao = DAORegistry::getDAO('IssueDAO');
        $issueDao->insertObject($issue);
    }

    private function createStatementFileSetting(): void
    {
        $statementFileData = json_encode([
            'originalFileName' => $this->statementOriginalFileName,
            'fileName' => $this->statementFileName,
            'fileType' => 'application/pdf',
        ]);
    
        $plugin = new CarinianaPreservationPlugin();
        $plugin->updateSetting($this->journalId, 'statementFile', $statementFileData);
    }

    public function testBuiltPreservationEmailFrom(): void
    {
        $expectedFrom = ['name' => $this->journalAcronym, 'email' => $this->journalContactEmail];
        $this->assertEquals($expectedFrom, $this->email->getData('from'));
    }

    public function testBuiltPreservationEmailRecipient(): void
    {
        $expectedRecipient = ['name' => self::CARINIANA_NAME, 'email' => self::CARINIANA_EMAIL];
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
        $this->email = $this->preservationEmailBuilder->buildPreservationEmail($this->journal, $this->baseUrl, self::CARINIANA_NAME, self::CARINIANA_EMAIL, $this->locale);
        
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
        $this->assertEquals($expectedAttachment, $this->email->getData('attachments')[0]);
    }

    public function testBuiltPreservationEmailStatement(): void
    {
        $expectedFilePath = "public/journals/{$this->journalId}/{$this->statementFileName}";
        $pdfContentType = 'application/pdf';
        $expectedAttachment = ['path' => $expectedFilePath, 'filename' => $this->statementOriginalFileName, 'content-type' => $pdfContentType];
        $this->assertEquals($expectedAttachment, $this->email->getData('attachments')[1]);
    }

    public function testBuiltPreservationEmailXml(): void
    {
        $expectedFileName = "marcacoes_preservacao_{$this->journalAcronym}.xml";
        $expectedFilePath = "/tmp/$expectedFileName";
        $xmlContentType = 'text/xml';
        $expectedAttachment = ['path' => $expectedFilePath, 'filename' => $expectedFileName, 'content-type' => $xmlContentType];
        $this->assertEquals($expectedAttachment, $this->email->getData('attachments')[2]);
    }
}