<?php
/**
 * Copyright 2010 MyBB Group, All Rights Reserved
 * Edited by Alexander René Sagen.
 *
 * Website: http://mybb.com
 * License: http://mybb.com/about/license
 */

class db_mysqli
{
	/**
	 * The database connection resource
	 *
	 * @var resource
	 */
	public $link;

	/**
	 * The database encoding currently in use
	 *
	 * @var string
	 */
	public $db_encoding = 'utf8';

	/**
	 * The current table type in use (myisam/innodb)
	 *
	 * @var string
	 */
	public $table_type = "myisam";

	/**
	 * The database engine
	 *
	 * @var string
	 */
	public $engine = "mysqli";

	/**
	* A count of the number of queries
	*
	* @var int
	*/
	public $query_count = 0;

	/**
	* The time spent performing queries
	*
	* @var float
	*/
	public $query_time = 0;

	/**
	* A list of the performed queries
	*
	* @var array
	*/
	public $query_list = array();

	/**
	 * 1 if error reporting enabled, 0 if disabled.
	 *
	 * @var boolean
	 */
	public $error_reporting = 0;

	/**
	* The function for connecting to the MySQLi server
	*
	* @param array An array containing connection details.
	* @return resource The database connection resource. Returns false on error.
	*/
	function connect($config)
	{
		if(!isset($config) || !is_array($config))
		{
			$this->error("Unable to connect to MySQL server");
			return false;
		}
		
		$this->get_execution_time();

		// Specified a custom port for this connection?
		$port = 0;
		if(strstr($config['hostname'],':'))
		{
			list($hostname, $port) = explode(":", $config['hostname'], 2);
		}

		if($port)
		{
			$this->link = @mysqli_connect($hostname, $config['username'], $config['password'], "", $port);
		}
		else
		{
			$this->link = mysqli_connect($config['hostname'], $config['username'], $config['password']);
		}

		$time_spent = $this->get_execution_time();
		$this->query_time += $time_spent;

		// Select databases
		if(!$this->select_db($config['database']))
		{
			return false;
		}

		return $this->link;
	}

	/**
	 * Selects the database to use.
	 *
	 * @param string The database name.
	 * @return boolean True when successfully connected, false if not.
	 */
	function select_db($database)
	{		
		$success = @mysqli_select_db($this->link, $database) or $this->error("Unable to select database");
		
		if($success && $this->db_encoding)
		{
			if(version_compare(PHP_VERSION, '5.0.5', '>='))
			{
				mysqli_set_charset($this->link, $this->db_encoding);
			}
			else
			{
				$this->query("SET NAMES '{$this->db_encoding}'");
			}
		}

		return $success;
	}

	/**
	 * Query the database.
	 *
	 * @param string The query SQL.
	 * @return resource The query data. Returns false on error.
	 */
	function query($string)
	{
		$this->get_execution_time();

		$query = @mysqli_query($this->link, $string);

		if($this->error_number() && $this->error_reporting)
		{
			$this->error($string);
			return false;
		}
		
		$query_time = $this->get_execution_time();
		$this->query_time += $query_time;
		$this->query_count++;
		
		return $query;
	}

	/**
	 * Return a result array for a query.
	 *
	 * @param resource The query data.
	 * @return array The array of results.
	 */
	function fetch_array($query)
	{
		if($query === false)
		{
			return false;
		}

		$array = mysqli_fetch_assoc($query);
		return $array;
	}

	/**
	 * Return a specific field from a query.
	 *
	 * @param resource The query ID.
	 * @param string The name of the field to return.
	 * @param int The number of the row to fetch it from.
	 */
	function fetch_field($query, $field, $row=false)
	{
		if($row !== false)
		{
			$this->data_seek($query, $row);
		}
		
		$array = $this->fetch_array($query);

		if(!is_array($array))
		{
			return false;
		}

		return $array[$field];
	}

	/**
	 * Moves internal row pointer to the next row
	 *
	 * @param resource The query ID.
	 * @param int The pointer to move the row to.
	 */
	function data_seek($query, $row)
	{
		return mysqli_data_seek($query, $row);
	}

	/**
	 * Return the number of rows resulting from a query.
	 *
	 * @param resource The query data.
	 * @return int The number of rows in the result.
	 */
	function num_rows($query)
	{		
		if(!is_a($query, 'mysqli_result'))
		{
			return false;
		}
		
		return mysqli_num_rows($query);
	}

	/**
	 * Return the last id number of inserted data.
	 *
	 * @return int The id number.
	 */
	function insert_id()
	{
		$id = mysqli_insert_id($this->link);
		return $id;
	}

