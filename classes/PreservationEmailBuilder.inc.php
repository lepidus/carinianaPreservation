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
        $email->addAttachment($spreadsheetFilePath);

        $statementData = $this->getResponsabilityStatementData($journal);
        $email->addAttachment($statementData['path'], $statementData['name'], $statementData['type']);

        $xmlFilePath = $this->createXml($journal, $baseUrl);
        $email->addAttachment($xmlFilePath);

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
        $privateBase = rtrim($privateFileManager->getBasePath(), '/');
        $privatePath = $privateBase . '/carinianaPreservation/' . (int)$journal->getId() . '/' . $fileName;

        $statementFilePath = $privatePath;
        if (!is_readable($privatePath)) {
            import('classes.file.PublicFileManager');
            $publicFileManager = new PublicFileManager();
            $publicFilesPath = $publicFileManager->getContextFilesPath($journal->getId());
            $publicCandidate = $publicFilesPath . '/' . $fileName;
            if (is_readable($publicCandidate)) {
                $statementFilePath = $publicCandidate;
            }
        }

        return [
            'path' => $statementFilePath,
            'name' => $originalName,
            'type' => $type
        ];
    }
}
