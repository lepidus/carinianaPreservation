<?php

namespace APP\plugins\generic\carinianaPreservation\tests;

use APP\plugins\generic\carinianaPreservation\CarinianaPreservationPlugin;
use APP\plugins\generic\carinianaPreservation\classes\PreservationUpdateEmailBuilder;
use PKP\tests\DatabaseTestCase;

class PreservationUpdateEmailBuilderTest extends DatabaseTestCase
{
    use CarinianaTestFixtureTrait;
    private $preservationUpdateEmailBuilder;
    private $email;
    private $journal;
    private const ATTACHMENT_INDEX_XML = 0;
    private $journalId;
    private $locale = 'pt_BR';
    private $journalAcronym = 'RBRU';
    private $journalContactEmail = 'contact@rbru.com.br';
    private $extraCopyEmail = 'extra.contact@rbru.com.br';
    private $publisherOrInstitution = 'PKP';
    private $title = 'PKP Journal n19';
    private $issn = '1234-5678';
    private $eIssn = '0101-1010';
    private $baseUrl = 'https://pkp-journal-19.test/';
    private $journalPath = 'pkpjournal19';
    private $firstIssueYear = '2019';
    private $lastIssueYear = '2023';

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
        $this->preservationUpdateEmailBuilder = new PreservationUpdateEmailBuilder();
        $this->persistIssue($this->journal, ['year' => $this->firstIssueYear]);
        $this->persistIssue($this->journal, ['year' => $this->lastIssueYear]);
        $this->email = $this->preservationUpdateEmailBuilder->buildPreservationUpdateEmail($this->journal, $this->baseUrl, $this->locale);
    }

    protected function getAffectedTables()
    {
        return ['issues', 'issue_settings', 'plugin_settings'];
    }

    protected function tearDown(): void
    {
        $this->cleanupJournal($this->journal);
        parent::tearDown();
    }

    public function testBuiltPreservationUpdateEmailFrom(): void
    {
        $expectedEmail = $this->journalContactEmail;
        $expectedName = $this->journalAcronym;
        $this->assertCount(1, $this->email->from);
        $this->assertEquals($expectedEmail, $this->email->from[0]['address']);
        $this->assertEquals($expectedName, $this->email->from[0]['name']);
    }

    public function testBuiltPreservationUpdateEmailRecipient(): void
    {
        $expectedEmail = CARINIANA_EMAIL;
        $expectedName = CARINIANA_NAME;
        $this->assertCount(1, $this->email->to);
        $this->assertEquals($expectedEmail, $this->email->to[0]['address']);
        $this->assertEquals($expectedName, $this->email->to[0]['name']);
    }

    public function testBuiltPreservationUpdateEmailCarbonCopies(): void
    {
        $this->assertCount(1, $this->email->cc);
        $this->assertEquals($this->journalContactEmail, $this->email->cc[0]['address']);
        $this->assertEquals($this->journalAcronym, $this->email->cc[0]['name']);
    }

    public function testBuiltPreservationUpdateEmailCarbonCopiesWithExtra(): void
    {
        $plugin = new CarinianaPreservationPlugin();
        $plugin->updateSetting($this->journalId, 'extraCopyEmail', $this->extraCopyEmail);
        $this->email = $this->preservationUpdateEmailBuilder->buildPreservationUpdateEmail($this->journal, $this->baseUrl, $this->locale);

        $this->assertCount(2, $this->email->cc);
        $this->assertEquals($this->journalContactEmail, $this->email->cc[0]['address']);
        $this->assertEquals($this->journalAcronym, $this->email->cc[0]['name']);
        $this->assertEquals($this->extraCopyEmail, $this->email->cc[1]['address']);
    }

    public function testBuiltPreservationUpdateEmailSubject(): void
    {
        $expectedSubject = __('plugins.generic.carinianaPreservation.preservationUpdateEmail.subject', ['journalAcronym' => $this->journalAcronym], $this->locale);
        $this->assertEquals($expectedSubject, $this->email->subject);
    }

    public function testBuiltPreservationUpdateEmailBody(): void
    {
        $expectedPlain = __('plugins.generic.carinianaPreservation.preservationUpdateEmail.body', ['journalAcronym' => $this->journalAcronym], $this->locale);
        $expectedHtml = '<div">' . htmlspecialchars($expectedPlain, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8') . '</div>';
        $this->assertEquals($expectedHtml, $this->email->viewData['body']);
    }

    public function testBuiltPreservationUpdateEmailXml(): void
    {
        $expectedFileName = "marcacoes_preservacao_{$this->journalAcronym}.xml";
        $xmlContentType = 'text/xml';

        $attachment = $this->email->attachments[self::ATTACHMENT_INDEX_XML];
        $this->assertEquals($expectedFileName, $attachment['options']['as']);
        $this->assertEquals($xmlContentType, $attachment['options']['mime']);
        $this->assertStringEndsWith($expectedFileName, $attachment['file']);
    }

    public function testPreservationSettingsAreUpdated(): void
    {
        $plugin = new CarinianaPreservationPlugin();
        $lastPreservationTimestamp = $plugin->getSetting($this->journalId, 'lastPreservationTimestamp');
        $preservedXMLmd5 = $plugin->getSetting($this->journalId, 'preservedXMLmd5');

        $this->assertNotEmpty($lastPreservationTimestamp);
        $this->assertNotEmpty($preservedXMLmd5);
    }

    public function testXmlContentIsPersistedOnUpdate(): void
    {
        $plugin = new CarinianaPreservationPlugin();
        $xmlSettingContent = $plugin->getSetting($this->journalId, 'preservedXMLcontent');
        $this->assertNotEmpty($xmlSettingContent, 'Expected persisted XML content in preservedXMLcontent');
        $xmlAttachment = $this->email->attachments[self::ATTACHMENT_INDEX_XML];
        $this->assertFileExists($xmlAttachment['file']);
        $expectedContent = file_get_contents($xmlAttachment['file']);
        $this->assertEquals($expectedContent, $xmlSettingContent, 'Persisted XML content differs from sent XML');
    }

    public function testNoDiffAttachmentWhenNoDataChanges(): void
    {
        foreach ($this->email->attachments as $attachment) {
            $this->assertStringEndsNotWith('.diff', $attachment['options']['as'], 'Diff should not appear when there are no data changes');
        }
    }

    public function testDiffAttachmentPresentAfterDataChange(): void
    {
        $newYear = (string)(((int)$this->lastIssueYear) + 1);
        $this->persistIssue($this->journal, ['year' => $newYear]);

        $this->email = $this->preservationUpdateEmailBuilder->buildPreservationUpdateEmail($this->journal, $this->baseUrl, $this->locale);

        $diffAttachment = null;
        foreach ($this->email->attachments as $attachment) {
            if (str_ends_with($attachment['options']['as'], '.diff')) {
                $diffAttachment = $attachment;
                break;
            }
        }

        $this->assertNotNull($diffAttachment, 'Expected a diff attachment after data change');
        $this->assertFileExists($diffAttachment['file']);
        $diffContent = file_get_contents($diffAttachment['file']);
        $this->assertNotEmpty($diffContent, 'Diff file should not be empty');
        $this->assertStringContainsString($newYear, $diffContent, 'Diff should reference the newly added issue year');
        $this->assertStringContainsString('+', $diffContent, 'Diff should contain additions marked with +');
    }
}
