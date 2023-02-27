<?php

use PHPUnit\Framework\TestCase;

import('lib.pkp.tests.DatabaseTestCase');
import('classes.journal.Journal');
import('classes.issue.Issue');
import('plugins.generic.carinianaPreservation.classes.PreservationEmailBuilder');

class PreservationEmailBuilderTest extends DatabaseTestCase
{
    private $preservationEmailBuilder;
    private $journal;
    private $journalId = 2;
    private $locale = 'pt_BR';
    private $journalAcronym = 'RBRB';
    private $journalContactEmail = 'contact@rbrb.com.br';
    private $preservationName = 'Preservacao Cariniana';
    private $preservationEmail = 'destino.cariniana@gmail.com';
    private $publisherOrInstitution = 'SciELO';
    private $title = 'SciELO Journal n18';
    private $issn = '1234-1234';
    private $eIssn = '0101-1010';
    private $baseUrl = 'https://scielo-journal-18.com.br/';
    private $journalPath = 'scielojournal18';
    private $firstIssueYear = '2018';
    private $lastIssueYear = '2022';
    private $notesAndComments = 'We are the 18th SciELO journal';

    public function setUp(): void
    {
        parent::setUp();
        $this->createTestJournal();
        $this->preservationEmailBuilder = new PreservationEmailBuilder();
        $this->createTestIssue($this->firstIssueYear);
        $this->createTestIssue($this->lastIssueYear);
    }

    protected function getAffectedTables()
    {
		return ['issues', 'issue_settings'];
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
        $this->journal->setData('description', $this->notesAndComments);
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

    public function testBuildsPreservationEmail(): void
    {
        $email = $this->preservationEmailBuilder->buildPreservationEmail($this->journal, $this->baseUrl, $this->preservationName, $this->preservationEmail, $this->locale);
        
        $expectedFrom = ['name' => $this->journalAcronym, 'email' => $this->journalContactEmail];
        $this->assertEquals($expectedFrom, $email->getData('from'));
        
        $expectedRecipient = ['name' => $this->preservationName, 'email' => $this->preservationEmail];
        $this->assertEquals($expectedRecipient, $email->getData('recipients')[0]);
        
        $expectedSubject = __('plugins.generic.carinianaPreservation.preservationEmail.subject', ['journalAcronym' => $this->journalAcronym], $this->locale);
        $this->assertEquals($expectedSubject, $email->getData('subject'));

        $expectedBody = __('plugins.generic.carinianaPreservation.preservationEmail.body', ['journalAcronym' => $this->journalAcronym], $this->locale);
        $this->assertEquals($expectedBody, $email->getData('body'));

        $expectedFilePath = $expectedFileName = "planilha_preservacao_$this->journalAcronym";
        $xlsxContentType = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
        $expectedAttachment = ['path' => $expectedFilePath, 'filename' => $expectedFileName, 'content-type' => $xlsxContentType];
        $this->assertEquals($expectedAttachment, $email->getData('attachments')[0]);
    }
}