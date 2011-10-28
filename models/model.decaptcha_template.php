<?php
/**
 * A VERY simple model for the decaptcha template table.
 */
include LIBS . 'class.model.php';

class DecaptchaTemplate extends Model
{
	private $solution = null;
	private $horizontalProjection = array();
	private $verticalProjection = array();

	public function  __construct()
	{
		parent::__construct();
	}

	public function setSolution($solution)
	{
		if(empty($solution))
		{
			return false;
		}
		$this->solution = $solution;
		return true;
	}

	public function setHorizontalProjection($hp)
	{
		if(empty($hp) || !is_array($hp))
		{
			return false;
		}
		$this->horizontalProjection = $hp;
		return true;
	}

	public function setVerticalProjection($vp)
	{
		if(empty($vp) || !is_array($vp))
		{
			return false;
		}
		$this->verticalProjection = $vp;
		return true;
	}

	public function getAll()
	{
		$query = "SELECT * FROM decaptcha_templates";
		return $this->db_handling->query($query)->get_result();
	}

	public function save()
	{
		$query = "INSERT INTO decaptcha_templates (solution, horizontalProjection, verticalProjection) VALUES(%s,%s,%s)";
		$this->db_handling->prepare($query, array($this->solution, serialize($this->horizontalProjection), serialize($this->verticalProjection)));
		if($this->db_handling->query() !== false)
		{
			return true;
		}
		return false;
	}
}