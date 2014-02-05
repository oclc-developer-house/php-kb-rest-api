<?php
require('/Users/coombsk/Documents/workspace/code_libraries/oclc-auth.phar');
require('/Users/coombsk/git/php-kb-rest-api/src/Resource.php');

class ResourceTest extends PHPUnit_Framework_TestCase {

	public function setup(){
		Resource::$service_url = 'http://worldcat.org/webservices/kb/rest';
		Resource::$object_path = '/settings';
		Resource::$dataURLsyntax = false;
		Resource::$supportedAuthenticationMethods = array('WSKeyLite');
	}
	public function testConstructorWithID() {
		$wskey  = new WSKey('key', 'secret');
		$options = array(
			'wskey' => $wskey,
			'mockResponseFilePath' => __DIR__ . '/mocks/200.txt'
		);
		$resource = new Resource('123', $options);
		
		// Make sure the id is set
		$this->assertAttributeEquals('123', 'id', $resource);

		// make sure the parameters are set
		$this->assertAttributeNotEmpty('requestParameters', $resource);
		$parameters = $resource->getRequestParameters();
		$this->assertEquals('myKey', $parameters['wskey']);
		
		// make sure the url gets built right
		$this->assertAttributeEquals('http://worldcat.org/webservices/kb/rest/settings/123?wskey=myKey', 'request_url', $resource);
		
		//make sure mockResponseFilePath is set
		$this->assertAttributeEquals(__DIR__ . '/mocks/200.txt', 'mockResponseFilePath', $resource);
		
		//make sure the authorization header is set
		
		//make sure the accept header is set right
		$this->assertAttributeEquals('application/atom+xml', 'acceptType', $resource);
		
		//make sure the HTTP request returns a 200
		$this->assertAttributeEquals('200', 'responseCode', $resource);
		$this->assertAttributeNotEmpty('responseBody', $resource);
		$this->assertAttributeEmpty('errorCode', $resource);
		
		return $resource;
	}
	
	public function testSearch(){
		
	}
	
	

}