<?php

namespace APP\plugins\generic\carinianaPreservation\tests;

use APP\plugins\generic\carinianaPreservation\CarinianaPreservationPlugin;
use APP\plugins\generic\carinianaPreservation\classes\PreservationChangeDetector;
use PKP\tests\DatabaseTestCase;

class PreservationChangeDetectorTest extends DatabaseTestCase
{
    private $journalId = 9998;
    protected function getAffectedTables()
    {
        return ['plugin_settings'];
    }

    private function seedStoredXml($journalId, $xml)
    {
        $plugin = new CarinianaPreservationPlugin();
        $plugin->updateSetting($journalId, 'preservedXMLmd5', md5($xml));
    }

    public function testFirstPreservationAlwaysHasChanges()
    {
        $detector = new PreservationChangeDetector($this->journalId);
        $this->assertTrue($detector->hasChanges('<root/>'), 'First preservation (no previous XML) must report changes');
    }

    public function testIdenticalXmlHasNoChanges()
    {
        $xml = '<root><a>1</a></root>';
        $this->seedStoredXml($this->journalId, $xml);
        $detector = new PreservationChangeDetector($this->journalId);
        $this->assertFalse($detector->hasChanges($xml), 'Identical XML should not report changes');
    }

    public function testDifferentXmlReportsChanges()
    {
        $previous = '<root><a>1</a></root>';
        $current = '<root><a>2</a></root>';
        $this->seedStoredXml($this->journalId, $previous);
        $detector = new PreservationChangeDetector($this->journalId);
        $this->assertTrue($detector->hasChanges($current), 'Different XML content should report changes');
    }

    public function testWhitespaceDifferencesAreConsideredChanges()
    {
        $pretty = "<root>\n  <a>1</a>\n</root>\n";
        $compact = '<root><a>1</a></root>';
        $this->seedStoredXml($this->journalId, $pretty);
        $detector = new PreservationChangeDetector($this->journalId);
        $this->assertTrue($detector->hasChanges($compact), 'Whitespace differences change MD5 and are treated as changes');
    }
}
