<?php

namespace APP\plugins\generic\carinianaPreservation\tests;

use APP\plugins\generic\carinianaPreservation\classes\XmlDiffGenerator;
use PHPUnit\Framework\TestCase;

class XmlDiffGeneratorTest extends TestCase
{
    private $originalXml;
    private $mutableXml;
    private $generator;

    protected function setUp(): void
    {
        $this->generator = new XmlDiffGenerator();
        $this->originalXml = <<<XML
--- previous
<lockss-config>
  <property name="org.lockss.titleSet">
    <property name="PublisherA">
      <property name="name" value="All PublisherA"/>
    </property>
  </property>
  <property name="org.lockss.title">
    <property name="OJS3PluginJNL1_2023">
      <property name="attributes.publisher" value="PublisherA"/>
      <property name="journalTitle" value="JNL"/>
      <property name="issn" value="1234-5678"/>
      <property name="eissn" value="0101-1010"/>
      <property name="type" value="journal"/>
      <property name="title" value="Issue Title 2023"/>
      <property name="plugin" value="org.lockss.plugin.ojs3.ClockssOJS3Plugin"/>
      <property name="params">
        <property name="param.1"><property name="key" value="base_url"/><property name="value" value="https://example.org"/></property>
        <property name="param.2"><property name="key" value="journal_id"/><property name="value" value="journalpath"/></property>
        <property name="param.3"><property name="key" value="year"/><property name="value" value="2023"/></property>
      </property>
      <property name="attributes.year" value="2023"/>
      <property name="attributes.volume" value="1"/>
    </property>
  </property>
</lockss-config>
XML;
        $this->mutableXml = $this->originalXml;
    }

    private function diffWith(string $search, string $replace): ?string
    {
        $new = str_replace($search, $replace, $this->mutableXml);
        return $this->generator->generate($this->stripHeader($this->originalXml), $this->stripHeader($new));
    }

    private function stripHeader(string $xml): string
    {
        return preg_replace('/^--- previous\n\+\+\+ current\n/m', '', $xml);
    }

    public function testNoChangeReturnsNull(): void
    {
        $diff = $this->generator->generate($this->stripHeader($this->originalXml), $this->stripHeader($this->originalXml));
        $this->assertNull($diff);
    }

    public function testPublisherChangeProducesDiff(): void
    {
        $diff = $this->diffWith('PublisherA', 'PublisherB');
        $this->assertNotNull($diff);
        $this->assertStringContainsString('-      <property name="attributes.publisher" value="PublisherA"/>', $diff);
        $this->assertStringContainsString('+      <property name="attributes.publisher" value="PublisherB"/>', $diff);
    }

    public function testAcronymChangeChangesNodeName(): void
    {
        $diff = $this->diffWith('OJS3PluginJNL1_2023', 'OJS3PluginNEW1_2023');
        $this->assertNotNull($diff);
        $this->assertStringContainsString('-    <property name="OJS3PluginJNL1_2023">', $diff);
        $this->assertStringContainsString('+    <property name="OJS3PluginNEW1_2023">', $diff);
    }

    public function testIssnChangeProducesDiff(): void
    {
        $diff = $this->diffWith('1234-5678', '9999-9999');
        $this->assertNotNull($diff);
        $this->assertStringContainsString('-      <property name="issn" value="1234-5678"/>', $diff);
        $this->assertStringContainsString('+      <property name="issn" value="9999-9999"/>', $diff);
    }

    public function testBaseUrlChangeProducesDiff(): void
    {
        $diff = $this->diffWith('https://example.org', 'https://example.net');
        $this->assertNotNull($diff);
        $this->assertStringContainsString('-        <property name="param.1"><property name="key" value="base_url"/><property name="value" value="https://example.org"/></property>', $diff);
        $this->assertStringContainsString('+        <property name="param.1"><property name="key" value="base_url"/><property name="value" value="https://example.net"/></property>', $diff);
    }

    public function testJournalPathChangeProducesDiff(): void
    {
        $diff = $this->diffWith('journalpath', 'newjournalpath');
        $this->assertNotNull($diff);
        $this->assertStringContainsString('-        <property name="param.2"><property name="key" value="journal_id"/><property name="value" value="journalpath"/></property>', $diff);
        $this->assertStringContainsString('+        <property name="param.2"><property name="key" value="journal_id"/><property name="value" value="newjournalpath"/></property>', $diff);
    }

    public function testYearAddedProducesDiff(): void
    {
        $newXml = str_replace('</lockss-config>', "  <property name=\"OJS3PluginJNL1_2024\">\n    <property name=\"attributes.publisher\" value=\"PublisherA\"/>\n  </property>\n</lockss-config>", $this->originalXml);
        $diff = $this->generator->generate($this->stripHeader($this->originalXml), $this->stripHeader($newXml));
        $this->assertNotNull($diff);
        $this->assertStringContainsString('+  <property name="OJS3PluginJNL1_2024">', $diff);
    }

    public function testYearRemovedProducesDiff(): void
    {
        $oldContent = $this->stripHeader($this->originalXml);
        $newContent = preg_replace('/\s*<property name=\"OJS3PluginJNL1_2023\">[\s\S]*?<\/property>\n/m', '', $oldContent);
        $diff = $this->generator->generate($oldContent, $newContent);
        $this->assertNotNull($diff);
        $this->assertStringContainsString('-    <property name="OJS3PluginJNL1_2023">', $diff);
    }


    public function testEissnChangeProducesDiff(): void
    {
        $diff = $this->diffWith('0101-1010', '2222-3333');
        $this->assertNotNull($diff);
        $this->assertStringContainsString('-      <property name="eissn" value="0101-1010"/>', $diff);
        $this->assertStringContainsString('+      <property name="eissn" value="2222-3333"/>', $diff);
    }

    public function testIssueTitleChangeProducesDiff(): void
    {
        $diff = $this->diffWith('Issue Title 2023', 'Issue Title Revised');
        $this->assertNotNull($diff);
        $this->assertStringContainsString('-      <property name="title" value="Issue Title 2023"/>', $diff);
        $this->assertStringContainsString('+      <property name="title" value="Issue Title Revised"/>', $diff);
    }

    public function testVolumeChangeProducesDiff(): void
    {
        $diff = $this->diffWith('attributes.volume" value="1"', 'attributes.volume" value="2"');
        $this->assertNotNull($diff);
        $this->assertStringContainsString('-      <property name="attributes.volume" value="1"/>', $diff);
        $this->assertStringContainsString('+      <property name="attributes.volume" value="2"/>', $diff);
    }

    public function testYearParamChangeProducesDiff(): void
    {
        $diff = $this->diffWith('param.3"><property name="key" value="year"/><property name="value" value="2023"', 'param.3"><property name="key" value="year"/><property name="value" value="2024"');
        $this->assertNotNull($diff);
        $this->assertStringContainsString('-        <property name="param.3"><property name="key" value="year"/><property name="value" value="2023"/></property>', $diff);
        $this->assertStringContainsString('+        <property name="param.3"><property name="key" value="year"/><property name="value" value="2024"/></property>', $diff);
    }
}
