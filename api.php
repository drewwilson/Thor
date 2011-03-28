<?php if (!defined('DIRECTSCRIPT')) exit('No direct script access allowed');

class Api {

	private static $instance;

	function Api(){
		self::$instance =& $this;
		$this->method = 'get';
		$this->allowed_methods = array('get', 'post', 'put', 'delete');
		$this->params = array();
		$this->request_uri_ignore = array('api');
		$this->request_uri_raw = '';
		$this->request_uri = array();
		$this->output = new Output();
		$this->defaults = array(
			'limit' => null,
			'offset' => null,
			'order' => null,
			'joins' => null,
			'include' => null
		);
	}

	public static function &get_instance(){
		return self::$instance;
	}

	function process_request(){
		$this->method = strtolower($_SERVER['REQUEST_METHOD']);
		if (!in_array($this->method, $this->allowed_methods)) {
			$this->output->error('error', 'Method not supported: '.$this->method);
		}
		$this->request_uri_raw = strtolower($_SERVER['REQUEST_URI']);
		$this->request_uri = explode("/", $this->request_uri_raw);
		
		foreach ($this->request_uri as $k => &$v){
			if ($v == "" || in_array($v, $this->request_uri_ignore)) {
				unset($this->request_uri[$k]);
			} else {
				if (strpos($v, "?") > -1) {
					$i = strpos($v, "?");
					$v = substr($v, 0, $i);
					if ($v == "") { unset($this->request_uri[$k]); }
				}
			}
		}
		unset($v);
		$tmp = $this->request_uri;
		$model = singular(array_shift($tmp));
		$this->load_first_model($model);
		$id = array_shift($tmp)+0;
		if ($id > 0){
			$this->params['id'] = $id;
		}
		$this->get_params();
		$this->$model->where_ary = $this->params;
		$this->$model->defaults = $this->defaults;
		
		// run the request method
		$this->$model->{$this->method}();
		
		$this->$model->push_results();
	}
	
	function load_first_model($model){
		$modelName = ucfirst($model);
		if (!file_exists('models/'.$model.'.php')){
			$this->output->error('error', 'Unable to locate the model you have specified: '.$modelName);
		}
		require_once('models/'.$model.'.php');
		$this->$model = new $modelName();
	}
	
	function get_params(){
		$params = array();
		if ($this->method == 'get'){
			$params = $_GET;
		} elseif ($this->method == 'post'){
			$params = $_POST;
		} elseif ($this->method == 'put' || $this->method == 'delete'){
			$params = $this->parse_query(file_get_contents('php://input'));
		}

		$params = array_merge($params, $this->params); // has to be in this order, that way the internal params overwrite the posted params
		$largest_array = 1;
		if (!empty($this->params)){
			$largest_array = count(max($this->params));
		}
		$this->params = array_fill(0, $largest_array, array());
		foreach ($params as $key => $val){
			if (array_key_exists($key, $this->defaults)){
				$this->defaults[$key] = $val;
			} else {
				if (is_array($val)){
					foreach ($val as $k => $v){
						$this->params[$k][$key] = $v;
					}
				} else {
					foreach ($this->params as $k => $v){
						$this->params[$k][$key] = $val;
					}
				}
			}
		}
		$this->clean_params($this->params);
	}

	function clean_params(&$params){
		foreach ($params as &$v) { 
			stripslashes_deep($v);
		}
	}

	function parse_query($str){
		$pairs = explode('&', $str);
		foreach($pairs as $pair) {
			list($name, $value) = explode('=', urldecode($pair), 2);
			$name = preg_replace('/\[|\]/', '', $name);
			if (isset($params[$name])) {
				if (!is_array($query[$name])) {
					$tmp = $query[$name];
					$params[$name] = array();
					$params[$name][] = $tmp;
				}
				$params[$name][] = $value;
			} else {
				$params[$name] = $value;
			}
		}
		return $params;
	}

}

function &get_instance(){
	return Api::get_instance();
}

?>