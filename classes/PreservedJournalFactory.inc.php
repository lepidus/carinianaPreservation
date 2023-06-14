<?php

import('plugins.generic.carinianaPreservation.classes.PreservedJournal');

class PreservedJournalFactory
{
    public function buildPreservedJournal($journal, $baseUrl, $notesAndComments, $locale): PreservedJournal
    {
        $publisherOrInstitution = $journal->getData('publisherInstitution');
        $title = $journal->getLocalizedData('name', $locale);
        $issn = $journal->getData('printIssn');
        $eIssn = $journal->getData('onlineIssn');
        $journalPath = $journal->getData('urlPath');
        $availableYears = $this->getAvailableYears($journal);

        return new PreservedJournal($publisherOrInstitution, $title, $issn, $eIssn, $baseUrl, $journalPath, $availableYears, $notesAndComments);
    }

    private function getAvailableYears($journal): string
    {
        $issueDao = DAORegistry::getDAO('IssueDAO');
        $issues = $issueDao->getIssues($journal->getId());

        $lastIssue = $firstIssue = $issues->next();
        while($issue = $issues->next()) {
            $firstIssue = $issue;
        }

        $lastIssueYear = (new DateTime($lastIssue->getData('datePublished')))->format('Y');
        $firstIssueYear = (new DateTime($firstIssue->getData('datePublished')))->format('Y');

        return "$firstIssueYear-$lastIssueYear";
    }
}
