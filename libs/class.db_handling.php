<?php
/** class.db_handling.php
 *
 * @version 20101014
 * @author Simon Samtleben
 *
 *  [description]
 *
 *  A simple mysqli handler.
 *  This class is a singleton!
 *
 * [changelog]
 *
 *		2010-10-14
 *		Modified class to allow method chaining. Why? Because code is poetry!
 * 
 *		2010-10-11
 *		Modified class to user single variables for connection params (instead
 *		of an array).
 *		NOTICE: No backwards-compatibility!
 *
 *		2009-09-17 by Simon Samtleben
 *		Bugfix: No fatal on call of get_result after sql-error.
 * 
 *		2009-09-11 by Simon Samtleben
 *		Feature: Escaping of %-chars in querys added. 
 *
 */

class db_handling
{
	private static $instance = null;
	private $db_config = null;
	private $mysqli = null;
	private $result = null;
	private $query = null;

	private function __construct($db_host = null, $db_user = null, $db_pass = null, $db = null)
	{
		if($db_host !== null && $db_user !== null)
		{
			$this->db_config = array(
				'db_host' => $db_host,
				'db_user' => $db_user,
				'db_pass' => $db_pass,
				'db' => $db
			);
			$this->mysqli = mysqli_init();
			$this->mysqli->real_connect($db_host, $db_user, $db_pass, $db);
			$this->mysqli->set_charset("utf8");	
		}
	}

	private function __clone() {}

	public function __destruct()
	{
		$this->disconnect();
		unset($this->mysqli, $this->db_config, $this->result);
	}

	/** Use instead of constructor to get instance of class.
	 *
	 * @param array $conf Connection information.
	 * @return object Instance of db_handling.
	 */
	public static function get_instance($db_host = null, $db_user = null, $db_pass = null, $db = null)
	{
		if(self::$instance === null)
		{
			self::$instance = new db_handling($db_host, $db_user, $db_pass, $db);
		}
		return self::$instance;
	}

	/** Connect to a mysql database using mysqli.
	 *
	 * @param array $db_config Connection information.
	 */
	public function connect($db_host = null, $db_user = null, $db_pass = null, $db = null)
	{
		$this->db_config = array(
			'db_host' => $db_host,
			'db_user' => $db_user,
			'db_pass' => $db_pass,
			'db' => $db
		);
		$this->mysqli = mysqli_init();
		$this->mysqli->real_connect($db_host, $db_user, $db_pass, $db);
		$this->mysqli->set_charset("utf8");
	}

	/** Close database connection.
	 */
	public function disconnect()
	{
		if($this->mysqli !== null)
		{
			$this->mysqli->close();
		}
	}

	/** Run a query on database.
	 *
	 * @param string $query An sql query.
	 * @return bool True on suceess false on error.
	 */
	public function query($query = null)
	{
		if($query !== null)
		{
			$this->query = $query;
		}
		$this->result = $this->mysqli->query($this->query);
		$this->query = null;
		return ($this->result === false) ? false : $this;
	}

	/** Returns id of last insert operation.
	 *
	 * @return int Id of last insert operation.
	 */
	public function get_insert_id()
	{
		return $this->mysqli->insert_id;
	}

	/** Prepares and returns result of an sql query.
	 *
	 * @param bool $pop Removes "0"-Element from array if only one result row.
	 * @return array Result information.
	 */
	public function get_result($pop = false)
	{
		if($this->result === false)
		{
			return false;
		}
		
		$result = array();
		while($row = $this->result->fetch_array(MYSQLI_ASSOC))
		{
			$result[] = $row;
		}

		if($this->result->num_rows == 1 && $pop === true)
		{
			$result = $result[0];
		}

		// strip slashes:
		array_walk_recursive($result , create_function('&$temp', '$temp = stripslashes($temp);'));

		return $result;
	}

	/** Returns number of result rows.
	 *
	 * @return int Number of results.
	 */
	public function get_result_count()
	{
		return $this->result->num_rows;
	}

	/** Returns mysqli error message.
	 *
	 * @return string Error message.
	 */
	public function get_error()
	{
		return $this->mysqli->error;
	}

	/** Replaces placeholders in a query-string with according values.
	 *
	 * @param string $query The query string.
	 * @param array $values Values to put into query-string.
	 * @return string Prepared query string.
	 */
	public function prepare($query, $values)
	{
		// mask escaped signs:
		$query = str_replace('\%', '{#}', $query);

		if(substr_count($query, '%s') + substr_count($query, '%d') != count($values))
		{
			return false;
		}

		// sanitize query:
		$query = str_replace("'%s'", '%s', $query);
		$query = str_replace('"%s"', '%s', $query);
		$query = str_replace("'%d'", '%d', $query);
		$query = str_replace('"%d"', '%d', $query);

		// quote strings:
		$query = str_replace('%s', "'%s'", $query);

		// add slashes:
		foreach(array_keys($values) as $key)
		{
			$values[$key] = $this->mysqli->real_escape_string($values[$key]);
		}

		// replace placeholders whith values from array:
		$query = vsprintf($query, $values);

		// unmask:
		$this->query = str_replace('{#}', '%', $query);

		return $this;
	}
}