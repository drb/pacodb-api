<?php
/**
 * Used to extract all user data for a main account
 *
 * [WebClass]
 */
class PacoUsers extends PacoAPI
{	
	
	/**
	 * Gets a single user
	 *
	 * @param string $id The user's internal ID
	 *
	 * [WebMethod]
	 */
	public function get ($id)
	{
		
		//print($this->user);

		$user = $this->find(
			'users',
			array(
				'_id'=>new MongoId($id),
				'owner_id'=>new MongoId($this->user_id)
			)
		);
			
		if (!$user)
		{
			return new PacoError('No such user with supplied ID', '101');
		}
		
		$foo = PacoUtils::transform($user);
		
		return array('user'=>$foo);
	}	
	
	/**
	 * Displays all users available under this account. Passwords are ommited by default, pass in a $fields array to extract this information, or call users.get() directly with the user's id
	 *  
	 * @param string $filter Filter results based on supplied ruleset
	 * @param string $fields Which fields to return. Defaults to name and API key
	 * @param int $limit How many users to return. Default is 25
	 *
	 * [WebMethod]
	 */
	public function query($filter=null, $fields=null, $limit=self::API_DEFAULT_LIMIT)
	{
		$filter_rules = array();
		
		if (!is_null($filter))
		{
			// parse filter rules
			$filter_rules = PacoUtils::filter_rules($filter);
		}
				
		$u = $this->find(
			'owners',			// collection
			$filter_rules,			// filter
			array(				// field
				'name', 
				'api.key'
			),
			array('_id'=>1),		// sort
			$limit				// limit
		);
		
		$users = PacoUtils::transform($u);
		
		return array('users'=>$users);
	}
}
?>
