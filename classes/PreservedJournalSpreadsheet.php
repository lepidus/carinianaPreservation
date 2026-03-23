<?php

namespace APP\plugins\generic\carinianaPreservation\classes;

class PreservedJournalSpreadsheet
{
    private $journals;
    private $locale;

    public function __construct(array $journals, string $locale)
    {
        $this->journals = $journals;
        $this->locale = $locale;
    }

    private function getHeaders(): array
    {
        return [
            __('plugins.generic.carinianaPreservation.headers.publisherOrInstitution', [], $this->locale),
            __('plugins.generic.carinianaPreservation.headers.title', [], $this->locale),
            __('plugins.generic.carinianaPreservation.headers.issn', [], $this->locale),
            __('plugins.generic.carinianaPreservation.headers.eIssn', [], $this->locale),
            __('plugins.generic.carinianaPreservation.headers.baseUrl', [], $this->locale),
            __('plugins.generic.carinianaPreservation.headers.journalPath', [], $this->locale),
            __('plugins.generic.carinianaPreservation.headers.availableYears', [], $this->locale),
            __('plugins.generic.carinianaPreservation.headers.issuesVolumes', [], $this->locale),
            __('plugins.generic.carinianaPreservation.headers.notesAndComments', [], $this->locale),
            __('admin.systemVersion', [], $this->locale)
        ];
    }

    public function createSpreadsheet(string $filePath)
    {
        $file = fopen($filePath, 'w');
        if ($file === false) {
            throw new \RuntimeException("Failed to open file for writing: {$filePath}");
        }

        fputcsv($file, $this->getHeaders());

        foreach ($this->journals as $journal) {
            fputcsv($file, $journal->asRecord());
        }

        fclose($file);
    }
}
