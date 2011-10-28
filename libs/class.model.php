<?php
include LIBS . 'class.db_handling.php';

class Model
{
	protected $db_handling = null;

	public function  __construct()
	{
		$this->db_handling = db_handling::get_instance(DB_HOST, DB_USER, DB_PASS, DB);
	}
}