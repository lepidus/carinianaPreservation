<?php

namespace APP\plugins\generic\carinianaPreservation\tests;

use APP\plugins\generic\carinianaPreservation\CarinianaPreservationPlugin;
use APP\plugins\generic\carinianaPreservation\classes\PreservationChangeDetector;
use PKP\tests\PKPTestCase;

class PreservationChangeDetectorTest extends PKPTestCase
{
    use CarinianaTestFixtureTrait;

    private $journal;

    protected function setUp(): void
    {
        parent::setUp();
        $this->journal = $this->buildAndPersistJournal();
    }

    protected function tearDown(): void
    {
        $this->cleanupJournal($this->journal);
        parent::tearDown();
    }

    private function seedStoredXml($journalId, $xml)
    {
        $plugin = new CarinianaPreservationPlugin();
        $plugin->updateSetting($journalId, 'preservedXMLmd5', md5($xml));
    }

    public function testFirstPreservationAlwaysHasChanges()
    {
        $detector = new PreservationChangeDetector($this->journal->getId());
        $this->assertTrue($detector->hasChanges('<root/>'), 'First preservation (no previous XML) must report changes');
    }

    public function testIdenticalXmlHasNoChanges()
    {
        $xml = '<root><a>1</a></root>';
        $this->seedStoredXml($this->journal->getId(), $xml);
        $detector = new PreservationChangeDetector($this->journal->getId());
        $this->assertFalse($detector->hasChanges($xml), 'Identical XML should not report changes');
    }

    public function testDifferentXmlReportsChanges()
    {
        $previous = '<root><a>1</a></root>';
        $current = '<root><a>2</a></root>';
        $this->seedStoredXml($this->journal->getId(), $previous);
        $detector = new PreservationChangeDetector($this->journal->getId());
        $this->assertTrue($detector->hasChanges($current), 'Different XML content should report changes');
    }

    public function testWhitespaceDifferencesAreConsideredChanges()
    {
        $pretty = "<root>\n  <a>1</a>\n</root>\n";
        $compact = '<root><a>1</a></root>';
        $this->seedStoredXml($this->journal->getId(), $pretty);
        $detector = new PreservationChangeDetector($this->journal->getId());
        $this->assertTrue($detector->hasChanges($compact), 'Whitespace differences change MD5 and are treated as changes');
    }
}
