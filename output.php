<?php
class Output {
	
	function Output(){
		$this->results = array();
	}
	
	function send_output($type){
		switch ($type){
			case "json":
				header("Content-type: application/json", true);
				echo json_encode($this->results);
				break;
			default:
				$this->error("error", "Output content-type not allowed.");
		}
		exit();
	}
	
	function error($type='', $text=''){
		switch ($type){
			case "error":
				$this->set_header_status("400", "Bad Request");
				echo '{"error": "'.$text.'"}';
				break;
			case "unauthorized":
				$this->set_header_status("401", "Unauthorized");
				echo '{unauthorized: "'.$text.'"}';
				break;
			default:
				$this->set_header_status('400', 'Bad Request');
				echo '{"error": "Bad Request"}';
		}
		exit();
	}

	function set_header_status($code, $text) {
		header("HTTP/1.1 $code $text", true, $code);
	}

}
?>