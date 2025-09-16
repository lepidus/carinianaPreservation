<?php

namespace APP\plugins\generic\carinianaPreservation\tests;

use PKP\tests\DatabaseTestCase;
use APP\journal\Journal;
use APP\issue\Issue;
use APP\plugins\generic\carinianaPreservation\classes\PreservationUpdateEmailBuilder;
use APP\plugins\generic\carinianaPreservation\CarinianaPreservationPlugin;
use APP\facades\Repo;

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
        $this->preservationUpdateEmailBuilder = new PreservationUpdateEmailBuilder();
        $this->persistIssue($this->journal, ['year' => $this->firstIssueYear]);
        $this->persistIssue($this->journal, ['year' => $this->lastIssueYear]);
        $this->email = $this->preservationUpdateEmailBuilder->buildPreservationUpdateEmail($this->journal, $this->baseUrl, $this->locale);
    }

    protected function getAffectedTables()
    {
        return ['issues', 'issue_settings', 'plugin_settings'];
    }

    public function testBuiltPreservationUpdateEmailFrom(): void
    {
        $expectedFrom = ['name' => $this->journalAcronym, 'email' => $this->journalContactEmail];
        $this->assertEquals($expectedFrom, $this->email->getData()['from']);
    }

    public function testBuiltPreservationUpdateEmailRecipient(): void
    {
        $expectedRecipient = ['name' => CARINIANA_NAME, 'email' => CARINIANA_EMAIL];
        $this->assertEquals($expectedRecipient, $this->email->getData()['recipients'][0]);
    }

    public function testBuiltPreservationUpdateEmailCarbonCopies(): void
    {
        $expectedCarbonCopies = [
            ['name' => $this->journalAcronym, 'email' => $this->journalContactEmail]
        ];
        $this->assertEquals($expectedCarbonCopies, $this->email->getData()['ccs']);
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
        $this->assertEquals($expectedCarbonCopies, $this->email->getData()['ccs']);
    }

    public function testBuiltPreservationUpdateEmailSubject(): void
    {
        $expectedSubject = __('plugins.generic.carinianaPreservation.preservationUpdateEmail.subject', ['journalAcronym' => $this->journalAcronym], $this->locale);
        $this->assertEquals($expectedSubject, $this->email->getData()['subject']);
    }

    public function testBuiltPreservationUpdateEmailBody(): void
    {
        $expectedBody = __('plugins.generic.carinianaPreservation.preservationUpdateEmail.body', ['journalAcronym' => $this->journalAcronym], $this->locale);
        $this->assertEquals($expectedBody, $this->email->getData()['body']);
    }

    public function testBuiltPreservationUpdateEmailXml(): void
    {
        $expectedFileName = "marcacoes_preservacao_{$this->journalAcronym}.xml";
        $expectedFilePath = "/tmp/$expectedFileName";
        $xmlContentType = 'text/xml';
        $expectedAttachment = ['path' => $expectedFilePath, 'filename' => $expectedFileName, 'content-type' => $xmlContentType];
        $this->assertEquals($expectedAttachment, $this->email->getData()['attachments'][self::ATTACHMENT_INDEX_XML]);
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
        $xmlAttachment = $this->email->getData()['attachments'][self::ATTACHMENT_INDEX_XML];
        $this->assertFileExists($xmlAttachment['path']);
        $expectedContent = file_get_contents($xmlAttachment['path']);
        $this->assertEquals($expectedContent, $xmlSettingContent, 'Persisted XML content differs from sent XML');
    }

    public function testNoDiffAttachmentWhenNoDataChanges(): void
    {
        $attachments = $this->email->getData()['attachments'];
        foreach ($attachments as $attachment) {
            $this->assertStringEndsNotWith('.diff', $attachment['filename'], 'Diff should not appear when there are no data changes');
        }
    }

    public function testDiffAttachmentPresentAfterDataChange(): void
    {
        $newYear = (string)(((int)$this->lastIssueYear) + 1);
        $this->persistIssue($this->journal, ['year' => $newYear]);

        // Build email again after change
        $this->email = $this->preservationUpdateEmailBuilder->buildPreservationUpdateEmail($this->journal, $this->baseUrl, $this->locale);

        $attachments = $this->email->getData()['attachments'];
        $diffAttachment = null;
        foreach ($attachments as $attachment) {
            if (substr($attachment['filename'], -5) === '.diff') {
                $diffAttachment = $attachment;
                break;
            }
        }

        $this->assertNotNull($diffAttachment, 'Expected a diff attachment after data change');
        $this->assertFileExists($diffAttachment['path']);
        $diffContent = file_get_contents($diffAttachment['path']);
        $this->assertNotEmpty($diffContent, 'Diff file should not be empty');
        $this->assertStringContainsString($newYear, $diffContent, 'Diff should reference the newly added issue year');
        $this->assertStringContainsString('+', $diffContent, 'Diff should contain additions marked with +');
    }
}
