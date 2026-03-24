<?php

class PreservedJournalSpreadsheet
{
    private $journal;

    public function __construct(PreservedJournal $journal)
    {
        $this->journal = $journal;
    }

    private function getHeaders(): array
    {
        return [
            __("plugins.generic.carinianaPreservation.headers.publisherOrInstitution"),
            __("plugins.generic.carinianaPreservation.headers.title"),
            __("plugins.generic.carinianaPreservation.headers.issn"),
            __("plugins.generic.carinianaPreservation.headers.eIssn"),
            __("plugins.generic.carinianaPreservation.headers.baseUrl"),
            __("plugins.generic.carinianaPreservation.headers.journalPath"),
            __("plugins.generic.carinianaPreservation.headers.availableYears"),
            __("plugins.generic.carinianaPreservation.headers.issuesVolumes"),
            __("plugins.generic.carinianaPreservation.headers.notesAndComments"),
            __("admin.systemVersion")
        ];
    }

    public function createCsv(string $filePath)
    {
        AppLocale::requireComponents(LOCALE_COMPONENT_APP_ADMIN);

        $handle = fopen($filePath, 'w');
        if ($handle === false) {
            throw new Exception("Failed to open file for writing: {$filePath}");
        }
        fputcsv($handle, $this->getHeaders());
        fputcsv($handle, $this->journal->asRecord());
        fclose($handle);
    }
}
