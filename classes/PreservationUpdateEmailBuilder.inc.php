<?php

import('plugins.generic.carinianaPreservation.classes.BasePreservationEmailBuilder');

class PreservationUpdateEmailBuilder extends BasePreservationEmailBuilder
{
    public function buildPreservationUpdateEmail($journal, $baseUrl, $locale)
    {
        $email = $this->buildBaseEmail($journal, $locale);

        $journalAcronym = $journal->getLocalizedData('acronym', $locale);
        $this->setEmailSubjectAndBody($email, $journalAcronym, $locale);

        $xmlFilePath = $this->createXml($journal, $baseUrl, $locale);
        $email->addAttachment($xmlFilePath);

        $this->updatePreservationSettings($journal, $xmlFilePath);

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
