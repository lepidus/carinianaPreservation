<?php

import('plugins.generic.carinianaPreservation.CarinianaPreservationPlugin');

class PreservationXmlStatePersister
{
    public function persist(int $journalId, string $xmlFilePath): void
    {
        $plugin = new CarinianaPreservationPlugin();
        $plugin->updateSetting($journalId, 'lastPreservationTimestamp', Core::getCurrentDate());

        $xmlMd5 = md5_file($xmlFilePath);
        if ($xmlMd5) {
            $plugin->updateSetting($journalId, 'preservedXMLmd5', $xmlMd5);
        }

        if (is_readable($xmlFilePath)) {
            $xmlContent = file_get_contents($xmlFilePath);
            if ($xmlContent !== false && trim($xmlContent) !== '') {
                $plugin->updateSetting($journalId, 'preservedXMLcontent', $xmlContent);
            }
        }
    }
}
