<?php if (!defined('DIRECTSCRIPT')) exit('No direct script access allowed');

class Security {

	function Security(){
		$this->mcrypt_cipher = $GLOBALS['security']['mcrypt']['cipher'] || MCRYPT_RIJNDAEL_256;
		$this->mcrypt_mode = $GLOBALS['security']['mcrypt']['mode'] || MCRYPT_MODE_ECB;
		$this->key = $GLOBALS['security']['key'];
	}
	
	function _docrypt($fields, &$rec, $encrypt){
		if ($fields){
			foreach($fields as $field){
				if (isset($rec[$field]) && $rec[$field] != ''){
					if ($encrypt){
						$this->_encrypt_data($rec[$field]);
					} else {
						$this->_decrypt_data($rec[$field]);
					}
				}
			}
		}
	}
	
	function _encrypt_data(&$value){
		$text = $value;
		$iv_size = mcrypt_get_iv_size($this->mcrypt_cipher, $this->mcrypt_mode);
		$iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);
		$value = mcrypt_encrypt($this->mcrypt_cipher, $this->key, $text, $this->mcrypt_mode, $iv);
	} 

	function _decrypt_data(&$value){
		$crypttext = $value;
		$iv_size = mcrypt_get_iv_size($this->mcrypt_cipher, $this->mcrypt_mode);
		$iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);
		$value = trim(mcrypt_decrypt($this->mcrypt_cipher, $this->key, $crypttext, $this->mcrypt_mode, $iv));
	}

	function encrypt($fields, &$rec){
		$this->_docrypt($fields, $rec, true);
	}

	function decrypt($fields, &$rec){
		$this->_docrypt($fields, $rec, false);
	}

}

?>