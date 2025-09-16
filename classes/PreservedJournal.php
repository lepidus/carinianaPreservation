<?php

namespace APP\plugins\generic\carinianaPreservation\classes;

class PreservedJournal
{
    private $publisherOrInstitution;
    private $title;
    private $issn;
    private $eIssn;
    private $baseUrl;
    private $journalPath;
    private $availableYears;
    private $issuesVolumes;
    private $notesAndComments;
    private $ojsVersion;

    public function __construct(
        string $publisherOrInstitution,
        string $title,
        ?string $issn,
        ?string $eIssn,
        string $baseUrl,
        string $journalPath,
        string $availableYears,
        ?string $issuesVolumes,
        string $notesAndComments,
        string $ojsVersion
    ) {
        $this->publisherOrInstitution = $publisherOrInstitution;
        $this->title = $title;
        $this->issn = $issn;
        $this->eIssn = $eIssn;
        $this->baseUrl = $baseUrl;
        $this->journalPath = $journalPath;
        $this->availableYears = $availableYears;
        $this->issuesVolumes = $issuesVolumes;
        $this->notesAndComments = $notesAndComments;
        $this->ojsVersion = $ojsVersion;
    }

    public function asRecord(): array
    {
        return [
            $this->publisherOrInstitution,
            $this->title,
            $this->issn,
            $this->eIssn,
            $this->baseUrl,
            $this->journalPath,
            $this->availableYears,
            $this->issuesVolumes,
            $this->notesAndComments,
            $this->ojsVersion
        ];
    }
}
