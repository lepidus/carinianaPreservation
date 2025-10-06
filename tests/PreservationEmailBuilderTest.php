<?php

namespace APP\plugins\generic\carinianaPreservation\tests;

use APP\plugins\generic\carinianaPreservation\CarinianaPreservationPlugin;
use APP\plugins\generic\carinianaPreservation\classes\PreservationEmailBuilder;
use PKP\tests\PKPTestCase;

class PreservationEmailBuilderTest extends PKPTestCase
{
    use CarinianaTestFixtureTrait;
    private $preservationEmailBuilder;
    private $email;
    private $journal;
    private const ATTACHMENT_INDEX_SPREADSHEET = 0;
    private const ATTACHMENT_INDEX_STATEMENT = 1;
    private const ATTACHMENT_INDEX_XML = 2;
    private $journalId;
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
        $this->journal = $this->buildAndPersistJournal([
            'publisherInstitution' => $this->publisherOrInstitution,
            'name' => $this->title,
            'printIssn' => $this->issn,
            'onlineIssn' => $this->eIssn,
            'urlPath' => $this->journalPath . '_' . uniqid(),
            'primaryLocale' => $this->locale,
            'acronym' => $this->journalAcronym,
            'contactEmail' => $this->journalContactEmail,
        ]);
        $this->journalId = $this->journal->getId();
        $this->mockRequestForJournal($this->journal);
        $this->preservationEmailBuilder = new PreservationEmailBuilder();
        $this->persistIssue($this->journal, ['year' => $this->firstIssueYear]);
        $this->persistIssue($this->journal, ['year' => $this->lastIssueYear]);
        $this->createStatementFileWithSettings(
            $this->journalId,
            $this->statementFileName,
            $this->statementOriginalFileName
        );
        $this->email = $this->preservationEmailBuilder->buildPreservationEmail($this->journal, $this->baseUrl, $this->notesAndComments, $this->locale);
    }

    protected function tearDown(): void
    {
        $this->cleanupStatementDir($this->journalId, $this->statementFileName);
        $this->cleanupJournal($this->journal);
        parent::tearDown();
    }

    public function testBuiltPreservationEmailFrom(): void
    {
        $expectedEmail = $this->journalContactEmail;
        $expectedName = $this->journalAcronym;
        $this->assertCount(1, $this->email->from);
        $this->assertEquals($expectedEmail, $this->email->from[0]['address']);
        $this->assertEquals($expectedName, $this->email->from[0]['name']);
    }

    public function testBuiltPreservationEmailRecipient(): void
    {
        $expectedEmail = CARINIANA_EMAIL;
        $expectedName = CARINIANA_NAME;
        $this->assertCount(1, $this->email->to);
        $this->assertEquals($expectedEmail, $this->email->to[0]['address']);
        $this->assertEquals($expectedName, $this->email->to[0]['name']);
    }

    public function testBuiltPreservationEmailCarbonCopies(): void
    {
        $this->assertCount(1, $this->email->cc);
        $this->assertEquals($this->journalContactEmail, $this->email->cc[0]['address']);
        $this->assertEquals($this->journalAcronym, $this->email->cc[0]['name']);
    }

    public function testBuiltPreservationEmailCarbonCopiesWithExtra(): void
    {
        $plugin = new CarinianaPreservationPlugin();
        $plugin->updateSetting($this->journalId, 'extraCopyEmail', $this->extraCopyEmail);
        $this->email = $this->preservationEmailBuilder->buildPreservationEmail($this->journal, $this->baseUrl, $this->notesAndComments, $this->locale);

        $this->assertCount(2, $this->email->cc);
        $this->assertEquals($this->journalContactEmail, $this->email->cc[0]['address']);
        $this->assertEquals($this->journalAcronym, $this->email->cc[0]['name']);
        $this->assertEquals($this->extraCopyEmail, $this->email->cc[1]['address']);
    }

    public function testBuiltPreservationEmailSubject(): void
    {
        $expectedSubject = __('plugins.generic.carinianaPreservation.preservationEmail.subject', ['journalAcronym' => $this->journalAcronym], $this->locale);
        $this->assertEquals($expectedSubject, $this->email->subject);
    }

    public function testBuiltPreservationEmailBody(): void
    {
        $expectedPlain = __('plugins.generic.carinianaPreservation.preservationEmail.body', ['journalAcronym' => $this->journalAcronym], $this->locale);
        $expectedHtml = '<div style="white-space:pre-line">' . htmlspecialchars($expectedPlain, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8') . '</div>';
        $this->assertEquals($expectedHtml, $this->email->viewData['body']);
    }

    public function testBuiltPreservationEmailSpreadsheet(): void
    {
        $expectedFileName = "planilha_preservacao_{$this->journalAcronym}.xlsx";
        $xlsxContentType = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';

        $attachment = $this->email->attachments[self::ATTACHMENT_INDEX_SPREADSHEET];
        $this->assertEquals($expectedFileName, $attachment['options']['as']);
        $this->assertEquals($xlsxContentType, $attachment['options']['mime']);
        $this->assertStringEndsWith($expectedFileName, $attachment['file']);
    }

    public function testBuiltPreservationEmailStatement(): void
    {
        $attachment = $this->email->attachments[self::ATTACHMENT_INDEX_STATEMENT];
        $this->assertEquals($this->statementOriginalFileName, $attachment['options']['as']);
        $this->assertEquals('application/pdf', $attachment['options']['mime']);
        $this->assertFalse(str_starts_with($attachment['file'], 'public/'));
        $expectedDirPrefix = 'files/carinianaPreservation/' . $this->journalId . '/';
        $this->assertTrue(str_starts_with($attachment['file'], $expectedDirPrefix));
        $fileName = basename($attachment['file']);
        $this->assertEquals($this->statementFileName, $fileName);
        $this->assertFileExists($attachment['file']);
    }

    public function testBuiltPreservationEmailXml(): void
    {
        $expectedFileName = "marcacoes_preservacao_{$this->journalAcronym}.xml";
        $xmlContentType = 'text/xml';

        $attachment = $this->email->attachments[self::ATTACHMENT_INDEX_XML];
        $this->assertEquals($expectedFileName, $attachment['options']['as']);
        $this->assertEquals($xmlContentType, $attachment['options']['mime']);
        $this->assertStringEndsWith($expectedFileName, $attachment['file']);
    }

    public function testXmlContentIsPersistedOnFirstPreservation(): void
    {
        $plugin = new CarinianaPreservationPlugin();
        $xmlSettingContent = $plugin->getSetting($this->journalId, 'preservedXMLcontent');
        $this->assertNotEmpty($xmlSettingContent, 'Expected persisted XML content in preservedXMLcontent');
        $xmlAttachment = $this->email->attachments[self::ATTACHMENT_INDEX_XML];
        $this->assertFileExists($xmlAttachment['file']);
        $expectedContent = file_get_contents($xmlAttachment['file']);
        $this->assertEquals($expectedContent, $xmlSettingContent, 'Persisted XML content differs from sent XML');
    }

    public function testNoDiffAttachmentOnFirstPreservation(): void
    {
        $diffFound = false;
        foreach ($this->email->attachments as $attachment) {
            if (str_ends_with($attachment['options']['as'], '.diff')) {
                $diffFound = true;
                break;
            }
        }
        $this->assertFalse($diffFound, 'Diff attachment should not be present on first preservation email');
    }
}
