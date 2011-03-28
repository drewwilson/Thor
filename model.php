<?php if (!defined('DIRECTSCRIPT')) exit('No direct script access allowed');

class Model {
	
	/*
	Variables:
		where_ary = condition stack
		where_or_ary = "OR" condition stack
		where_str = SQL where string
		disabled = array of field names that aren’t update-able (will show in results)
		removals = array of field names that aren’t shown in results
		unique_fields = array of field names that need to stay unique system wide
		create_timestamps = array of field names that get a timestamp on creation
		update_timestamps = array of field names that get a timestamp on update
		results = result stack (local)
	*/
	function Model($params=array()){
		$this->_model = strtolower(get_class($this));
		$this->_table = plural($this->_model);
		$this->where_ary = array();
		$this->where_or_ary = array();
		$this->where_str = "";
		$this->disabled = array();
		$this->removals = array();
		$this->unique_fields = array();
		$this->create_timestamps = array();
		$this->update_timestamps = array();
		$this->results = array('items' => array(), 'count' => 0);
		$this->defaults = array(
			'limit' => null,
			'offset' => null,
			'order' => null,
			'joins' => null,
			'include' => null
		);
	}

	function push_results($results=array()){
		$api =& get_instance();
		if (empty($results)){
			$api->output->results[] = $this->results;
		} else {
			$api->output->results[] = $results;
		}
	}

	function _error($error, $text=''){
		$api =& get_instance();
		$api->output->error($error, $text);
	}

	function load($model){
		if (is_array($model)){
			foreach ($model as $child){
				$this->load($child);
			}
		} else {
			$modelName = ucfirst($model);
			if (!isset($this->$model)){
				if (!file_exists('models/'.$model.'.php')){
					$this->_error('error', 'Unable to locate the model you have specified: '.$modelName);
				}
				require_once('models/'.$model.'.php');
				$this->$model = new $modelName();
			}
		}
	}
	
	/* START sql functions */
	function _mysql_fetch_alias_array($result) {
		if (!($row = mysql_fetch_array($result))) {
			return null;
		}
		$assoc = array();
		$rowCount = mysql_num_fields($result);
		for ($idx = 0; $idx < $rowCount; $idx++) {
			$table = mysql_field_table($result, $idx);
			$field = mysql_field_name($result, $idx);
			if (array_key_exists($field, $assoc)) {
				$assoc[singular($this->table)."_$field"] = $row[$idx];
			} else {
				$assoc["$field"] = $row[$idx];
			}
		}
		return $assoc;
	}

	function _mysql_fetch($result) {
		return ($this->defaults['joins'] != '') ? $this->_mysql_fetch_alias_array($result) : mysql_fetch_assoc($result);
	}

	function delete_children($tables=array()){
		foreach($tables as $table){
			mysql_query("DELETE FROM $table WHERE account_id = {$GLOBALS['account']['id']}");
		}
	}
	
	function prep_where_ary(&$where_ary, $method=''){
		foreach ($this->disabled as $prop) unset($where_ary[$prop]);
		if (($method == 'post' || $method == 'put') && !empty($this->unique_fields)){
			foreach ($this->unique_fields as $unique){
				if (isset($where_ary[$unique])){
					$id = 0;
					if (isset($where_ary['id'])) { $id = $where_ary['id']; }
					if (!$this->_unique_check("SELECT id FROM `{$this->_table}` WHERE `$unique` = '{$where_ary[$unique]}' and id != $id LIMIT 1")){
						$this->_error('error', $unique.' already exists');
					}
				}
			}
		}
	}
	
