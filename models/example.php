<?php if (!defined('DIRECTSCRIPT')) exit('No direct script access allowed');

class Example extends Model {

	function Example() {
		parent::Model();
		$this->load(array('user'));
		$this->encrypt_fields = array('password');
	}
	
}
?>