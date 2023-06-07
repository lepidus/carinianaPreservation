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
        $dom = new DOMDocument('1.0', 'UTF-8');

        $lockssConfig = $dom->createElement('lockss-config');
        $dom->appendChild($lockssConfig);

        $titleSet = $this->createTitleSetProperty($dom);
        $lockssConfig->appendChild($titleSet);

        $titleProperty = $this->createXmlProperty($dom, 'org.lockss.title');
        $lockssConfig->appendChild($titleProperty);

        $preservedYearsData = $this->getPreservedYearsData();
        foreach($preservedYearsData as $preservedYear) {
            $preservedYearProperty = $this->createPreservedYearProperty($dom, $preservedYear['year'], $preservedYear['title'], $preservedYear['volume'], $preservedYear['volumeText']);
            $titleProperty->appendChild($preservedYearProperty);
        }

        $dom->formatOutput = true;
        $dom->save($filePath);
    }

    private function getPreservedYearsData(): array
    {
        $preservedYearsData = [];

        $issuesByYear = [];
        foreach($this->issues as $issue) {
            $issueYear = $issue->getData('year');
            if(array_key_exists($issueYear, $issuesByYear))
                $issuesByYear[$issueYear] = array_merge($issuesByYear[$issueYear], [$issue]);
            else
                $issuesByYear[$issueYear] = [$issue];
        }

        foreach($issuesByYear as $issuesFromYear) {
            $numIssues = count($issuesFromYear);
            $firstIssue = $issuesFromYear[0];
            $lastIssue = $issuesFromYear[$numIssues-1];

            $indexToUse = (is_null($firstIssue->getData('volume')) ? 'number' : 'volume');

            if($firstIssue->getData($indexToUse) == $lastIssue->getData($indexToUse))
                $volumeText = $firstIssue->getData($indexToUse);
            else
                $volumeText = $firstIssue->getData($indexToUse) . "-" . $lastIssue->getData($indexToUse);
            
            $year = $firstIssue->getData('year');
            $preservedYearsData[$year] = [
                'year' => $year,
                'title' => $firstIssue->getData('title', $this->locale),
                'volume' => $firstIssue->getData($indexToUse),
                'volumeText' => $volumeText
            ];
        }

        return $preservedYearsData;
    }

    private function createXmlProperty($dom, $name, $value = null)
    {
        $property = $dom->createElement('property');
        $property->setAttribute('name', $name);
        if($value) {
            $property->setAttribute('value', $value);
        }

        return $property;
    }

    private function createTitleSetProperty($dom)
    {
        $titleSetProperty = $this->createXmlProperty($dom, 'org.lockss.titleSet');

        $journalTitle = $this->journal->getData('name', $this->locale);
        $journalProperty = $this->createXmlProperty($dom, $journalTitle);
        $titleSetProperty->appendChild($journalProperty);

        $nameProperty = $this->createXmlProperty($dom, 'name', "All $journalTitle");
        $journalProperty->appendChild($nameProperty);

        $classProperty = $this->createXmlProperty($dom, 'class', 'xpath');
        $journalProperty->appendChild($classProperty);

        $xpathProperty = $this->createXmlProperty($dom, 'xpath', '[attributes/publisher="' . $journalTitle . '"]');
        $journalProperty->appendChild($xpathProperty);

        return $titleSetProperty;
    }

    private function createPreservedYearParamProperty($dom, $index, $key, $value)
    {
        $paramProperty = $this->createXmlProperty($dom, "param.$index");

        $keyProperty = $this->createXmlProperty($dom, "key", $key);
        $paramProperty->appendChild($keyProperty);

        $valueProperty = $this->createXmlProperty($dom, "value", $value);
        $paramProperty->appendChild($valueProperty);

        return $paramProperty;
    }

    private function createPreservedYearProperty($dom, $year, $title, $volume, $volumeText)
    {
        $journalAcronym = $this->journal->getData('acronym', $this->locale);
        $preservedYearNodeName = 'OJS3Plugin' . $journalAcronym . $volume . '_' . $year;
        $preservedYear = $this->createXmlProperty($dom, $preservedYearNodeName);

        $preservedYearProperties = [
            'attributes.publisher' => $this->journal->getData('name', $this->locale),
            'journalTitle' => $journalAcronym,
            'issn' => $this->journal->getData('printIssn'),
            'eissn' => $this->journal->getData('onlineIssn'),
            'type' => 'journal',
            'title' => $title,
            'plugin' => 'org.lockss.plugin.ojs3.OJS3Plugin',
            'params' => [
                'base_url' => $this->baseUrl,
                'journal_id' => $this->journal->getData('urlPath'),
                'year' => $year
            ],
            'attributes.year' => $year,
            'attributes.volume' => $volumeText
        ];

        foreach ($preservedYearProperties as $propertyName => $propertyValue) {
            if($propertyName == 'params') {
                $i = 1;
                foreach($propertyValue as $paramName => $paramValue) {
                    $paramNode = $this->createPreservedYearParamProperty($dom, $i, $paramName, $paramValue);
                    $preservedYear->appendChild($paramNode);
                    $i++;
                }
            }
            else {
                $propertyNode = $this->createXmlProperty($dom, $propertyName, $propertyValue);
                $preservedYear->appendChild($propertyNode);
            }
        }

        return $preservedYear;
    }
}