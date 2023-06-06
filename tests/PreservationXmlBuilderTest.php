<?php

use PHPUnit\Framework\TestCase;

import('lib.pkp.tests.PKPTestCase');
import('classes.journal.Journal');
import('classes.issue.Issue');
import('plugins.generic.carinianaPreservation.classes.PreservationXmlBuilder');

class PreservationXmlBuilderTest extends PKPTestCase
{
    private $preservationXmlBuilder;
    private $xml;
    private $journal;
    private $issues = [];
    private $journalId = 2;
    private $locale = 'pt_BR';
    private $journalAcronym = 'RBRB';
    private $journalContactEmail = 'contact@rbrb.com.br';
    private $publisherOrInstitution = 'SciELO';
    private $title = 'SciELO Journal n18';
    private $issn = '1234-1234';
    private $eIssn = '0101-1010';
    private $baseUrl = 'https://scielo-journal-18.com.br/';
    private $journalPath = 'scielojournal18';
    private $xmlPath = '/tmp/test_preservation_xml.xml';

    public function setUp(): void
    {
        parent::setUp();
        $this->createTestJournal();
    }

    private function createTestJournal(): void
    {
        $this->journal = new Journal();
        $this->journal->setId($this->journalId);
        $this->journal->setData('publisherInstitution', $this->publisherOrInstitution);
        $this->journal->setData('name', $this->title, $this->locale);
        $this->journal->setData('printIssn', $this->issn);
        $this->journal->setData('onlineIssn', $this->eIssn);
        $this->journal->setData('urlPath', $this->journalPath);
        $this->journal->setData('acronym', $this->journalAcronym, $this->locale);
        $this->journal->setData('contactEmail', $this->journalContactEmail);
    }

    private function createTestIssue(int $year, string $title, int $volume, int $number): Issue
    {
        $issue = new Issue();
        $issue->setData('year', $year);
        $issue->setData('title', $title, $this->locale);
        $issue->setData('volume', $volume);
        $issue->setData('number', $number);

        return $issue;
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

    private function createExpectedTitleSetProperty($dom)
    {
        $titleSet = $this->createXmlProperty($dom, 'org.lockss.titleSet');

        $journalProperty = $this->createXmlProperty($dom, $this->title);
        $titleSet->appendChild($journalProperty);

        $nameProperty = $this->createXmlProperty($dom, 'name', 'All ' . $this->title);
        $journalProperty->appendChild($nameProperty);

        $classProperty = $this->createXmlProperty($dom, 'class', 'xpath');
        $journalProperty->appendChild($classProperty);

        $xpathProperty = $this->createXmlProperty($dom, 'xpath', '[attributes/publisher="' . $this->title . '"]');
        $journalProperty->appendChild($xpathProperty);

        return $titleSet;
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

    private function createPreservedYearProperty($dom, $year, $volume)
    {
        $preservedYear = $this->createXmlProperty($dom, "OJS3PluginRBRB${volume}_${year}");

        $publisherProperty = $this->createXmlProperty($dom, 'attributes.publisher', $this->title);
        $preservedYear->appendChild($publisherProperty);

        $journalTitleProperty = $this->createXmlProperty($dom, 'journalTitle', $this->journalAcronym);
        $preservedYear->appendChild($journalTitleProperty);

        $issnProperty = $this->createXmlProperty($dom, 'issn', $this->issn);
        $preservedYear->appendChild($issnProperty);

        $eIssnProperty = $this->createXmlProperty($dom, 'eissn', $this->eIssn);
        $preservedYear->appendChild($eIssnProperty);

        $typeProperty = $this->createXmlProperty($dom, 'type', 'journal');
        $preservedYear->appendChild($typeProperty);

        $titleProperty = $this->createXmlProperty($dom, 'title', "RBRB 1sem $year");
        $preservedYear->appendChild($titleProperty);

        $pluginProperty = $this->createXmlProperty($dom, 'plugin', 'org.lockss.plugin.ojs3.OJS3Plugin');
        $preservedYear->appendChild($pluginProperty);

        $param1Property = $this->createPreservedYearParamProperty($dom, 1, 'base_url', $this->baseUrl);
        $preservedYear->appendChild($param1Property);

        $param2Property = $this->createPreservedYearParamProperty($dom, 2, 'journal_id', $this->journalPath);
        $preservedYear->appendChild($param2Property);

        $param3Property = $this->createPreservedYearParamProperty($dom, 3, 'year', $year);
        $preservedYear->appendChild($param3Property);

        $attributesYearProperty = $this->createXmlProperty($dom, 'attributes.year', $year);
        $preservedYear->appendChild($attributesYearProperty);

        $attributesVolumeProperty = $this->createXmlProperty($dom, 'attributes.volume', $volume);
        $preservedYear->appendChild($attributesVolumeProperty);

        return $preservedYear;
    }

    private function createExpectedXml($preservedYears)
    {
        $dom = new DOMDocument('1.0', 'UTF-8');

        $lockssConfig = $dom->createElement('lockss-config');
        $dom->appendChild($lockssConfig);

        $titleSet = $this->createExpectedTitleSetProperty($dom);
        $lockssConfig->appendChild($titleSet);

        $titleProperty = $this->createXmlProperty($dom, 'org.lockss.title');
        $lockssConfig->appendChild($titleProperty);

        foreach($preservedYears as $year => $volume) {
            $preservedYearProperty = $this->createPreservedYearProperty($dom, $year, $volume);
            $titleProperty->appendChild($preservedYearProperty);
        }

        $dom->formatOutput = true;

        return $dom;
    }

    public function testPreservationXmlCreationUsingVolume(): void
    {
        $this->issues = [
            $this->createTestIssue(2018, 'RBRB 1sem 2018', 1, 1),
            $this->createTestIssue(2018, 'RBRB 2sem 2018', 1, 2),
            $this->createTestIssue(2019, 'RBRB 1sem 2019', 2, 1)
        ];
        $this->xml = $this->createExpectedXml(['2018' => '1', '2019' => '2']);
        
        $preservationXmlBuilder = new PreservationXmlBuilder($this->journal, $this->issues, $this->baseUrl, $this->locale);
        $preservationXmlBuilder->createPreservationXml($this->xmlPath);

        $writtenXml = new DOMDocument();
        $writtenXml->load($this->xmlPath);

        $this->assertEquals($this->xml->saveXML(), $writtenXml->saveXML());
    }
}
