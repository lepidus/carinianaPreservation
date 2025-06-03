<?php

import('lib.pkp.classes.mail.Mail');
import('plugins.generic.carinianaPreservation.classes.PreservationXmlBuilder');
import('plugins.generic.carinianaPreservation.CarinianaPreservationPlugin');

define('CARINIANA_NAME', 'Rede Cariniana');
define('CARINIANA_EMAIL', 'cariniana@ibict.br');

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
        $journalAcronym = $journal->getLocalizedData('acronym', $journal->getPrimaryLocale());
        $xmlFilePath = "/tmp/marcacoes_preservacao_{$journalAcronym}.xml";

        $preservationXmlBuilder = new PreservationXmlBuilder($journal, $baseUrl);
        $preservationXmlBuilder->createPreservationXml($xmlFilePath);

        return $xmlFilePath;
    }

    protected function updatePreservationSettings($journal, $xmlFilePath)
    {
        $plugin = new CarinianaPreservationPlugin();
        $plugin->updateSetting($journal->getId(), 'lastPreservationTimestamp', Core::getCurrentDate());

        $xmlMd5 = md5_file($xmlFilePath);
        if ($xmlMd5) {
            $plugin->updateSetting($journal->getId(), 'preservedXMLmd5', $xmlMd5);
        }
    }

    abstract protected function setEmailSubjectAndBody($email, $journalAcronym, $locale);
}
