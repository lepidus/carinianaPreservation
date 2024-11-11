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
        $issues = array_reverse($issueDao->getIssues($journal->getId())->toArray());
        $lastIssue = end($issues);
        $issuesYear = "";

        foreach ($issues as $issue) {
            $issuesYear .= (new DateTime($issue->getData('datePublished')))->format('Y');
            if ($issue != $lastIssue) {
                $issuesYear .= "; ";
            }
        }

        return $issuesYear;
    }
}
