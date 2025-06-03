<?php

use PHPUnit\Framework\TestCase;

import('lib.pkp.tests.DatabaseTestCase');
import('classes.journal.Journal');
import('classes.issue.Issue');
import('plugins.generic.carinianaPreservation.classes.PreservationUpdateEmailBuilder');
import('plugins.generic.carinianaPreservation.CarinianaPreservationPlugin');

class PreservationUpdateEmailBuilderTest extends DatabaseTestCase
{
    private $preservationUpdateEmailBuilder;
    private $email;
    private $journal;
    private $journalId = 3;
    private $locale = 'pt_BR';
    private $journalAcronym = 'RBRU';
    private $journalContactEmail = 'contact@rbru.com.br';
    private $extraCopyEmail = 'extra.contact@rbru.com.br';
    private $publisherOrInstitution = 'SciELO';
    private $title = 'SciELO Journal n19';
    private $issn = '1234-5678';
    private $eIssn = '0101-1010';
    private $baseUrl = 'https://scielo-journal-19.com.br/';
    private $journalPath = 'scielojournal19';
    private $firstIssueYear = '2019';
    private $lastIssueYear = '2023';

    public function setUp(): void
    {
        parent::setUp();
        $this->createTestJournal();
        $this->preservationUpdateEmailBuilder = new PreservationUpdateEmailBuilder();
        $this->createTestIssue($this->firstIssueYear);
        $this->createTestIssue($this->lastIssueYear);
        $this->email = $this->preservationUpdateEmailBuilder->buildPreservationUpdateEmail($this->journal, $this->baseUrl, $this->locale);
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
        $issue->setData('year', $issueYear);
        $issue->setData('journalId', $this->journalId);
        $issue->setData('datePublished', $issueDatePublished);

        $issueDao = DAORegistry::getDAO('IssueDAO'); /* @var $issueDao IssueDAO */
        $issueDao->insertObject($issue);
    }

    public function testBuiltPreservationUpdateEmailFrom(): void
    {
        $expectedFrom = ['name' => $this->journalAcronym, 'email' => $this->journalContactEmail];
        $this->assertEquals($expectedFrom, $this->email->getData('from'));
    }

    public function testBuiltPreservationUpdateEmailRecipient(): void
    {
        $expectedRecipient = ['name' => CARINIANA_NAME, 'email' => CARINIANA_EMAIL];
        $this->assertEquals($expectedRecipient, $this->email->getData('recipients')[0]);
    }

    public function testBuiltPreservationUpdateEmailCarbonCopies(): void
    {
        $expectedCarbonCopies = [
            ['name' => $this->journalAcronym, 'email' => $this->journalContactEmail]
        ];
        $this->assertEquals($expectedCarbonCopies, $this->email->getData('ccs'));
    }

    public function testBuiltPreservationUpdateEmailCarbonCopiesWithExtra(): void
    {
        $plugin = new CarinianaPreservationPlugin();
        $plugin->updateSetting($this->journalId, 'extraCopyEmail', $this->extraCopyEmail);
        $this->email = $this->preservationUpdateEmailBuilder->buildPreservationUpdateEmail($this->journal, $this->baseUrl, $this->locale);

        $expectedCarbonCopies = [
            ['name' => $this->journalAcronym, 'email' => $this->journalContactEmail],
            ['name' => '', 'email' => $this->extraCopyEmail]
        ];
        $this->assertEquals($expectedCarbonCopies, $this->email->getData('ccs'));
    }

    public function testBuiltPreservationUpdateEmailSubject(): void
    {
        $expectedSubject = __('plugins.generic.carinianaPreservation.preservationUpdateEmail.subject', ['journalAcronym' => $this->journalAcronym], $this->locale);
        $this->assertEquals($expectedSubject, $this->email->getData('subject'));
    }

    public function testBuiltPreservationUpdateEmailBody(): void
    {
        $expectedBody = __('plugins.generic.carinianaPreservation.preservationUpdateEmail.body', ['journalAcronym' => $this->journalAcronym], $this->locale);
        $this->assertEquals($expectedBody, $this->email->getData('body'));
    }

    public function testBuiltPreservationUpdateEmailXml(): void
    {
        $expectedFileName = "marcacoes_preservacao_{$this->journalAcronym}.xml";
        $expectedFilePath = "/tmp/$expectedFileName";
        $xmlContentType = 'text/xml';
        $expectedAttachment = ['path' => $expectedFilePath, 'filename' => $expectedFileName, 'content-type' => $xmlContentType];
        $this->assertEquals($expectedAttachment, $this->email->getData('attachments')[0]);
    }

    public function testPreservationSettingsAreUpdated(): void
    {
        $plugin = new CarinianaPreservationPlugin();
        $lastPreservationTimestamp = $plugin->getSetting($this->journalId, 'lastPreservationTimestamp');
        $preservedXMLmd5 = $plugin->getSetting($this->journalId, 'preservedXMLmd5');

        $this->assertNotEmpty($lastPreservationTimestamp);
        $this->assertNotEmpty($preservedXMLmd5);
    }
}
