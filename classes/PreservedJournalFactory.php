<?php

namespace APP\plugins\generic\carinianaPreservation\classes;

use APP\plugins\generic\carinianaPreservation\classes\PreservedJournal;
use PKP\db\DAORegistry;

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
        $issues = $issueDao->getPublishedIssues($journal->getId())->toArray();

        $years = [];
        foreach ($issues as $issue) {
            $year = $issue->getData('year');
            if (!$year) {
                $datePublished = $issue->getData('datePublished');
                if ($datePublished) {
                    $year = substr($datePublished, 0, 4);
                }
            }
            if (!$year) {
                continue;
            }
            $year = (string)intval($year);
            $years[] = $year;
        }

        $years = array_values(array_unique($years));
        sort($years, SORT_NUMERIC);

        return implode('; ', $years);
    }

    private function getIssuesVolumes($journal): string
    {
        $issueDao = DAORegistry::getDAO('IssueDAO'); /** @var IssueDAO $issueDao */
        $issues = array_reverse($issueDao->getPublishedIssues($journal->getId())->toArray());

        $normalized = [];
        foreach ($issues as $issue) {
            $volume = $issue->getVolume();
            if ($volume === null) {
                continue;
            }
            $volStr = trim((string)$volume);
            if ($volStr === '' || $volStr === '0') {
                continue;
            }
            if (ctype_digit($volStr)) {
                $volStr = (string)intval($volStr);
            } else {
                // Non-numeric volumes are ignored to avoid corrupt output
                continue;
            }
            if (!in_array($volStr, $normalized, true)) {
                $normalized[] = $volStr;
            }
        }

        return implode('; ', $normalized);
    }
}
