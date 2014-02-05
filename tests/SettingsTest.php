<?php

use OCLC\WCKB\Settings;

class SettingsTest extends PHPUnit_Framework_TestCase {

    /**
     * @returns $settings;
     */
    public function testConstructor() {
        $settings = new Settings( 'abcdef', 123456 );
        $this->assertEquals( $settings->getInstitutionId(), 123456 );
        $this->assertEquals( $settings->getWskey(), 'abcdef' );
        $this->assertCount( 0, $settings->getPropertyNames());
        return $settings;
    }
    
    /**
     * @depends testConstructor
     * @return $settings
     */
    public function testLoadProperties($settings) {
    	return $settings;
    }

    /**
     * @depends testLoadProperties
     */
    public function xtestExpectedProperties($settings) {
        $setKeys = $settings->getPropertyNames();
    	$keys = array(
          "institution_name",
          "institution_id",
          "download_ip",
          "preferred_oclc_symbol",
          "google_scholar_enabled",
          "wcsync_enabled",
          "marcdelivery_enabled",
          "marcdelivery_no_delete",
          "eswitch_enabled",
          "eswitch_eligible",
          "article_filter_enabled",
          "oclc_symbols",
          "openaccess_in_resolver",
          "selected_collections",
          "galesiteid"
        );
        foreach($keys as $key) {
    	   $this->assertArrayHasKey($key, $setKeys);
        }
    	
    }

}
