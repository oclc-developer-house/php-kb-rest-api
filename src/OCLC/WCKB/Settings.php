<?php

namespace OCLC\WCKB;

class Settings
{

    protected $wskey;
    protected $institution_id;
    protected $data = array();

    public function __construct( $wskey, $institution_id ) {
        $this->setWskey( $wskey );
        $this->setInstitutionId( $institution_id );
    }

    public function getInstitutionId() {
        return $this->institution_id;
    }

    public function setInstitutionId( $institution_id ) {
        $this->institution_id = $institution_id; 
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
        return $this->data[ $var ];
    }

}
