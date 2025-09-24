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
        $email = new CarinianaMailable();

        $fromName = $journal->getLocalizedData('acronym', $locale);
        $fromEmail = $journal->getData('contactEmail');

        $email->from($fromEmail, $fromName);

        $toName = CARINIANA_NAME;
        $toEmail = CARINIANA_EMAIL;

        $testEmail = Config::getVar('carinianapreservation', 'email_for_tests');
        if ($testEmail) {
            $toName = (string) $journal->getData('contactName');
            $toEmail = $testEmail;
        }

        $email->to($toEmail, $toName);

        if (!$testEmail) {
            $email->cc($fromEmail, $fromName);
        }

        $plugin = new CarinianaPreservationPlugin();
        $extraCopyEmail = $plugin->getSetting($journal->getId(), 'extraCopyEmail');
        if (!empty($extraCopyEmail)) {
            $email->cc($extraCopyEmail);
        }

        // Populate viewData for tests
        $ccs = [];
        if (!$testEmail) {
            $ccs[] = ['name' => $fromName, 'email' => $fromEmail];
        }
        if (!empty($extraCopyEmail)) {
            $ccs[] = ['name' => '', 'email' => $extraCopyEmail];
        }
        $email->addData([
            'from' => ['name' => $fromName, 'email' => $fromEmail],
            'recipients' => [
                ['name' => $toName, 'email' => $toEmail]
            ],
            'ccs' => $ccs,
        ]);

        return $email;
    }

    protected function addAttachment(Mailable $email, string $path, ?string $filename = null, ?string $contentType = null): void
    {
        $filename = $filename ?? basename($path);
        if ($contentType === null) {
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            $contentType = match ($ext) {
                'xml' => 'text/xml',
                'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'diff' => 'text/plain',
                default => 'application/octet-stream'
            };
        }

        $email->attach($path, ['as' => $filename, 'mime' => $contentType]);

        // Mirror attachment info into viewData for tests
        $data = $email->getData();
        $attachments = $data['attachments'] ?? [];
        $attachments[] = [
            'path' => $path,
            'filename' => $filename,
            'content-type' => $contentType,
        ];
        $email->addData(['attachments' => $attachments]);
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

    protected function formatBodyAsHtml(string $text): string
    {
        $escaped = htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8');
        return '<div style="white-space:pre-line">' . $escaped . '</div>';
    }
}