	/**
	 * Close the connection with the DBMS.
	 *
	 */
	function close()
	{
		@mysqli_close($this->link);
	}

	/**
	 * Return an error number.
	 *
	 * @return int The error number of the current error.
	 */
	function error_number()
	{
		if($this->link)
		{
			return mysqli_errno($this->link);			
		}
		else
		{
			return mysqli_connect_errno();
		}
	}

	/**
	 * Return an error string.
	 *
	 * @return string The explanation for the current error.
	 */
	function error_string()
	{
		if($this->link)
		{
			return mysqli_error($this->link);			
		}
		else
		{
			return mysqli_connect_error();
		}
	}

	/**
	 * Returns the number of affected rows in a query.
	 *
	 * @return int The number of affected rows.
	 */
	function affected_rows()
	{
		return mysqli_affected_rows($this->link);
	}

	/**
	 * Return the number of fields.
	 *
	 * @param resource The query data.
	 * @return int The number of fields.
	 */
	function num_fields($query)
	{
		return mysqli_num_fields($query);
	}

	/**
	 * Lists all functions in the database.
	 *
	 * @param string The database name.
	 * @param string Prefix of the table (optional)
	 * @return array The table list.
	 */
	function list_tables($database, $prefix='')
	{
		if($prefix)
		{
			$query = $this->query("SHOW TABLES FROM `$database` LIKE '".$this->escape_string($prefix)."%'");
		}
		else
		{
			$query = $this->query("SHOW TABLES FROM `$database`");
		}
		
		while(list($table) = mysqli_fetch_array($query))
		{
			$tables[] = $table;
		}
		return $tables;
	}

	/**
	 * Check if a table exists in a database.
	 *
	 * @param string The table name.
	 * @return boolean True when exists, false if not.
	 */
	function table_exists($table)
	{
		$query = $this->query("SHOW TABLES LIKE '$table'");
		$exists = $this->num_rows($query);
		
		if($exists > 0)
		{
			return true;
		}
		else
		{
			return false;
		}
	}

