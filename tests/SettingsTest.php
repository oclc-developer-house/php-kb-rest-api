<?php

use OCLC\WCKB\Settings;
use OCLC\Resource;

class SettingsTest extends PHPUnit_Framework_TestCase {

	public function setup(){
		Resource::$service_url = 'http://worldcat.org/webservices/kb/rest';
		Resource::$object_path = '/settings';
		Resource::$dataURLsyntax = false;
		Resource::$supportedAuthenticationMethods = array('WSKeyLite');
	}
    /**
     * @returns $settings;
     */
    public function testConstructor() {
        $settings = new Settings( 'abcdef', 123456, 
          array(
            "mockResponseFilePath" => __DIR__ . '/OCLC/mocks/json/200.txt'
          )
        );
        
        $this->assertInstanceOf("\OCLC\WCKB\Settings", $settings);
        $this->assertEquals( $settings->getInstitutionId(), 123456 );
        $this->assertEquals( $settings->getWskey(), 'abcdef' );
		$this->assertAttributeNotEmpty('responseBody', $settings);
        //$this->assertCount( 0, $settings->getPropertyNames());
        return $settings;
    }
    
    /**
     * @depends testConstructor
     */
    public function testExpectedProperties($settings) {
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
    	   $this->assertNotNull($settings->getProperty($key));
        }
        
        $this->assertNull($settings->getProperty("ffoo"));
    	
    }

}
