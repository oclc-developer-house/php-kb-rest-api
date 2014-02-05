<?php

namespace OCLC\WCKB;

class Settings extends \OCLC\Resource
{

    protected $data = array();

    public function __construct( $wskey, $institution_id ) {
    	parent::__construct(
    	  $institution_id, 
    	  array(
            "wskey" => $wskey,
		    "service_url" => 'http://worldcat.org/webservices/kb/rest',
		    "object_path" => '/settings',
		    "dataURLsyntax" => false,
		    "supportedAuthenticationMethods" => array('WSKeyLite')
          )
        );
    }

    public function getInstitutionId() {
        return $this->id;
    }

    public function setInstitutionId( $institution_id ) {
        $this->id = $institution_id; 
    }

    public function getWskey() {
        return $this->wskey;
    }

    public function setWskey( $wskey ) {
        $this->wskey = $wskey; 
    }

	protected function setProperty( $var, $default = '' ) {
		$this->data[ $var ] = $default;
	}

    public function getProperty( $var ) {
    	if (!isset($this->data[$var])) {
    		return null;
    	}
        return $this->data[ $var ];
    }
    
    public function getPropertyNames() {
    	return array_keys($this->data);
    }

}