	/**
	 * Check if a field exists in a database.
	 *
	 * @param string The field name.
	 * @param string The table name.
	 * @return boolean True when exists, false if not.
	 */
	function field_exists($field, $table)
	{
		$query = $this->query("SHOW COLUMNS 
			FROM $table 
			LIKE '$field'");
		$exists = $this->num_rows($query);
		
		if($exists > 0)
		{
			return true;
		}
		else
		{
			return false;
		}
	}

	/**
	 * Performs a simple select query.
	 *
	 * @param string The table name to be queried.
	 * @param string Comma delimetered list of fields to be selected.
	 * @param string SQL formatted list of conditions to be matched.
	 * @param array List of options, order by, order direction, limit, limit start.
	 * @return resource The query data.
	 */
	
	function simple_select($table, $fields="*", $conditions="", $options=array())
	{
		$query = "SELECT " . $fields . " FROM " . $table;
		
		if(!empty($conditions))
		{
			$query .= " WHERE " . $conditions;
		}
		
		if(isset($options['order_by']))
		{
			$query .= " ORDER BY " . $options['order_by'];
			if(isset($options['order_dir']))
			{
				$query .= " " . strtoupper($options['order_dir']);
			}
		}
		
		if(isset($options['limit_start']) && isset($options['limit']))
		{
			$query .= " LIMIT " . $options['limit_start'] . ", " . $options['limit'];
		}
		else if(isset($options['limit']))
		{
			$query .= " LIMIT " . $options['limit'];
		}
		
		return $this->query($query);
	}

	/**
	 * Build an insert query from an array.
	 *
	 * @param string The table name to perform the query on.
	 * @param array An array of fields and their values.
	 * @return int The insert ID if available
	 */
	function insert_query($table, $array)
	{
		if(!is_array($array))
		{
			return false;
		}

		$fields = "`".implode("`,`", array_keys($array))."`";
		$values = implode("','", $array);
		$query = $this->query("
			INSERT 
			INTO {$table} (".$fields.") 
			VALUES ('".$values."')
		");
		
		if(!$query)
		{
			return false;
		}

		return $this->insert_id();
	}

	/**
	 * Build one query for multiple inserts from a multidimensional array.
	 *
	 * @param string The table name to perform the query on.
	 * @param array An array of inserts.
	 * @return int The insert ID if available
	 */
	function insert_query_multiple($table, $array)
	{
		if(!is_array($array))
		{
			return false;
		}
		// Field names
		$fields = array_keys($array[0]);
		$fields = "`".implode("`,`", $fields)."`";

		$insert_rows = array();
		foreach($array as $values)
		{
			$insert_rows[] = "('".implode("','", $values)."')";
		}
		$insert_rows = implode(", ", $insert_rows);

		$this->query("
			INSERT 
			INTO {$table} ({$fields}) 
			VALUES {$insert_rows}
		");
	}

	/**
	 * Build an update query from an array.
	 *
	 * @param string The table name to perform the query on.
	 * @param array An array of fields and their values.
	 * @param string An optional where clause for the query.
	 * @param string An optional limit clause for the query.
	 * @return resource The query data.
	 */
	function update_query($table, $array, $where="", $limit="")
	{
		if(!is_array($array))
		{
			return false;
		}
		
		$comma = "";
		$query = "";
		
		foreach($array as $field => $value)
		{
			$query .= $comma . "`" . $field . "`='{$value}'";
			$comma = ', ';
		}
		
		if(!empty($where))
		{
			$query .= " WHERE $where";
		}
		
		if(!empty($limit))
		{
			$query .= " LIMIT $limit";
		}

		return $this->query("
			UPDATE $table
			SET $query
		");
	}

	/**
	 * Build a delete query.
	 *
	 * @param string The table name to perform the query on.
	 * @param string An optional where clause for the query.
	 * @param string An optional limit clause for the query.
	 * @return resource The query data.
	 */
	function delete_query($table, $where="", $limit="")
	{
		$query = "";
		if(!empty($where))
		{
			$query .= " WHERE $where";
		}
		if(!empty($limit))
		{
			$query .= " LIMIT $limit";
		}
		return $this->query("DELETE FROM $table $query");
	}

	/**
	 * Escape a string according to the MySQL escape format.
	 *
	 * @param string The string to be escaped.
	 * @return string The escaped string.
	 */
	function escape_string($string)
	{
		if(function_exists("mysqli_real_escape_string") && $this->link)
		{
			$string = mysqli_real_escape_string($this->link, $string);
		}
		else
		{
			$string = addslashes($string);
		}

		return $string;
	}

	/**
	 * Frees the resources of a MySQLi query.
	 *
	 * @param object The query to destroy.
	 * @return boolean Returns true on success, false on faliure
	 */
	function free_result($query)
	{
		return mysqli_free_result($query);
	}

	/**
	 * Escape a string used within a like command.
	 *
	 * @param string The string to be escaped.
	 * @return string The escaped string.
	 */
	function escape_string_like($string)
	{
		return $this->escape_string(str_replace(array('%', '_') , array('\\%' , '\\_') , $string));
	}

	/**
	 * Optimizes a specific table.
	 *
	 * @param string The name of the table to be optimized.
	 */
	function optimize_table($table)
	{
		$this->query("OPTIMIZE TABLE " . $table . "");
	}

	/**
	 * Analyzes a specific table.
	 *
	 * @param string The name of the table to be analyzed.
	 */
	function analyze_table($table)
	{
		$this->query("ANALYZE TABLE " . $table . "");
	}

	/**
	 * Show the "create table" command for a specific table.
	 *
	 * @param string The name of the table.
	 * @return string The MySQL command to create the specified table.
	 */
	function show_create_table($table)
	{
		$query = $this->query("SHOW CREATE TABLE ".$this->table_prefix.$table."");
		$structure = $this->fetch_array($query);
		
		return $structure['Create Table'];
	}

	/**
	 * Show the "show fields from" command for a specific table.
	 *
	 * @param string The name of the table.
	 * @return string Field info for that table
	 */
	function show_fields_from($table)
	{
		$query = $this->query("SHOW FIELDS FROM " . $table . "");
		while($field = $this->fetch_array($query))
		{
			$field_info[] = $field;
		}
		return $field_info;
	}

	/**
	 * Returns whether or not the table contains a fulltext index.
	 *
	 * @param string The name of the table.
	 * @param string Optionally specify the name of the index.
	 * @return boolean True or false if the table has a fulltext index or not.
	 */
	function is_fulltext($table, $index="")
	{
		$structure = $this->show_create_table($table);
		if($index != "")
		{
			if(preg_match("#FULLTEXT KEY (`?)$index(`?)#i", $structure))
			{
				return true;
			}
			else
			{
				return false;
			}
		}
		if(preg_match('#FULLTEXT KEY#i', $structure))
		{
			return true;
		}
		return false;
	}

	/**
	 * Checks to see if an index exists on a specified table
	 *
	 * @param string The name of the table.
	 * @param string The name of the index.
	 */
	function index_exists($table, $index)
	{
		$index_exists = false;
		$query = $this->query("SHOW INDEX FROM {$table}");
		while($ukey = $this->fetch_array($query))
		{
			if($ukey['Key_name'] == $index)
			{
				$index_exists = true;
				break;
			}
		}
		
		if($index_exists)
		{
			return true;
		}
		
		return false;
	}

	/**
	 * Creates a fulltext index on the specified column in the specified table with optional index name.
	 *
	 * @param string The name of the table.
	 * @param string Name of the column to be indexed.
	 * @param string The index name, optional.
	 */
	function create_fulltext_index($table, $column, $name="")
	{
		$this->query("ALTER TABLE $table ADD FULLTEXT $name ($column)");
	}

	/**
	 * Drop an index with the specified name from the specified table
	 *
	 * @param string The name of the table.
	 * @param string The name of the index.
	 */
	function drop_index($table, $name)
	{
		$this->query("ALTER TABLE $table DROP INDEX $name");
	}

	/**
	 * Drop an table with the specified table
	 *
	 * @param boolean hard drop - no checking
	 * @param boolean use table prefix
	 */
	function drop_table($table, $hard=false, $table_prefix=true)
	{
		if($table_prefix == false)
		{
			$table_prefix = "";
		}
		else
		{
			$table_prefix = $this->table_prefix;
		}
		
		if($hard == false)
		{
			$this->query('DROP TABLE IF EXISTS ' . $table);
		}
		else
		{
			$this->query('DROP TABLE ' . $table);
		}
	}

	/**
	 * Replace contents of table with values
	 *
	 * @param string The table
	 * @param array The replacements
	 */
	function replace_query($table, $replacements=array())
	{
		$values = '';
		$comma = '';
		foreach($replacements as $column => $value)
		{
			$values .= $comma."`".$column."`='".$value."'";
			
			$comma = ',';
		}
		
		if(empty($replacements))
		{
			 return false;
		}
		
		return $this->query("REPLACE INTO {$table} SET {$values}");
	}

	/**
	 * Drops a column
	 *
	 * @param string The table
	 * @param string The column name
	 */
	function drop_column($table, $column)
	{
		return $this->query("ALTER TABLE {$table} DROP {$column}");
	}

	/**
	 * Adds a column
	 *
	 * @param string The table
	 * @param string The column name
	 * @param string the new column definition
	 */
	function add_column($table, $column, $definition)
	{
		return $this->query("ALTER TABLE {$table} ADD {$column} {$definition}");
	}
	
	/**
	 * Modifies a column
	 *
	 * @param string The table
	 * @param string The column name
	 * @param string the new column definition
	 */
	function modify_column($table, $column, $new_definition)
	{
		return $this->query("ALTER TABLE {$table} MODIFY {$column} {$new_definition}");
	}
	
	/**
	 * Renames a column
	 *
	 * @param string The table
	 * @param string The old column name
	 * @param string the new column name
	 * @param string the new column definition
	 */
	function rename_column($table, $old_column, $new_column, $new_definition)
	{
		return $this->query("ALTER TABLE {$table} CHANGE {$old_column} {$new_column} {$new_definition}");
	}

	/**
	 * Fetched the total size of all mysql tables or a specific table
	 *
	 * @param string The table (optional)
	 * @return integer the total size of all mysql tables or a specific table
	 */
	function fetch_size($table='')
	{
		if($table != '')
		{
			$query = $this->query("SHOW TABLE STATUS LIKE '" . $table . "'");
		}
		else
		{
			$query = $this->query("SHOW TABLE STATUS");
		}
		$total = 0;
		while($table = $this->fetch_array($query))
		{
			$total += $table['Data_length']+$table['Index_length'];
		}
		return $total;
	}

	/**
	 * Output an SQL error.
	 *
	 * @param string The string to present as an error.
	 */
	function error($string="")
	{
		if($this->error_reporting)
		{
			trigger_error("<strong>[SQL] [" . $this->error_number() . "] " . $this->error_string() . "</strong><br />{$string}", E_USER_ERROR);
		}
		else
		{
			return false;
		}
	}

	/**
	 * Get the time it takes to perform a piece of code. Called before and after the block of code.
	 *
	 * @return float The time taken.
	 */
	function get_execution_time()
	{
		static $time_start;

		$time = microtime(true);

		if(!$time_start)
		{
			$time_start = $time;
			return;
		}
		else
		{
			$total = $time - $time_start;
			if($total < 0) $total = 0;
			$time_start = 0;
			return $total;
		}
	}

}