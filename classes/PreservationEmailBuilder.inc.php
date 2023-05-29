<?php

import('lib.pkp.classes.mail.Mail');
import('plugins.generic.carinianaPreservation.classes.PreservedJournalFactory');
import('plugins.generic.carinianaPreservation.classes.PreservedJournalSpreadsheet');
import('plugins.generic.carinianaPreservation.CarinianaPreservationPlugin');

class PreservationEmailBuilder {

    public function buildPreservationEmail($journal, $baseUrl, $preservationName, $preservationEmail, $locale) {
        $email = new Mail();

        $fromName = $journal->getLocalizedData('acronym', $locale);
        $fromEmail = $journal->getData('contactEmail');
        $email->setFrom($fromEmail, $fromName);
        
        $email->setRecipients([
            [
                'name' => $preservationName,
                'email' => $preservationEmail,
            ],
        ]);
        
        $subject = __('plugins.generic.carinianaPreservation.preservationEmail.subject', ['journalAcronym' => $fromName], $locale);
        $body = __('plugins.generic.carinianaPreservation.preservationEmail.body', ['journalAcronym' => $fromName], $locale);
        $email->setSubject($subject);
        $email->setBody($body);

        $spreadsheetFilePath = $this->createJournalSpreadsheet($journal, $baseUrl, $locale);
        $email->addAttachment($spreadsheetFilePath);
        
        $statementData = $this->getResponsabilityStatementData($journal);
        $email->addAttachment($statementData['path'], $statementData['name'], $statementData['type']);

        return $email;
    }

    private function createJournalSpreadsheet($journal, $baseUrl, $locale): string
    {
        $preservedJournalFactory = new PreservedJournalFactory();
        $preservedJournal = $preservedJournalFactory->buildPreservedJournal($journal, $baseUrl, $locale);

        $journalAcronym = $journal->getLocalizedData('acronym', $locale);
        $spreadsheetFilePath = "/tmp/planilha_preservacao_$journalAcronym";

        $preservedJournalSpreadsheet = new PreservedJournalSpreadsheet([$preservedJournal]);
        $preservedJournalSpreadsheet->createSpreadsheet($spreadsheetFilePath);

        return $spreadsheetFilePath;
    }

    private function getResponsabilityStatementData($journal): array
    {
        $plugin = new CarinianaPreservationPlugin();
        $statementFileData = json_decode($plugin->getSetting($journal->getId(), 'statementFile'), true);
        
        import('classes.file.PublicFileManager');
		$publicFileManager = new PublicFileManager();
        $publicFilesPath = $publicFileManager->getContextFilesPath($journal->getId());
        $statementFilePath = $publicFilesPath . '/' . $statementFileData['fileName'];

        return [
            'path' => $statementFilePath,
            'name' => $statementFileData['originalFileName'],
            'type' => $statementFileData['fileType']
        ];
    }

}