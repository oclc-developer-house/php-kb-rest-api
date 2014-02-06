<?php

namespace OCLC;

class Search
{
	/**
	 * A generic class that represents a Search Object
	 * 
	 * @author Karen A. Coombs <coombsk@oclc.org>
	 * 
	 * @var string $wskey
	 * @var string $accessToken
	 * @var \OCLC\User $user an /OCLC/User object which contains a valid principalID, principalIDNS and institution ID for a user
	 * @method string $method
	 * @var array $requestParameters
	 * @var string $requestUrl
	 * @var string $headers
	 * @var string $acceptType
	 * @var string $mockResponseFilePath
	 * @var string $responseCode
	 * @var binary $responseOk
	 * @var string $responseBody
	 * @var string $eTag
	 * @var array $resultSet
	 * @var integer $totalResults
	 * @var integer $totalPages
	 * @var integer $currentPage
	 * @var integer $itemsPerPage
	 * @var string $errorCode
	 * @var string $errorMessage
	 * @var string $errorDetail
	 */
	
	protected $wskey;
	protected $accessToken = null;
	protected $authObject = null;
    protected $user = null;
    
    protected $method = 'GET';
    protected $requestParameters = null;
    protected $requestUrl = null;
    protected $headers = null;
    
    protected $acceptType = 'application/atom+xml';
    protected $mockResponseFilePath = null;
    
	protected $responseCode = null;
	protected $responseOk = null;
	protected $responseBody = null;
	protected $etag = null;
	
	protected $resultSet = null;
	protected $totalResults = null;
	protected $totalPages = null;
	protected $currentPage = null;
	protected $itemsPerPage = null;
	
	protected $errorCode = null;
	protected $errorMessage = null;
	protected $errorDetail = null;
	
	/**
	 * Create standard getters and setters for all the properties
	 *
	 * @param string $method
	 * @param array $params
	 * @throws \Exception
	 */
	
	public function __call($method, $params) {
		if (strlen($method) > 4) {
			$action = substr($method, 0, 3);
			$property = strtolower(substr($method, 3, 1)) . substr($method, 4);
			if ($action == 'get' && property_exists($this, $property)) {
				return $this->$property;
			}
	
			if ($action == 'set' && property_exists($this, $property)) {
				if (empty($params[0])) {
					Throw new \Exception('You must send a valid ' . $property);
				} else {
					$this->$property = $params[0];
					return;
				}
				throw new \Exception($method . ' missing required parameter');
			}
		} else {
			throw new \Exception('Call to Undefined Method/Class Function');
		}
	}
	
	/**
	 * 
	 * @param array $options
	 * - wskey: an WSKey object with a key and secret
	 * - accessToken: an AccessToken object
	 * - user: a user object
	 * - parameters: an array of query parameters
	 * - acceptType: the media type to send in the HTTP Accept Header. eg. application/json, application/atom+xml
	 * - mockResponseFilePath: the file path to a mock response you want to use for testing purposes
	 */
	public function __construct($options){
		self::parseOptions($options);
	}
	
	/**
	 * 
	 * @param array $options
	 * - wskey: an WSKey object with a key and secret
	 * - accessToken: an AccessToken object
	 * - user: a user object
	 * - parameters: an array of query parameters
	 * - acceptType: the media type to send in the HTTP Accept Header. eg. application/json, application/atom+xml
	 * - mockResponseFilePath: the file path to a mock response you want to use for testing purposes
	 */
	
	public function parseOptions($options) {
		if (isset($options['accessToken'])){
			$this->accessToken = $options['accessToken'];
			$this->authObject = $options['accessToken'];
		}elseif (isset($options['wskey'])){
			$this->wskey = $options['wskey'];
			$this->authObject = $options['wskey'];
		}
	
		if (isset($options['user'])) {
			$this->user = $options['user'];
		}
	
		if (isset($options['parameters'])) {
			foreach ($options['parameters'] as $name => $value) {
				$this->requestParameters[$name] = $value;
			}
		}
		 
		if (isset($options['mockResponseFilePath'])){
			$this->mockResponseFilePath = $options['mockResponseFilePath'];
		}
	
	}
	