	function _where_str($where_ary, $pre_text = "", $del = " AND") {
		$i = 0;
		$this->where_str = "";
		foreach ($where_ary as $k => $v) { //remove "OR" items
			if(preg_match('/^LIKEOR\s/',$v)){
				$this->where_or_ary[$k] = $v;
				unset($where_ary[$k]);
			}
		}
		foreach ($where_ary as $k => $v) {
			if (count($where_ary) > 1 && $i > 0) {
				$this->where_str .= $del;
			}
			if(preg_match('/^GREATERTHAN\s[^.]+/',$v)){
				$v = preg_replace('/^GREATERTHAN\s/', "", $v);
				$this->where_str .= " `$k` > '$v'";
			} else if(preg_match('/^LESSTHAN\s[^.]+/',$v)){
				$v = preg_replace('/^LESSTHAN\s/', "", $v);
				$this->where_str .= " `$k` < '$v'";
			} else {
				$this->where_str .= " `$k` = '".mysql_real_escape_string($v)."'";
			}
			$i++;
		}
		$i = 0;
		$where_or = "";
		foreach ($this->where_or_ary as $k => $v) {
			if (count($this->where_or_ary) > 1 && $i > 0) {
				$where_or .= ' OR';
			}
			if(preg_match('/^LIKEOR\s/',$v)){
				$v = preg_replace('/^LIKEOR\s/', "", $v);
				$where_or .= " `$k` LIKE '%$v%'";
			}
			$i++;
		}
		if ($this->where_str != "") { $this->where_str = $pre_text.$this->where_str; }
		if ($where_or != "" && $this->where_str != "") {
			$this->where_str = $this->where_str . ' and (' . $where_or . ')';
		} elseif ($where_or != "") {
			$this->where_str = $pre_text . '(' . $where_or . ')';
		}
	}

	function _insert_str(){
		$i = 0;
		$fields = ""; $upd_fields = "";
		$values = array();
		$where = "";
		if (!empty($this->where_ary)){
			foreach ($this->where_ary as $k => $v){
				if (count($this->where_ary) > 1 && $i > 0){
					$fields .= ", ";
					if ($k != 'id') $upd_fields .= ", ";
					foreach ($values as &$value) {
						$value .= ", ";
					}
					unset($value);
				}
				$fields .= "`$k`";
				if ($k != 'id') $upd_fields .= "`$k`=VALUES(`$k`)";
				if (is_array($v)){
					foreach($v as $key => $val){
						$val = mysql_real_escape_string($val);
						if (isset($values[$key])){
							$values[$key] .= "'$val'";
						} else {
							$values[$key] = "'$val'";
						}
					}
				} else {
					@$values[0] .= "'".mysql_real_escape_string($v)."'";
				}
				$i++;
			}
			$i = 0;
			$values_str = "";
			foreach ($values as $k => $v){
				if (count($values) > 1 && $i > 0){
					$values_str .= ", ";
				}
				$values_str .= "($v)";
				$i++;
			}
			$where = "($fields) VALUES $values_str";
		}
		$this->where_str = $where;
	}

	function _sql_select($where_ary){
		if ($this->defaults['joins'] != ''){
			$join_table = $this->defaults['joins'];
			$join = " LEFT JOIN `{$this->defaults['joins']}` ON `{$this->_table}`.".singular($this->defaults['joins'])."_id = {$this->defaults['joins']}.id ";
			$where_tmp = array();
			foreach($this->where_ary as $k => $v){
				$where_tmp[$this->_table.'`.`'.$k] = $v;
			}
			$this->where_ary = $where_tmp;
			$this->_where_str($where_ary, "WHERE ");
			return $this->_run_query("SELECT `{$this->_table}`.*, `$join_table`.* FROM `{$this->_table}` $join {$this->where_str} {$this->defaults['order']} {$this->defaults['limit']}", true);
		} else {
			$this->_where_str($where_ary,"WHERE ");
			return $this->_run_query("SELECT * FROM `{$this->_table}` {$this->where_str} {$this->defaults['order']} {$this->defaults['limit']}", true);
		}
	}

