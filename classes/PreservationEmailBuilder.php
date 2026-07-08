<?php

namespace APP\plugins\generic\carinianaPreservation\classes;

use APP\plugins\generic\carinianaPreservation\CarinianaPreservationPlugin;

class PreservationEmailBuilder extends BasePreservationEmailBuilder
{
    public function buildPreservationEmail($journal, $baseUrl, $notesAndComments, $locale)
    {
        $email = $this->buildBaseEmail($journal, $locale);

        $journalAcronym = $journal->getLocalizedData('acronym', $locale);
        $this->setEmailSubjectAndBody($email, $journalAcronym, $locale);

        $spreadsheetFilePath = $this->createJournalSpreadsheet($journal, $baseUrl, $notesAndComments, $locale);
        $this->addAttachment($email, $spreadsheetFilePath, $this->getSpreadsheetAttachmentName($journalAcronym), 'text/csv');

        $statementData = $this->getResponsabilityStatementData($journal);
        $this->addAttachment($email, $statementData['path'], $statementData['name'], $statementData['type']);

        $xmlFilePath = $this->createXml($journal, $baseUrl);
        $this->addAttachment($email, $xmlFilePath, $this->getXmlAttachmentName($journalAcronym), 'text/xml');

        (new PreservationXmlStatePersister())->persist($journal->getId(), $xmlFilePath);

        return $email;
    }

    protected function setEmailSubjectAndBody($email, $journalAcronym, $locale)
    {
        $email->setData($locale);
        $subject = __('plugins.generic.carinianaPreservation.preservationEmail.subject', ['journalAcronym' => $journalAcronym], $locale);
        $body = __('plugins.generic.carinianaPreservation.preservationEmail.body', ['journalAcronym' => $journalAcronym], $locale);
        $email->subject($subject);
        $email->body($this->formatBodyAsHtml($body));
        $email->addData(['subject' => $subject, 'body' => $this->formatBodyAsHtml($body)]);
    }

    private function createJournalSpreadsheet($journal, $baseUrl, $notesAndComments, $locale): string
    {
        $preservedJournalFactory = new PreservedJournalFactory();
        $preservedJournal = $preservedJournalFactory->buildPreservedJournal($journal, $baseUrl, $notesAndComments, $locale);

        $spreadsheetFilePath = $this->createTempPath('cariniana_csv_');

        $preservedJournalSpreadsheet = new PreservedJournalSpreadsheet($preservedJournal, $locale);
        $preservedJournalSpreadsheet->createSpreadsheet($spreadsheetFilePath);

        return $spreadsheetFilePath;
    }

    private function getResponsabilityStatementData($journal): array
    {
        $plugin = new CarinianaPreservationPlugin();
        $statementFileData = $plugin->getStatementFileData($journal->getId());
        if (!$statementFileData) {
            throw new \Exception('Invalid responsibility statement file data.');
        }

        $originalName = $statementFileData['originalFileName'];
        $type = $statementFileData['fileType'];
        $statementFilePath = $plugin->getStatementFilePath($journal->getId(), $statementFileData);
        if (!$statementFilePath) {
            throw new \Exception('Responsibility statement file not found.');
        }

        return [
            'path' => $statementFilePath,
            'name' => $originalName,
            'type' => $type
        ];
    }
}
