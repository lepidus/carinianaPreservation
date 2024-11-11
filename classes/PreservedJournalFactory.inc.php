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

        return new PreservedJournal(
            $publisherOrInstitution,
            $title,
            $issn,
            $eIssn,
            $baseUrl,
            $journalPath,
            $availableYears,
            $notesAndComments
        );
    }

    private function getAvailableYears($journal): string
    {
        $issueDao = DAORegistry::getDAO('IssueDAO');
        $issues = array_reverse($issueDao->getPublishedIssues($journal->getId())->toArray());
        $issuesYearList = [];
        $availableYears = "";

        foreach ($issues as $issue) {
            $issuesYearList[] = (new DateTime($issue->getData('datePublished')))->format('Y');
        }

        $issuesYearList = array_unique($issuesYearList);
        $lastIssueYear = end($issuesYearList);

        foreach ($issuesYearList as $issueYear) {
            $availableYears .= $issueYear;
            if ($issueYear != $lastIssueYear) {
                $availableYears .= "; ";
            }
        }

        return $availableYears;
    }
}
