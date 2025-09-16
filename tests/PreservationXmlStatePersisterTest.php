<?php

namespace APP\plugins\generic\carinianaPreservation\tests;

use PKP\tests\DatabaseTestCase;
use APP\plugins\generic\carinianaPreservation\classes\PreservationXmlStatePersister;
use APP\plugins\generic\carinianaPreservation\CarinianaPreservationPlugin;

class PreservationXmlStatePersisterTest extends DatabaseTestCase
{
    private $journalId = 999; // isolated id for test
    private $xmlFilePath;

    protected function getAffectedTables()
    {
        return ['plugin_settings'];
    }

    public function setUp(): void
    {
        parent::setUp();
        $this->xmlFilePath = sys_get_temp_dir() . '/test_preservation_xml_' . uniqid() . '.xml';
        file_put_contents($this->xmlFilePath, "<root><node year='2025'>Test</node></root>\n");
    }

    public function tearDown(): void
    {
        if (file_exists($this->xmlFilePath)) {
            unlink($this->xmlFilePath);
        }
        parent::tearDown();
    }

    public function testPersistStoresTimestampMd5AndContent(): void
    {
        $persister = new PreservationXmlStatePersister();
        $persister->persist($this->journalId, $this->xmlFilePath);

        $plugin = new CarinianaPreservationPlugin();
        $timestamp = $plugin->getSetting($this->journalId, 'lastPreservationTimestamp');
        $md5 = $plugin->getSetting($this->journalId, 'preservedXMLmd5');
        $content = $plugin->getSetting($this->journalId, 'preservedXMLcontent');

        $this->assertNotEmpty($timestamp);
        $this->assertNotEmpty($md5);
        $this->assertNotEmpty($content);
        $this->assertEquals(md5($content), $md5);
    }
}
