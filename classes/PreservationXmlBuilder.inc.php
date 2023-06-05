<?php

import('classes.journal.Journal');

class PreservationXmlBuilder {
    
    private $journal;
    private $issues;
    private $baseUrl;
    private $locale;
    
    public function __construct(Journal $journal, array $issues, string $baseUrl, string $locale)
    {
        $this->journal = $journal;
        $this->issues = $issues;
        $this->baseUrl = $baseUrl;
        $this->locale = $locale;
    }

    public function createPreservationXml(string $filePath)
    {
        $xml = new DOMDocument('1.0', 'UTF-8');
        $xml->formatOutput = true;
        $xml->save($filePath);
    }
}