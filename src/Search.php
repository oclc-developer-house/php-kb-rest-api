<?php
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
	 * @var string $request_url
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
	
	protected $wskey = null;
    protected $accessToken = null;
    protected $user = null;
    
    protected $method = 'GET';
    protected $requestParameters = null;
    protected $request_url = null;
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
		}elseif (isset($options['wskey'])){
			$this->wskey = $options['wskey'];
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
	
	public function buildAuthorizationHeader()
	{
		if (isset($this->accessToken)){
			$authHeader = 'Bearer ' . $this->accessToken->getValue();
			if (isset($this->user)){
				$this->authHeader .= ', principalID="' . $this->user->getPrincipalID() . '", principalIDNS="' . $this->user->getPrincipalIDNS() . '"';
			}
		} elseif (isset($this->wskey)){
			$options = array(
					'user' => $this->user
			);
			$authHeader = $this->wskey->getHMACSignature($this->method, $this->request_url, $options);
		} else {
			Throw new Exception('You must pass either a wskey or an accessToken Object in the options');
		}
		return $authHeader;
	}
	
	/**
	 *
	 * @param unknown $response
	 * @return \OCLC\Search
	 */
	public function parseSearchResponse($response) {
		if (!is_a($response, '\Guzzle\Http\Exception\BadResponseException')) {
			$this->responseOk = $response->isSuccessful();
			$this->responseCode = $response->getStatusCode();
			$this->responseBody = $response->getBody(true);
			$etag = $response->getETag();
			if (!empty($etag)){
				$this->etag($etag);
			}
			// figure out if it is Atom or not based on namespaces
			$results = simplexml_load_string($search->getResponseBody());
			$namespaces = $results->getNamespaces(true);
	
			if (in_array('http://www.loc.gov/zing/srw/', $namespaces)) {
				static::parseSRUResponse($results, $search);
			} else {
				self::parseAtomFeed($results);
			}
	
		} else {
			$this->responseCode = $response->getResponse()->getStatusCode();
			$this->responseBody = $response->getResponse()->getBody(true);
		}
	}
	
	/**
	 * Parse the information in the Atom Feed and add each entry as an object into a result set array within the \OCLC\Search object
	 * @param \SimpleXMLElement $results
	 */
	
	protected function parseAtomFeed($results) {
		$results->registerXPathNamespace("atom", "http://www.w3.org/2005/Atom");
		$results->registerXPathNamespace("os", "http://a9.com/-/spec/opensearch/1.1/");
			
		// want an array of resource objects with their XML
		$entries = array();
		foreach ($results->xpath('/atom:feed/atom:entry') as $entry) {
			// create a new resource using class name
			$class = get_called_class();
			$resource = new $class;
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
	
	protected function parseSRUResponse($results, $search_response) {
		$results->registerXPathNamespace("sru", "http://www.loc.gov/zing/srw/");
		// want an array of resource objects with their XML
		$records = array();
		foreach ($results->xpath('//sru:record/sru:recordData') as $record) {
			$class = get_called_class();
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
}