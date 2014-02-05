<?php

use OCLC\WCKB\Settings;

class SettingsTest extends PHPUnit_Framework_TestCase {

    public function testConstructor() {
        $settings = new Settings( 'abcdef', 123456 );
        $this->assertEquals( $settings->getInstitutionId(), 123456 );
        $this->assertEquals( $settings->getWskey(), 'abcdef' );
    }

}
