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
        $issuesVolumes = $this->getIssuesVolumes($journal);
        $versionDao = DAORegistry::getDAO('VersionDAO'); /** @var VersionDAO $versionDao */
        $ojsVersion = $versionDao->getCurrentVersion();

        return new PreservedJournal(
            $publisherOrInstitution,
            $title,
            $issn,
            $eIssn,
            $baseUrl,
            $journalPath,
            $availableYears,
            $issuesVolumes,
            $notesAndComments,
            $ojsVersion->getVersionString()
        );
    }

    private function getAvailableYears($journal): string
    {
        $issueDao = DAORegistry::getDAO('IssueDAO'); /** @var IssueDAO $issueDao */
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

    private function getIssuesVolumes($journal): string
    {
        $issueDao = DAORegistry::getDAO('IssueDAO'); /** @var IssueDAO $issueDao */
        $issues = array_reverse($issueDao->getPublishedIssues($journal->getId())->toArray());
        $lastIssue = end($issues);
        $volumes = "";

        foreach ($issues as $issue) {
            $volumes .= $issue->getVolume();
            if ($issue != $lastIssue && !empty($issue->getVolume())) {
                $volumes .= "; ";
            }
        }

        return $volumes;
    }
}
