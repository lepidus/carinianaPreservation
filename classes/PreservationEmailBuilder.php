<?php

namespace APP\plugins\generic\carinianaPreservation\classes;

use APP\plugins\generic\carinianaPreservation\classes\BasePreservationEmailBuilder;
use APP\plugins\generic\carinianaPreservation\classes\PreservedJournalFactory;
use APP\plugins\generic\carinianaPreservation\classes\PreservedJournalSpreadsheet;
use APP\plugins\generic\carinianaPreservation\classes\PreservationXmlStatePersister;
use APP\plugins\generic\carinianaPreservation\CarinianaPreservationPlugin;
use PKP\file\PrivateFileManager;

class PreservationEmailBuilder extends BasePreservationEmailBuilder
{
    public function buildPreservationEmail($journal, $baseUrl, $notesAndComments, $locale)
    {
        $email = $this->buildBaseEmail($journal, $locale);

        $journalAcronym = $journal->getLocalizedData('acronym', $locale);
        $this->setEmailSubjectAndBody($email, $journalAcronym, $locale);

        $spreadsheetFilePath = $this->createJournalSpreadsheet($journal, $baseUrl, $notesAndComments, $locale);
        $this->addAttachment($email, $spreadsheetFilePath);

        $statementData = $this->getResponsabilityStatementData($journal);
        $this->addAttachment($email, $statementData['path'], $statementData['name'], $statementData['type']);

        $xmlFilePath = $this->createXml($journal, $baseUrl);
        $this->addAttachment($email, $xmlFilePath);

        (new PreservationXmlStatePersister())->persist($journal->getId(), $xmlFilePath);

        return $email;
    }

    protected function setEmailSubjectAndBody($email, $journalAcronym, $locale)
    {
        $subject = __('plugins.generic.carinianaPreservation.preservationEmail.subject', ['journalAcronym' => $journalAcronym], $locale);
        $body = __('plugins.generic.carinianaPreservation.preservationEmail.body', ['journalAcronym' => $journalAcronym], $locale);
        $email->subject($subject);
        $email->body($this->formatBodyAsHtml($body));
        $email->setLocale($locale);
        $email->addData(['subject' => $subject, 'body' => $this->formatBodyAsHtml($body)]);
    }

    private function createJournalSpreadsheet($journal, $baseUrl, $notesAndComments, $locale): string
    {
        $preservedJournalFactory = new PreservedJournalFactory();
        $preservedJournal = $preservedJournalFactory->buildPreservedJournal($journal, $baseUrl, $notesAndComments, $locale);

        $journalAcronym = $journal->getLocalizedData('acronym', $locale);
        $spreadsheetFilePath = "/tmp/planilha_preservacao_{$journalAcronym}.xlsx";

        $preservedJournalSpreadsheet = new PreservedJournalSpreadsheet([$preservedJournal]);
        $preservedJournalSpreadsheet->createSpreadsheet($spreadsheetFilePath);

        return $spreadsheetFilePath;
    }

    private function getResponsabilityStatementData($journal): array
    {
        $plugin = new CarinianaPreservationPlugin();
        $statementFileData = json_decode($plugin->getSetting($journal->getId(), 'statementFile'), true);
        $originalName = $statementFileData['originalFileName'];
        $type = $statementFileData['fileType'];
        $fileName = $statementFileData['fileName'];

        import('lib.pkp.classes.file.PrivateFileManager');
        $privateFileManager = new PrivateFileManager();
        $basePath = rtrim($privateFileManager->getBasePath(), '/');
        $statementFilePath = $basePath . '/carinianaPreservation/' . (int)$journal->getId() . '/' . $fileName;
        return [
            'path' => $statementFilePath,
            'name' => $originalName,
            'type' => $type
        ];
    }
}
