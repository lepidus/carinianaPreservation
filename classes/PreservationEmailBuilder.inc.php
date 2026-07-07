<?php

import('plugins.generic.carinianaPreservation.classes.BasePreservationEmailBuilder');
import('plugins.generic.carinianaPreservation.classes.PreservedJournalFactory');
import('plugins.generic.carinianaPreservation.classes.PreservedJournalSpreadsheet');
import('plugins.generic.carinianaPreservation.classes.PreservationXmlStatePersister');

class PreservationEmailBuilder extends BasePreservationEmailBuilder
{
    public function buildPreservationEmail($journal, $baseUrl, $notesAndComments, $locale)
    {
        $email = $this->buildBaseEmail($journal, $locale);

        $journalAcronym = $journal->getLocalizedData('acronym', $locale);
        $this->setEmailSubjectAndBody($email, $journalAcronym, $locale);

        $spreadsheetFilePath = $this->createJournalSpreadsheet($journal, $baseUrl, $notesAndComments, $locale);
        $email->addAttachment($spreadsheetFilePath, $this->getSpreadsheetAttachmentName($journalAcronym), 'text/csv');

        $statementData = $this->getResponsabilityStatementData($journal);
        $email->addAttachment($statementData['path'], $statementData['name'], $statementData['type']);

        $xmlFilePath = $this->createXml($journal, $baseUrl);
        $email->addAttachment($xmlFilePath, $this->getXmlAttachmentName($journalAcronym), 'text/xml');

        (new PreservationXmlStatePersister())->persist($journal->getId(), $xmlFilePath);

        return $email;
    }

    protected function setEmailSubjectAndBody($email, $journalAcronym, $locale)
    {
        $subject = __('plugins.generic.carinianaPreservation.preservationEmail.subject', ['journalAcronym' => $journalAcronym], $locale);
        $body = __('plugins.generic.carinianaPreservation.preservationEmail.body', ['journalAcronym' => $journalAcronym], $locale);
        $email->setSubject($subject);
        $email->setBody($body);
    }

    private function createJournalSpreadsheet($journal, $baseUrl, $notesAndComments, $locale): string
    {
        $preservedJournalFactory = new PreservedJournalFactory();
        $preservedJournal = $preservedJournalFactory->buildPreservedJournal($journal, $baseUrl, $notesAndComments, $locale);

        $spreadsheetFilePath = $this->createTempPath('cariniana_csv_');

        $preservedJournalSpreadsheet = new PreservedJournalSpreadsheet($preservedJournal);
        $preservedJournalSpreadsheet->createCsv($spreadsheetFilePath);

        return $spreadsheetFilePath;
    }

    private function getResponsabilityStatementData($journal): array
    {
        $plugin = new CarinianaPreservationPlugin();
        $statementFileData = $plugin->getStatementFileData($journal->getId());
        if (!$statementFileData) {
            throw new Exception('Invalid responsibility statement file data.');
        }

        $originalName = $statementFileData['originalFileName'];
        $type = $statementFileData['fileType'];
        $statementFilePath = $plugin->getStatementFilePath($journal->getId(), $statementFileData);
        if (!$statementFilePath) {
            throw new Exception('Responsibility statement file not found.');
        }

        return [
            'path' => $statementFilePath,
            'name' => $originalName,
            'type' => $type
        ];
    }
}
