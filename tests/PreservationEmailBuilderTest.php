<?php

use PHPUnit\Framework\TestCase;

import('classes.journal.Journal');
import('plugins.generic.carinianaPreservation.classes.PreservationEmailBuilder');

class PreservationEmailBuilderTest extends TestCase
{
    private $preservationEmailBuilder;
    private $journal;
    private $locale = 'pt_BR';
    private $journalAcronym = 'RBRB';
    private $journalContactEmail = 'contact@rbrb.com.br';
    private $preservationName = 'Preservacao Cariniana';
    private $preservationEmail = 'destino.cariniana@gmail.com';

    public function setUp(): void {
        $this->createTestJournal();
        $this->preservationEmailBuilder = new PreservationEmailBuilder();
    }

    private function createTestJournal(): void
    {
        $this->journal = new Journal();
        $this->journal->setData('acronym', $this->journalAcronym, $this->locale);
        $this->journal->setData('contactEmail', $this->journalContactEmail);
    }

    public function testBuildsPreservationEmail(): void
    {
        $email = $this->preservationEmailBuilder->buildPreservationEmail($this->journal, $this->preservationName, $this->preservationEmail, $this->locale);
        
        $expectedFrom = ['name' => $this->journalAcronym, 'email' => $this->journalContactEmail];
        $this->assertEquals($expectedFrom, $email->getData('from'));
        
        $expectedRecipient = ['name' => $this->preservationName, 'email' => $this->preservationEmail];
        $this->assertEquals($expectedRecipient, $email->getData('recipients')[0]);
        
        $expectedSubject = __('plugins.generic.carinianaPreservation.preservationEmail.subject', ['journalAcronym' => $this->journalAcronym], $this->locale);
        $this->assertEquals($expectedSubject, $email->getData('subject'));

        $expectedBody = __('plugins.generic.carinianaPreservation.preservationEmail.body', ['journalAcronym' => $this->journalAcronym], $this->locale);
        $this->assertEquals($expectedBody, $email->getData('body'));
    }
}