<?php

/**
 * Exposes a data construct as JSON
 *
 * @package pako
 */
 
class PacoData extends PacoAPI
{
	private $p;
	
	public function __construct($p)
	{
		$this->p = $p;
		//parent::__construct();
		//var_dump($this);
	}
	
	public function get_data($id)
	{
		return $this->find(
			'data',
			array(),
			array(),
			array()
		);
	}
	
	
	/**
	 * Returns specific data types the data class can handle
	 */
	public function types()
	{
		return array(
			'string'=>'String', 
			'numeric'=>'Number',
			'file'=>'User Uploaded File', 
			'date'=>'Date', 
			'keypair'=>'Key/Pair Value',
			'tags'=>'Tag field'
		);
	}
	
	/**
	 * Returns a list of available datasets for this user 
	 *
	 * @APIInterfaceMethod
	 */
	public function list_sets()
	{
		$data = $this->p->find_one(
			'users'
		);
		return $data;
	}
}
?>
