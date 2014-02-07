<?php

namespace OCLC\WCKB;
use \OCLC\Resource;

class Settings extends Resource
{

    protected $data = array();

    public function __construct( $wskey, $institution_id, $options = array() ) {
    	$options['wskey'] = $wskey;
    	parent::__construct(
    	  $institution_id, 
    	  $options
       );
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
            foreach($json_atom as $k => $v) {
       	        $m = array();
       	        if (preg_match("/^kb:(.*)$/", $k, $m)) {
                    $this->setProperty($m[1], $v);      		
       	        }
           }
		} else {
			$this->from_doc($response);
		}
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
    		return;
    	}
        return $this->data[ $var ];
    }
    
    public function getPropertyNames() {
    	return array_keys($this->data);
    }
    
    protected function foo() {return "foo2";}
    

}
