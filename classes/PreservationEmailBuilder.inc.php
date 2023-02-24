<?php

import('lib.pkp.classes.mail.Mail');

class PreservationEmailBuilder {

    public function buildPreservationEmail($journal, $preservationName, $preservationEmail, $locale) {
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

        return $email;
    }

}