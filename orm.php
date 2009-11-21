<?php

class ORM{
	# Public
	function save(){
		# Ignored if not in the columns
		$this->updated_at = strftime("%Y-%m-%d %H:%M:%S", time());
		
		if($this->isNewRecord()){
			# Ignored if not in the columns
			$this->created_at = strftime("%Y-%m-%d %H:%M:%S", time());

			$columns = implode(array_keys($this->columns), ",");

			$values = array();
			foreach($this->columns as $key => $value){
				array_push($values, "'" . mysql_escape_string($this->$key) . "'");
			}
			$values = implode($values, ",");

			$sql = "insert into $this->table ($columns) VALUES ($values)";
			mysql_query($sql);
			$this->id = mysql_insert_id();
		}else{
			$columns = array();
			foreach($this->columns as $key => $value){
				array_push($columns, $key . "='" . mysql_escape_string($this->$key) . "'");
			}
			$columns = implode($columns, ",\n");

			$sql = "update $this->table set $columns where id=" . (int)$this->id;
			mysql_query($sql);
		}
	}
	
	function isNewRecord(){
		return !isset($this->id);
	}
	
	function setAttributes($array){
		foreach($array as $key => $value){
			$this->$key = $value;
		}
	}
	
	# Protected, must be called by child class
	function _find($id){
		$sql = "select * from $this->table where id=" . (int)$id;
		$result = mysql_query($sql);
		
		if($row = mysql_fetch_assoc($result)){
			$this->_load_columns($row);
		}else{
			echo "<b>Warning:</b> Could not find $this->table record #$id.<br />";
		}
		
		return $this;
	}
	
	# Protected, must be called by child class
	function _find_by($column, $id){
		$sql = "select * from $this->table where $column='" . mysql_escape_string($id) . "'";
		$result = mysql_query($sql);
		
		if($row = mysql_fetch_assoc($result)){
			$this->_load_columns($row);
		}else{
			return false;
		}
		
		return $this;
	}
	
	# Protected, must be called by child class
	function _find_or_create_by($column, $id){
		$sql = "select * from $this->table where $column='" . mysql_escape_string($id) . "'";
		$result = mysql_query($sql);
		
		if($row = mysql_fetch_assoc($result)){
			$this->_load_columns($row);
		}else{
			$this->$column = $id;
			$this->save();
		}
		
		return $this;
	}

	# Private, only used internally
	function _load_columns($row){
		foreach($row as $key => $value){
			$this->$key = $value;
		}
	}
	
	# Protected, must be called by child class
	function _migrate(){
		// $result = mysql_query("show fields from $this->table");
		// 
		// while($row = mysql_fetch_assoc($result)){
		// 	print_r($row);
		// }
		
		$columns = array("id bigint auto_increment primary key");
		foreach($this->columns as $key => $value){
			array_push($columns, $key . " " . $value);
		}
		$columns = implode($columns, ",\n");

		$sql = "drop table $this->table; create table $this->table($columns); ";
		mysql_query($sql);
	}
}

?>