	/**
	 *
	 * @param unknown $response
	 * @return \OCLC\Search
	 */
	public function parseSearchResponse($response, $class) {
	if (!is_a($response, '\Guzzle\Http\Exception\BadResponseException')) {
			self::parseSuccess($response, $class);
		} else {
			self::parseError($response->getResponse(), $class);
		}
	}
	
	/**
	 * Parse the Guzzle Response into properties for successes
	 * @param unknown $response
	 */
	
	protected function parseSuccess($response, $class) {
		$this->responseCode = $response->getStatusCode();
		$this->responseBody = $response->getBody(true);
		$this->responseSuccessful = true;
		$etag = $response->getETag();
		if (isset($etag)) {
			$this->eTag = $etag;
		}
			
		//check to see if it is XML or not
		libxml_use_internal_errors(true);
		simplexml_load_string($response->getBody(true));
			
		if (empty($this->responseBody) || count(libxml_get_errors()) > 0) {
			$isXML = FALSE;
		} else {
			$isXML = TRUE;
		}
		libxml_clear_errors();
		if ($isXML) {
			self::from_xml($response->getBody(true), $class);
		} else {
			self::from_json($response->getBody(true), $class);
		}
	}
	
	/**
	 * Parse the Guzzle Response into properties for errors
	 * @param \Guzzle\Http\Response $response
	 */
	
	protected function parseError($response, $class){
		$this->responseCode = $response->getStatusCode();
		$this->responseBody = $response->getBody(true);
		//check to see if it is XML or not
		libxml_use_internal_errors(true);
		simplexml_load_string($response->getBody(true));
			
		if (empty($this->responseBody) || count(libxml_get_errors()) > 0) {
			$isXML = FALSE;
		} else {
			$isXML = TRUE;
		}
		libxml_clear_errors();
		if ($isXML) {
			$errors = simplexml_load_string($this->responseBody);
			$errors->registerXPathNamespace("oclc", "http://worldcat.org/xmlschemas/response");
			$code = $errors->xpath('//oclc:code');
			$message = $errors->xpath('//oclc:message');
			$detail = $errors->xpath('//oclc:detail');
			if (isset($code[0])) {
				$this->errorCode = (string)$code[0];
			}
			if (isset($message[0])){
				$this->errorMessage = (string)$message[0];
			}
			if (isset($detail[0])){
				$this->errorDetail = (string)$detail[0];
			}
		} else {
			$errors = json_decode($this->responseBody);
			$this->errorCode = $errors['error'][0]['code'];
			$this->errorMessage = $errors['error'][0]['message'];
			$this->errorDetail = $errors['error'][0]['detail'];
		}
	}
	
	/**
	 * 
	 * @param string $responseBody
	 * @param string $class
	 */
	protected function from_xml($responseBody, $class)
	{
		if (empty($this->responseBody)){
			$this->responseBody = $responseBody;
		}
		$results = simple_xml_load($this->responseBody);
		if ($results->getName() == 'feed') {
			self::parseAtomFeedXML($results, $class);
		} else {
			self::parseSRUResponse($results, $class);	
		}
	}
	
	
	/**
	 * Parse the information in the Atom Feed and add each entry as an object into a result set array within the \OCLC\Search object
	 * @param \SimpleXMLElement $results
	 */
	
