<?php

import('lib.pkp.classes.mail.Mail');
import('plugins.generic.carinianaPreservation.classes.PreservationXmlBuilder');
import('plugins.generic.carinianaPreservation.CarinianaPreservationPlugin');
import('plugins.generic.carinianaPreservation.classes.PreservationXmlStatePersister');

define('CARINIANA_NAME', 'Rede Cariniana');
define('CARINIANA_EMAIL', 'cariniana-periodicos@ibict.br');

abstract class BasePreservationEmailBuilder
{
    protected function buildBaseEmail($journal, $locale)
    {
        $email = new Mail();

        $fromName = $journal->getLocalizedData('acronym', $locale);
        $fromEmail = $journal->getData('contactEmail');
        $email->setFrom($fromEmail, $fromName);

        if (Config::getVar('carinianapreservation', 'email_for_tests')) {
            $email->addRecipient(
                Config::getVar('carinianapreservation', 'email_for_tests'),
                $journal->getData('contactName')
            );
        } else {
            $email->addRecipient(CARINIANA_EMAIL, CARINIANA_NAME);
            $email->addCc($fromEmail, $fromName);
        }

        $plugin = new CarinianaPreservationPlugin();
        $extraCopyEmail = $plugin->getSetting($journal->getId(), 'extraCopyEmail');
        if (!empty($extraCopyEmail)) {
            $email->addCc($extraCopyEmail);
        }

        return $email;
    }

    protected function createXml($journal, $baseUrl): string
    {
        $xmlFilePath = $this->createTempPath('cariniana_xml_');

        $preservationXmlBuilder = new PreservationXmlBuilder($journal, $baseUrl);
        $preservationXmlBuilder->createPreservationXml($xmlFilePath);

        return $xmlFilePath;
    }

    protected function getXmlAttachmentName($journalAcronym): string
    {
        return 'marcacoes_preservacao_' . $this->sanitizeAttachmentNamePart($journalAcronym) . '.xml';
    }

    protected function getSpreadsheetAttachmentName($journalAcronym): string
    {
        return 'planilha_preservacao_' . $this->sanitizeAttachmentNamePart($journalAcronym) . '.csv';
    }

    protected function getDiffAttachmentName($journalAcronym, $timestamp): string
    {
        return 'diff_preservacao_' . $this->sanitizeAttachmentNamePart($journalAcronym) . '_' . $timestamp . '.diff';
    }

    protected function createTempPath(string $prefix): string
    {
        $path = tempnam(sys_get_temp_dir(), $prefix);
        if (!$path) {
            throw new Exception('Unable to create temporary preservation file.');
        }
        return $path;
    }

    private function sanitizeAttachmentNamePart($value): string
    {
        $value = preg_replace('/[^A-Za-z0-9_.-]+/', '_', (string)$value);
        $value = trim($value, '._-');
        return $value !== '' ? $value : 'journal';
    }

    abstract protected function setEmailSubjectAndBody($email, $journalAcronym, $locale);
}
