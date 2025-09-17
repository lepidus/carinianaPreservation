<?php

namespace APP\plugins\generic\carinianaPreservation\classes;

use APP\plugins\generic\carinianaPreservation\CarinianaPreservationPlugin;

class PreservationChangeDetector
{
    private $plugin;
    private $journalId;

    public function __construct(int $journalId)
    {
        $this->plugin = new CarinianaPreservationPlugin();
        $this->journalId = $journalId;
    }

    public function hasChanges(string $currentXml): bool
    {
        $storedMd5 = $this->plugin->getSetting($this->journalId, 'preservedXMLmd5');
        $currentMd5 = md5($currentXml);
        if (empty($storedMd5)) {
            return true;
        }
        return $storedMd5 !== $currentMd5;
    }
}