	function _sql_update($where_ary){
		if (isset($where_ary['id'])){
			$whr = "`id` = '{$where_ary['id']}'";
		}
		if (!empty($this->update_timestamps)){
			foreach ($this->update_timestamps as $ts){
				if (!isset($where_ary[$ts])){
					$where_ary[$ts] = $GLOBALS['date'];
				}
			}
		}
		return $this->_run_query("UPDATE `{$this->_table}` SET ".$this->_where_str($where_ary, "", "", ", ")." WHERE $whr LIMIT 1");
	}

	function _sql_delete($where_ary){
		$i = 0;
		$id_str = "";
		if (isset($where_ary['id'])){
			$id_str = " `id` = '{$where_ary['id']}'";
		}
		foreach($where_ary as $k => $v){
			$id_str .= " AND `$k` = '$v'";
		}
		return $this->_run_query("DELETE FROM `{$this->_table}` WHERE $id_str");
	}

	function _sql_insert($where_ary) {
		if (!empty($this->create_timestamps)){
			foreach ($this->create_timestamps as $ts){
				if (!isset($where_ary[$ts])){
					$where_ary[$ts] = $GLOBALS['date'];
				}
			}
		}
		$this->_insert_str($where_ary);
		return $this->_run_query("INSERT INTO {$this->_table} ".$this->where_str);
	}

	function _run_query($sql, $get_count=false){
		$json = array();
		$count = 0;
		$type = substr($sql, 0, 6);
		$query = mysql_query($sql);
		if ($query === true) { // INSERT, UPDATE, DELETE
			$count = mysql_affected_rows();
			if ($type != "DELETE") {
				if ($type == "INSERT") {
					$this->where_ary = array(); // clear out where_ary to insert just the id
					$this->where_ary[0]['id'] = mysql_insert_id();
				}
			}
		} elseif ($query){ // SELECT
			if (mysql_num_rows($query) > 0){
				while ($row = $this->_mysql_fetch($query)){
					if ($this->defaults['include'] != "" && isset($row[$this->defaults['include']."_id"])){
						$this->{$this->defaults['include']}->where_ary[0]['id'] = $row[$this->defaults['include']."_id"];
						$tmp = $this->{$this->defaults['include']}->get();
						$row[$this->defaults['include']] = $this->{$this->defaults['include']}->results['items'];
						$this->{$this->defaults['include']}->results['items'] = array();
					}
					foreach ($this->removals as $prop) unset($row[$prop]);
					$json[] = $row;
				}
			}
		} else {
			$this->_error('error', 'MySQL error: '.mysql_error());
		}
		if ($get_count){
			$query_count = @mysql_query("SELECT COUNT(*) AS `count` FROM `{$this->_table}` ".$this->where_str);
			while ($row = mysql_fetch_assoc($query_count)) {
				$count = $row['count'];
			}
		}
		return array("items" => $json, "count" => $count);
	}
	/* END sql functions */
	
	function get($get_count=true){
		foreach ($this->where_ary as $where_ary){
			$this->prep_where_ary($where_ary);
			$results = $this->_sql_select($where_ary);
			if ($get_count){
				$this->results['count'] += $results['count'];
			}
			$this->results['items'] = array_merge($results['items'], $this->results['items']);
		}
	}
	
	function put(){
		foreach ($this->where_ary as $where_ary){
			$this->prep_where_ary($where_ary);
			$results = $this->_sql_update($where_ary);
			$this->results['count'] += $results['count'];
		}
		$this->get(false);
	}
	
	function post(){
		foreach ($this->where_ary as $where_ary){
			$this->prep_where_ary($where_ary);
			$results = $this->_sql_insert($where_ary);
			$this->results['count'] += $results['count'];
		}
		$this->get(false);
	}
	
	function delete(){
		$this->get(false);
		// run delete after get(), that way we can still return the records
		foreach ($this->where_ary as $where_ary){
			$this->prep_where_ary($where_ary);
			$results = $this->_sql_delete($where_ary);
			$this->results['count'] += $results['count'];
		}
	}
		
}
?>