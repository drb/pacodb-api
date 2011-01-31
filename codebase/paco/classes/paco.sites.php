<?php
/**
 * Manages user sites
 *
 * [WebClass]
 */
class PacoSites extends PacoAPI
{
	public function __construct()
	{
		parent::__construct();
	}
	
	/**
	 * Something
	 *
	 * @param $id int Site id
	 * @param $fields array The fields to return
	 *
	 * [WebMethod]
	 */
	public function get($id, array $fields=array())
	{
		$site_id = '123';
		$site = $this->find
		(
			'sites'		
		);
		//$site = true;
		
		if (!$site)
		{
			return '0';
		}
		
		return '1';//$site;
	}
	
	/**
	 * Returns the sitemap data used for creating menus
	 *
	 * @param $site_id string The id of the sitemap to retreive 
	 *
	 * [WebMethod]
	 */
	public function sitemap($id=0, $host='')
	{	
	
		if (empty($host) && $id == 0)
		{
			return new PacoError('Either Site ID or host is required', PacoError::EMPTY_ARGS);
		}
		
		//
		if(empty($host))
		{
			$id = new MongoId($id);
		} 
		else 
		{
			//print_r($this->user);
			
			$site = $this->find_one(
				'sites',
				array(
					'host'=>$host,
					'owner_id'=>$this->user_id
				)
			);
			
			if ($site)
			{
				$keys = array_keys($site);
				$id = new MongoId($keys[0]);
			} 
			else 
			{
				return new PacoError('No such host', 900);
			}
		}
		
		$map = $this->get_pages(0, $id);
		return array('sitemap'=>$map);
	}
	
	/**
	 * Returns the sitemap as flattened XML for Google sitemap
	 *
	 * [WebMethod]
	 */
	public function sitemap_xml($host)
	{
		$data = $this->sitemap(0, $host);
		
		
	}
	
	
	
	
	/**
	 * Returns a list of user IDs associated with th supplied site, and their permissions
	 *
	 * @param $site_id string The site's id
	 *
	 * [WebMethod]
	 */
	public function users($id)
	{
		$users = $this->find(
			'users',
			array(
				'owner_id'=>new MongoId($this->user_id),
				'sites.site_id'=>new MongoId($id)
			),
			array(
				'_id',
				'name',
				'sites.auth'
			),
			array(
				'name'=>1
			)
			
		);
		
		if (!$users)
		{
			return new PacoError('No users');
		}
		
		$users = PacoUtils::transform($users);
		
		return array('users'=>$users);
	}
	
	
	
	
	/**
	 * Recursive function to loop over nested pages to retrieve a sitemap
	 *
	 * @param $root string Where to start - default is at the root (top level) pages
	 * @param $site_id string The ID of the site being interogated - MongoID
	 */
	private function get_pages($root=0, MongoId $site_id)
	{
		$map		= array();
		$parent_id 	= $root;
		
		if (gettype($parent_id) == 'MongoId')
		{
			$parent_id = new MongoId($root);
		}
		
		$pages = $this->find(
			'pages',
			array(
				'parent_id'=>$root,	// parent_id = MongoId()
				'site_id'=>$site_id,	// parent_id = MongoId()
				'sitemap'=>1		// only pages in sitemap
			),
			array(),			// fields
			array(
				'title'=>1		// ensure pages ordered by name
			)
		);
		
		if ($pages)
		{
			// now we start looping all the page to find the subpages
			foreach($pages as $id=>$page)
			{
				$map_d = array(
					'id'=>$page['_id']->__toString(),
					'title'=>$page['title'],
					'url'=>$page['url'],
					'meta'=>$page['meta']
				);		
				
				$pages = $this->get_pages($page['_id'], $site_id);
				// append pages array if there are some
				if ($pages)
				{
					$map_d['pages'] = $pages;
				}
				
				$map[] = $map_d;
			}
		}
		
		return $map;
	}
	
	/**
	 * Returns all available sites under this account
	 *
	 * [WebMethod]
	 */
	public function all(array $fields=array())
	{

		$sites = $this->db->find
		(
			'sites',
			array(
				'owner_id'=>new MongoID($this->user_id)
			),
			$fields
		);
		return $sites;
	}
	
	/**
	 * Updates a site's properties - needs work
	 *
	 * [WebMethod]
	 */
	public function update($id, array $fields)
	{
		$this->db->update
		(
			'sites', 
			array('_id'=>new MongoID($site_id)), 
			array(
				'site_name' => $fields['site_name'],
				'credentials.ftp.ftp_host'	=> $fields['ftp_host'], 
				'credentials.ftp.ftp_username'	=> $fields['ftp_username'], 
				'credentials.ftp.ftp_port'	=> $fields['ftp_port'], 
				'credentials.ftp.ftp_password' 	=> utf8_encode(Encryption::encrypt($fields['ftp_password']))
			)
		);
	}
	
	/**
	 * Creates a new site - needs work
	 *
	 * [WebMethod]
	 */
	public function create($fields)
	{
		$new_site = $this->db->insert
		(
			'sites',
			array(
				'site_name'=>$form_data['site_name'],
				'credentials'=>array(
						'ftp'=>array(
							'ftp_host'=>$form_data['ftp_host'],
							'ftp_port'=>$form_data['ftp_port'],
							'ftp_username'=>$form_data['ftp_username'],
							'ftp_password'=>utf8_encode(Encryption::encrypt($form_data['ftp_password']))
						),
						'ssh'=>array(
							'ssh_user'=>'dave'
						)
					),
				'users'=>array(
					'_id'=>new MongoID($_SESSION['id']),
					'permissions'=>array('admin', 'editor')
				),
				'modules'=>array('news', 'cms', 'images')
			)
		);
		
		return $new_site;
	}
}
?>
