<?php

import('lib.pkp.classes.mail.Mail');
import('plugins.generic.carinianaPreservation.classes.PreservedJournalFactory');
import('plugins.generic.carinianaPreservation.classes.PreservedJournalSpreadsheet');
import('plugins.generic.carinianaPreservation.classes.PreservationXmlBuilder');
import('plugins.generic.carinianaPreservation.CarinianaPreservationPlugin');

define('CARINIANA_NAME', 'Rede Cariniana');
define('CARINIANA_EMAIL', 'cariniana@ibict.br');

class PreservationEmailBuilder {

    public function buildPreservationEmail($journal, $baseUrl, $locale) {
        $email = new Mail();

        $fromName = $journal->getLocalizedData('acronym', $locale);
        $fromEmail = $journal->getData('contactEmail');
        $email->setFrom($fromEmail, $fromName);
        
        $email->addRecipient(CARINIANA_EMAIL, CARINIANA_NAME);
        $email->addCc($fromEmail, $fromName);

        $plugin = new CarinianaPreservationPlugin();
        $extraCopyEmail = $plugin->getSetting($journal->getId(), 'extraCopyEmail');
        if(!empty($extraCopyEmail)) {
            $email->addCc($extraCopyEmail);
        }
        
        $subject = __('plugins.generic.carinianaPreservation.preservationEmail.subject', ['journalAcronym' => $fromName], $locale);
        $body = __('plugins.generic.carinianaPreservation.preservationEmail.body', ['journalAcronym' => $fromName], $locale);
        $email->setSubject($subject);
        $email->setBody($body);

        $spreadsheetFilePath = $this->createJournalSpreadsheet($journal, $baseUrl, $locale);
        $email->addAttachment($spreadsheetFilePath);
        
        $statementData = $this->getResponsabilityStatementData($journal);
        $email->addAttachment($statementData['path'], $statementData['name'], $statementData['type']);

        $xmlFilePath = $this->createXml($journal, $baseUrl, $locale);
        $email->addAttachment($xmlFilePath);

        return $email;
    }

    private function createJournalSpreadsheet($journal, $baseUrl, $locale): string
    {
        $preservedJournalFactory = new PreservedJournalFactory();
        $preservedJournal = $preservedJournalFactory->buildPreservedJournal($journal, $baseUrl, $locale);

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

    private function createXml($journal, $baseUrl, $locale): string
    {
        $issueDao = DAORegistry::getDAO('IssueDAO');
        $issues = $issueDao->getIssues($journal->getId())->toArray();
        $issues = array_reverse($issues);

        $journalAcronym = $journal->getLocalizedData('acronym', $locale);
        $xmlFilePath = "/tmp/marcacoes_preservacao_{$journalAcronym}.xml";
        
        $preservationXmlBuilder = new PreservationXmlBuilder($journal, $issues, $baseUrl, $locale);
        $preservationXmlBuilder->createPreservationXml($xmlFilePath);

        return $xmlFilePath;
    }
}