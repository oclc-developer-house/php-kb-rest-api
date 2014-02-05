<?php
use \OCLC\Resource;

class ResourceTest extends PHPUnit_Framework_TestCase {

	public function setup(){
		Resource::$service_url = 'http://worldcat.org/webservices/kb/rest';
		Resource::$object_path = '/settings';
		Resource::$dataURLsyntax = false;
		Resource::$supportedAuthenticationMethods = array('WSKeyLite');
	}
	public function testConstructorWithIDXML() {
		$options = array(
			'parameters' => array('wskey' => 'myKey'),
			'mockResponseFilePath' => __DIR__ . '/mocks/XML/200.txt'
		);
		$resource = new Resource('123', $options);
		
		// Make sure the id is set
		$this->assertAttributeEquals('123', 'id', $resource);

		// make sure the parameters are set
		$this->assertAttributeNotEmpty('requestParameters', $resource);
		$parameters = $resource->getRequestParameters();
		$this->assertEquals('myKey', $parameters['wskey']);
		
		// make sure the url gets built right
		$this->assertAttributeEquals('http://worldcat.org/webservices/kb/rest/settings/123?wskey=myKey', 'requestUrl', $resource);
		
		//make sure mockResponseFilePath is set
		$this->assertAttributeEquals(__DIR__ . '/mocks/XML/200.txt', 'mockResponseFilePath', $resource);
		
		//make sure the accept header is set right
		$this->assertAttributeEquals('application/atom+xml', 'acceptType', $resource);
		
		//make sure the HTTP request returns a 200
		$this->assertAttributeEquals('200', 'responseCode', $resource);
		$this->assertAttributeNotEmpty('responseBody', $resource);
		$this->assertAttributeEmpty('errorCode', $resource);
		
		return $resource;
	}
	
	/**
	 * @depends testConstructorWithIDXML
	 */
	public function testParseXML($resource){
		$this->assertAttributeNotEmpty('id', $resource);
		$this->assertAttributeNotEmpty('atomTitle', $resource);
		$this->assertAttributeNotEmpty('atomLink', $resource);
		//based on the service that is called, this may or may not get set
		//$this->assertAttributeNotEmpty('doc', $resource);
	}
	
	public function testConstructorWithIDJSON() {
		$options = array(
				'parameters' => array('wskey' => 'myKey', 'alt' => 'json'),
				'acceptType' => 'application/json',
				'mockResponseFilePath' => __DIR__ . '/mocks/JSON/200.txt'
		);
		$resource = new Resource('123', $options);
	
		// Make sure the id is set
		$this->assertAttributeEquals('123', 'id', $resource);
	
		// make sure the parameters are set
		$this->assertAttributeNotEmpty('requestParameters', $resource);
		$parameters = $resource->getRequestParameters();
		$this->assertEquals('myKey', $parameters['wskey']);
	
		// make sure the url gets built right
		$this->assertAttributeEquals('http://worldcat.org/webservices/kb/rest/settings/123?wskey=myKey&alt=json', 'requestUrl', $resource);
	
		//make sure mockResponseFilePath is set
		$this->assertAttributeEquals(__DIR__ . '/mocks/JSON/200.txt', 'mockResponseFilePath', $resource);
	
		//make sure the accept header is set right
		$this->assertAttributeEquals('application/json', 'acceptType', $resource);
	
		//make sure the HTTP request returns a 200
		$this->assertAttributeEquals('200', 'responseCode', $resource);
		$this->assertAttributeNotEmpty('responseBody', $resource);
		$this->assertAttributeEmpty('errorCode', $resource);
	
		return $resource;
	}
	
	/**
	 * @depends testConstructorWithIDJSON
	 */
	public function testParseJSON($resource){
		$this->assertAttributeNotEmpty('id', $resource);
		$this->assertAttributeNotEmpty('atomTitle', $resource);
		$this->assertAttributeNotEmpty('atomLink', $resource);
		//based on the service that is called, this may or may not get set
		//$this->assertAttributeNotEmpty('doc', $resource);
	}
	
	public function testSearch(){
		$options = array(
				'parameters' => array('wskey' => 'myKey', 'institution_id' => 128807),
				'mockResponseFilePath' => __DIR__ . '/mocks/XML/Search200.txt'
		);
		$searchResults = Resource::search($options);
		$this->assertAttributeEquals('http://worldcat.org/webservices/kb/rest/settings/search?wskey=myKey&institution_id=128807', 'requestUrl', $searchResults);
		
		
	}
	
	

}
