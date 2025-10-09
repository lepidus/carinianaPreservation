<?php

namespace APP\plugins\generic\carinianaPreservation\tests;

use APP\plugins\generic\carinianaPreservation\CarinianaPreservationPlugin;
use APP\plugins\generic\carinianaPreservation\classes\PreservationXmlStatePersister;
use PKP\tests\PKPTestCase;

class PreservationXmlStatePersisterTest extends PKPTestCase
{
    use CarinianaTestFixtureTrait;

    private $journal;
    private $xmlFilePath;

    public function setUp(): void
    {
        parent::setUp();
        $this->journal = $this->buildAndPersistJournal();
        $this->xmlFilePath = sys_get_temp_dir() . '/test_preservation_xml_' . uniqid() . '.xml';
        file_put_contents($this->xmlFilePath, "<root><node year='2025'>Test</node></root>\n");
    }

    public function tearDown(): void
    {
        if (file_exists($this->xmlFilePath)) {
            unlink($this->xmlFilePath);
        }
        $this->cleanupJournal($this->journal);
        parent::tearDown();
    }

    public function testPersistStoresTimestampMd5AndContent(): void
    {
        $persister = new PreservationXmlStatePersister();
        $persister->persist($this->journal->getId(), $this->xmlFilePath);

        $plugin = new CarinianaPreservationPlugin();
        $timestamp = $plugin->getSetting($this->journal->getId(), 'lastPreservationTimestamp');
        $md5 = $plugin->getSetting($this->journal->getId(), 'preservedXMLmd5');
        $content = $plugin->getSetting($this->journal->getId(), 'preservedXMLcontent');

        $this->assertNotEmpty($timestamp);
        $this->assertNotEmpty($md5);
        $this->assertNotEmpty($content);
        $this->assertEquals(md5($content), $md5);
    }
}
