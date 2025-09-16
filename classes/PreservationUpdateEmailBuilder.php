<?php

namespace APP\plugins\generic\carinianaPreservation\classes;

use APP\plugins\generic\carinianaPreservation\CarinianaPreservationPlugin;
use APP\plugins\generic\carinianaPreservation\classes\BasePreservationEmailBuilder;
use APP\plugins\generic\carinianaPreservation\classes\PreservationXmlStatePersister;
use APP\plugins\generic\carinianaPreservation\classes\XmlDiffGenerator;

class PreservationUpdateEmailBuilder extends BasePreservationEmailBuilder
{
    public function buildPreservationUpdateEmail($journal, $baseUrl, $locale)
    {
        $email = $this->buildBaseEmail($journal, $locale);

        $journalAcronym = $journal->getLocalizedData('acronym', $locale);
        $this->setEmailSubjectAndBody($email, $journalAcronym, $locale);

        $plugin = new CarinianaPreservationPlugin();
        $oldContent = $plugin->getSetting($journal->getId(), 'preservedXMLcontent') ?? '';

        $xmlFilePath = $this->createXml($journal, $baseUrl);
        $email->addAttachment($xmlFilePath);

        $newContent = '';
        if (is_readable($xmlFilePath)) {
            $newContent = file_get_contents($xmlFilePath) ?: '';
        }

        if ($oldContent !== '' && $newContent !== '' && $oldContent !== $newContent) {
            $diffGenerator = new XmlDiffGenerator();
            $diff = $diffGenerator->generate($oldContent, $newContent);
            if (!is_null($diff)) {
                $timestamp = date('YmdHis');
                $diffFilePath = "/tmp/diff_preservacao_{$journalAcronym}_{$timestamp}.diff";
                file_put_contents($diffFilePath, $diff);
                $email->addAttachment($diffFilePath);
            }
        }

        (new PreservationXmlStatePersister())->persist($journal->getId(), $xmlFilePath);

        return $email;
    }

    protected function setEmailSubjectAndBody($email, $journalAcronym, $locale)
    {
        $subject = __('plugins.generic.carinianaPreservation.preservationUpdateEmail.subject', ['journalAcronym' => $journalAcronym], $locale);
        $body = __('plugins.generic.carinianaPreservation.preservationUpdateEmail.body', ['journalAcronym' => $journalAcronym], $locale);
        $email->setSubject($subject);
        $email->setBody($body);
    }
}
