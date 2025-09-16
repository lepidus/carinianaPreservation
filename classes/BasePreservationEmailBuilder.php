<?php

namespace APP\plugins\generic\carinianaPreservation\classes;

use PKP\mail\Mailable;
use PKP\config\Config;
use APP\plugins\generic\carinianaPreservation\CarinianaPreservationPlugin;
use APP\plugins\generic\carinianaPreservation\classes\PreservationXmlBuilder;

define('CARINIANA_NAME', 'Rede Cariniana');
define('CARINIANA_EMAIL', 'cariniana-periodicos@ibict.br');

abstract class BasePreservationEmailBuilder
{
    protected function buildBaseEmail($journal, $locale)
    {
        $email = new Mailable();

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

    abstract protected function setEmailSubjectAndBody($email, $journalAcronym, $locale);
}