	protected function parseAtomFeedXML($results, $class) {
		$results->registerXPathNamespace("atom", "http://www.w3.org/2005/Atom");
		$results->registerXPathNamespace("os", "http://a9.com/-/spec/opensearch/1.1/");
			
		// want an array of resource objects with their XML
		$entries = array();
		foreach ($results->xpath('/atom:feed/atom:entry') as $entry) {
			// create a new resource using class name
			$resource = new $class();
			$resource->from_xml($entry->saveXML());
			$entries[] = $resource;
		}
		$this->resultSet = $entries;
			
		// set the currentPage
		$currentPage = $results->xpath('/atom:feed/os:startIndex');
		$this->currentPage = (string)$currentPage[0];
		//set the total results
		$totalResults = $results->xpath('/atom:feed/os:totalResults');
		$this->totalResults = (string)$totalResults[0];
		// set the items per page
		$itemsPerPage = $results->xpath('/atom:feed/os:itemsPerPage');
		if ((string)$totalResults[0]  == 0) {
			$itemsPerPage = 0;
			$totalPages = 0;
		} elseif((string)$totalResults[0] < (string)$itemsPerPage[0]) {
			$itemsPerPage = (string)$totalResults[0];
			$totalPages = 1;
		} else {
			$totalPages = (string)$totalResults[0] /(string)$itemsPerPage[0];
			$itemsPerPage = (string)$itemsPerPage[0];
		}
		$this->itemsPerPage = (string)$itemsPerPage[0];
		// calculate and set the total # of pages
		$this->totalPages = $totalPages;
	}
	
	/**
	 * Parse the information in the SRU response and add each record as an object into a result set array the \OCLC\Search object
	 * @param \SimpleXMLElement $results
	 */
	
	protected function parseSRUResponse($results, $class) {
		$results->registerXPathNamespace("sru", "http://www.loc.gov/zing/srw/");
		// want an array of resource objects with their XML
		$records = array();
		foreach ($results->xpath('//sru:record/sru:recordData') as $record) {
			$resource = new $class;
			$resource->from_xml($record->saveXML());
			$records[] = $resource;
		}
		$search_response->setResultSet($records);
			
		// set the currentPage
		$currentPage = $results->xpath('//sru:startRecord');
		if (count($currentPage) > 0) {
			$search_response->setCurrentPage((string)$currentPage[0]);
		}
		//set the total results
		$totalResults = $results->xpath('//sru:numberOfRecords');
		$search_response->setTotalResults((string)$totalResults[0]);
	
		// set the items per page
		$itemsPerPage = $results->xpath('//sru:maximumRecords');
	
		if ((string)$totalResults[0] == 0) {
			$itemsPerPage = 0;
			$totalPages = 0;
		} elseif(count($itemsPerPage) > 0 && (string)$totalResults[0] < (string)$itemsPerPage[0]) {
			$itemsPerPage = (string)$totalResults[0];
			$totalPages = 1;
		} elseif (count($itemsPerPage) > 0) {
			$totalPages = (string)$totalResults[0] /(string)$itemsPerPage[0];
			$itemsPerPage = (string)$itemsPerPage[0];
		} elseif ((string)$totalResults[0] == 1) {
			$itemsPerPage = 1;
			$totalPages = 1;
		}
		$search_response->setItemsPerPage($itemsPerPage);
		// calculate and set the total # of pages
		$search_response->setTotalPages($totalPages);
	}
	
	protected function from_json($responseBody, $class){
		if (empty($this->responseBody)){
			$this->responseBody = $responseBody;
		}
		$results = json_decode($this->responseBody, true);
		self::parseAtomFeedJSON($results, $class);
	}
	
	protected function parseAtomFeedJSON($results, $class) {
		
		//This needs more work!!
			
		// want an array of resource objects with their JSON
		$entries = array();
		foreach ($results['entry'] as $entry) {
			// create a new resource using class name
			$resource = new $class();
			$resource->from_json($entry->saveXML());
			$entries[] = $resource;
		}
		$this->resultSet = $entries;
			
		// set the currentPage
		$this->currentPage = $results['os:startIndex'];
		//set the total results
		$this->totalResults = $results['os:totalResults'];
		// set the items per page
		$itemsPerPage = $results['os:itemsPerPage'];
		if ((string)$totalResults  == 0) {
			$itemsPerPage = 0;
			$totalPages = 0;
		} elseif((string)$totalResults < (string)$itemsPerPage) {
			$itemsPerPage = (string)$totalResults;
			$totalPages = 1;
		} else {
			$totalPages = (string)$totalResults /(string)$itemsPerPage;
			$itemsPerPage = (string)$itemsPerPage;
		}
		$this->itemsPerPage = (string)$itemsPerPage;
		// calculate and set the total # of pages
		$this->totalPages = $totalPages;
	}
}
