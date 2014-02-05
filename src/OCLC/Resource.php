<?php

namespace OCLC;

use Guzzle\Http\Client;
use Guzzle\Plugin\History\HistoryPlugin;
use Guzzle\Plugin\Mock\MockPlugin;

Class Resource
{
	/**
	 * A generic class that represents an OCLC Resource.
	 * Optionally, it can include principal information in the form of a principal ID and IDNS that represent an user and a redirect URI to be used in the OAuth 2 login flows.
	 * The Resource class is used to
	 * - construct a Resource
	 * - read a Resource
	 * - create a Resource
	 * - update a Resource
	 * - delete a Resource
	 *
	 * @author Karen A. Coombs <coombsk@oclc.org>

	 * @var string $service_url
	 * @var string $object_path
	 * @var binary $dataURLsyntax
	 * @var string $nsURL
	 * @var array $supportedAuthenticationMethods
	 * @var string $wskey
	 * @var string $accessToken
	 * @var \OCLC\User $user an /OCLC/User object which contains a valid principalID, principalIDNS and institution ID for a user
	 * @var string $id
	 * @var array $requestParameters
	 * @var string $requestUrl
	 * @var string $headers
	 * @var string $acceptType
	 * @var string $mockResponseFilePath
	 * @var string $responseCode
	 * @var binary $responseOk
	 * @var string $eTag
	 * @var string $responseBody
	 * @var string $doc
	 * @var string $atomTitle
	 * @var string $atomLink
	 * @var string $errorCode
	 * @var string $errorMessage
	 * @var string $errorDetail
	 *
	 */

	public static $service_url;
	public static $object_path;
	public static $dataURLsyntax = true;
	public static $nsURL;
	public static $supportedAuthenticationMethods = array('HMAC', 'AccessToken');

	protected $wskey;
	protected $accessToken = null;
	protected $authObject = null;
	protected $user = null;

	protected $id;
	protected $requestParameters;
	protected $requestUrl;
	protected $headers;
	protected $acceptType = 'application/atom+xml';
	protected $mockResponseFilePath;

	protected $responseCode;
	protected $responseOk = FALSE;
	protected $eTag = null;
	protected $responseBody;
	protected $doc;
	protected $atomTitle = null;
	protected $atomLink = null;

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
	 * Construct an OCLC Resource Object
	 *
	 * @param string $id
	 * @param array $options
	 * - wskey: an WSKey object with a key and secret
	 * - accessToken: an AccessToken object
	 * - user: a user object
	 * - parameters: an array of query parameters
	 * - acceptType: the media type to send in the HTTP Accept Header. eg. application/json, application/atom+xml
	 * - mockResponseFilePath: the file path to a mock response you want to use for testing purposes
	 */
	public function __construct($id = null, $options = null)
	{
		if (isset($id)){
			$this->id = $id;
		}
	  
		if (is_array($options)) {
			self::parseOptions($options);
		}
		if (isset($this->id) and (isset($this->wskey) || isset($this->accessToken) || isset($this->requestParameters['wskey']))){
			self::get();
		}
	}

	/**
	 * Get a Resource via HTTP
	 *
	 * @param array $options
	 * - wskey: an WSKey object with a key and secret
	 * - accessToken: an AccessToken object
	 * - user: a user object
	 * - parameters: an array of query parameters
	 * - acceptType: the media type to send in the HTTP Accept Header. eg. application/json, application/atom+xml
	 * - mockResponseFilePath: the file path to a mock response you want to use for testing purposes
	 */

	public function get($options = null)
	{
		if (is_array($options)) {
			self::parseOptions($options);
		}
			
		$this->method = 'GET';

		$this->requestUrl = static::buildRequestURL(__FUNCTION__, $this->id, $this->requestParameters);
			
		$this->headers = array(
				'Accept' => $this->acceptType
		);
			
		if (in_array('HMAC', static::$supportedAuthenticationMethods) || in_array('AccessToken', static::$supportedAuthenticationMethods)){
			$this->authHeader = self::buildAuthorizationHeader($this->authObject, $this->method, $this->requestUrl, $this->user);
			$this->headers['Authorization'] = $this->authHeader;
		}

		$httpOptions = array('mockResponseFilePath' => $this->mockResponseFilePath);

		self::parseResponse(static::makeHTTPRequest($this->method, $this->requestUrl, $this->headers, $httpOptions));
			
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

	public static function search($options = null){
		$search = new Search($options);
		
		$requestUrl = static::buildRequestURL(__FUNCTION__, null, $search->getRequestParameters());
		$search->setRequestUrl($requestUrl);

		$headers = array(
				'Accept' => $search->getAcceptType(),
		);

		if (in_array('HMAC', static::$supportedAuthenticationMethods) || in_array('AccessToken', static::$supportedAuthenticationMethods)){
			$search->setAuthHeader($search->buildAuthorizationHeader($search->getAuthObject(), $search->getMethod(), $search->getRequestUrl(), $search->getUser()));
			$headers['Authorization'] = $search->getAuthHeader();
		}

		$search->setHeaders($headers);

		$httpOptions = array(
				'mockResponseFilePath' => $search->getMockResponseFilePath()
		);

		$search->parseSearchResponse(static::makeHTTPRequest($search->getMethod(), $search->getRequestUrl(), $search->getHeaders(), $httpOptions), get_called_class());
		return $search;
	}

	/**
	 * Parse options into object properties
	 * @param array $options
	 * - wskey: an WSKey object with a key and secret
	 * - accessToken: an AccessToken object
	 * - user: a user object
	 * - parameters: an array of query parameters
	 * - acceptType: the media type to send in the HTTP Accept Header. eg. application/json, application/atom+xml
	 * - mockResponseFilePath: the file path to a mock response you want to use for testing purposes
	 */
	protected function parseOptions($options) {

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
		if (isset($options['acceptType'])){
			$this->acceptType = $options['acceptType'];
		}

		if (isset($options['mockResponseFilePath'])){
			$this->mockResponseFilePath = $options['mockResponseFilePath'];
		}

	}
	/**
	 * Build the full request URL with object id and parameters if necessary
	 *
	 * @param string $method
	 * @param string $id
	 * @param array $parameters
	 * @param string $action
	 * @return string
	 */

	private static function buildRequestURL($method, $id = null, $parameters = null, $action = null) {
		$requestUrl = static::buildObjectController($method);

		if (isset($id)) {
			$requestUrl .= '/' . $id;
		}
		if (isset($action)) {
			$requestUrl .= '/' . $action;
		}
		$requestUrl .= self::buildQuery($parameters);

		return $requestUrl;
	}

	/**
	 * Build the base url for the object and the controller
	 *
	 * @param string $method
	 * @return string
	 */

	private static function buildObjectController($method){
		if (static::$dataURLsyntax){
			if ($method == 'search') {
				$requestUrl = static::$service_url . static::$object_path . '/search';
			} else {
				$requestUrl = static::$service_url . static::$object_path . '/data';
			}
		} else {
			$requestUrl = static::$service_url . static::$object_path;
		}
		return $requestUrl;
			
	}

	/**
	 * Build the query string for the url
	 *
	 * @param array $parameters
	 * @return string
	 */
	private static function buildQuery($parameters = null)
	{
		$all_params = array();

		if (is_array($parameters)) {
			$all_params = array_merge_recursive($all_params, $parameters);
		}

		if (count($all_params) > 0) {
			$querystring = '?' . http_build_query($all_params, '', '&');
		} else {
			$querystring = '';
		}
		return $querystring;
	}

	/**
	 * Build an Authorization Header based on information in object
	 * @throws \Exception
	 */

	public static function buildAuthorizationHeader($authObject, $method, $requestUrl, $user = null)
	{
		if (is_a($authObject, '\OCLC\Auth\AccessToken')){
			$authHeader = 'Bearer ' . $authObject->getValue();
			if (isset($user)){
				$authHeader .= ', principalID="' . $this->user->getPrincipalID() . '", principalIDNS="' . $this->user->getPrincipalIDNS() . '"';
			}
		} elseif (is_a($authObject, '\OCLC\Auth\WSKey')){
			$options = array(
					'user' => $user
			);
			$authHeader = $authObject->getHMACSignature($method, $requestUrl, $options);
		} else {
			Throw new \Exception('You must pass either a wskey or an accessToken Object as the authObject');
		}
		return $authHeader;
	}

	/**
	 *
	 * @param string $method
	 * @param string $requestUrl
	 * @param array $headers
	 * @param array $options
	 * @return \Guzzle\Http\Exception\BadResponseException
	 */

	public static function makeHTTPRequest($method, $requestUrl, $headers, $options = null)
	{
		(isset($options['requestBody']) ? $requestBody = $options['requestBody'] : $requestBody = null);
		(isset($options['upload']) ? $upload = $options['upload'] : $upload = false);
		(isset($options['mockResponseFilePath']) ? $mockResponseFilePath = $options['mockResponseFilePath'] : $mockResponseFilePath = null);
	  
		if (defined('USER_AGENT')) {
			$userAgent = USER_AGENT;
		} else {
			$userAgent = 'OCLC Platform PHP Library';
		}

		$client = new Client();
		$client->setDefaultOption('timeout', 60);
		$client->setDefaultOption('redirect.strict', true);

		$history = new HistoryPlugin();
		$history->setLimit(1);
		$client->addSubscriber($history);
		$client->setuserAgent($userAgent);
		if (isset($mockResponseFilePath)) {
			$plugin = new MockPlugin();
			$client->addSubscriber($plugin);
			$plugin->addResponse($mockResponseFilePath);
		}

		if ($method == 'POST' and $upload == true) {
			$request = $client->post($requestUrl, $headers, array(
					'file_field' => $requestBody
			));
		} elseif ($method == 'POST' || $method =='PUT' || !empty($requestBody)) {
			$request = $client->createRequest($method, $requestUrl, $headers, $requestBody);
		} else {
			$request = $client->createRequest($method, $requestUrl, $headers);
		}
		$request->getCurlOptions()->set(CURLOPT_SSL_VERIFYHOST, false);
		$request->getCurlOptions()->set(CURLOPT_SSL_VERIFYPEER, false);

		try {
			$response = $request->send();
		} catch (\Guzzle\Http\Exception\BadResponseException $error) {
			$response = $error;
		}
		return $response;
	}

	/**
	 *
	 * @param unknown $response
	 */
	protected function parseResponse($response) {
		if (!is_a($response, '\Guzzle\Http\Exception\BadResponseException')) {
			self::parseSuccess($response);
		} else {
			self::parseError($response->getResponse());
		}
	}

	/**
	 * Parse the Guzzle Response into properties for successes
	 * @param unknown $response
	 */

	protected function parseSuccess($response) {
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
			$this->from_xml($response->getBody(true));
		} else {
			$this->from_json($response->getBody(true));
		}
	}

	/**
	 * Parse the Guzzle Response into properties for errors
	 * @param \Guzzle\Http\Response $response
	 */

	protected function parseError($response){
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
	 * Parse the object response either as an Atom Entry or other XML document
	 * @param string $response
	 */

	protected function from_xml($response) {
		$this->responseBody = $response;

		$entry = simplexml_load_string($this->responseBody);

		if ($entry->getName() == 'entry') {
			$namespaces = $entry->getNamespaces(true);

			if (isset($namespaces['gd'])) {
				$gd = $entry->children($namespaces['gd']);

				//set Etag
				if (isset($gd->etag)) {
					$this->eTag = (string)$gd->etag;
				} else {
					$this->eTag = (string)$gd['etag'];
				}
			}
			$this->atomTitle = (string)$entry->title;
			$this->atomLink = $entry->link['href'];
			if (count($entry->xpath('//content/child::*')) > 0) {
				$doc = $entry->xpath('//content/child::*');
			} else {
				$entry->registerXPathNamespace('atom', 'http://www.w3.org/2005/Atom');
				$doc = $entry->xpath('//atom:content/child::*');
			}
			if (isset($doc[0])) {
				$this->from_doc($doc[0]->asXML());
			}
				
		} else {
			$this->from_doc($response);
		}
	}

	protected function from_json($response){
		$this->responseBody = $response;
		$json_atom = json_decode($this->responseBody, true);

		if (isset($json_atom['id'])) {
			if (isset($json_atom['etag'])){
				$this->eTag = $json_atom['etag'];
			}
			$this->atomTitle = $json_atom['title'];
			foreach ($json_atom['links'] as $link) {
				if ($link['rel'] == 'self') {
					$this->atomLink = $link['href'];
				}
			}
			if (isset($json_atom['content'])){
				$doc = $json_atom['content'];
				$this->from_doc($doc);
			}
		} else {
			$this->from_doc($response);
		}
	}

	protected function from_doc($doc){
		$this->doc = $doc;
	}
}
