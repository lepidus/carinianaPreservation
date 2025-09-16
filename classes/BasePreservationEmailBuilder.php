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

    $from = ['name' => $fromName, 'email' => $fromEmail];
    $recipients = [];
    $copies = [];

        if (Config::getVar('carinianapreservation', 'email_for_tests')) {
            $recipients[] = [
                'name' => $journal->getData('contactName'),
                'email' => Config::getVar('carinianapreservation', 'email_for_tests'),
            ];
        } else {
            $recipients[] = ['name' => CARINIANA_NAME, 'email' => CARINIANA_EMAIL];
            $copies[] = $from; // contato da revista recebe cópia
        }

        $plugin = new CarinianaPreservationPlugin();
        $extraCopyEmail = $plugin->getSetting($journal->getId(), 'extraCopyEmail');
        if (!empty($extraCopyEmail)) {
            $copies[] = ['name' => '', 'email' => $extraCopyEmail];
        }

        $email->addData([
            'from' => $from,
            'recipients' => $recipients,
            'copies' => $copies,
            'ccs' => $copies, // manter chave antiga para compatibilidade
            'attachments' => [],
        ]);

        return $email;
    }

    protected function addAttachment(Mailable $email, string $path, ?string $filename = null, ?string $contentType = null): void
    {
        $data = $email->getData();
        $attachments = $data['attachments'] ?? [];

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
}
