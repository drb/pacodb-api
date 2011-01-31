<?php

require_once('classes/paco.bucket.php');
require_once('classes/paco.data.php');
require_once('classes/paco.html.php');
require_once('classes/paco.users.php');
require_once('classes/paco.utils.php');
require_once('classes/paco.sites.php');
require_once('classes/paco.error.php');

// vendor code
require_once('../codebase/vendor/dwoo/dwooAutoload.php');

// paco API class
class PacoAPI
{
	const API_VERSION 	= 0.1;			// api version - @todo this should be based on the SVN revision really
	const API_DEFAULT_LIMIT = 25;  			// default record limit
	const API_HOMEPAGE 	= 'http://pacocms.com'; // default record limit
	const NSPACE 		= 'Paco';  		// class namespace

	protected 	$db, $db_connected, $host, $tpl_consts;
	private 	$settings, $result, $error, $use_auth;
	private 	$version;
	public 		$data, $user, $user_id;

	//
	private static 	$instance;


	public function __construct()
	{
		// basic user is kept in this array
		$this->user = array(
			'id'=>null
		);

		// other
		$this->use_auth 	= true;
		$this->tpl_consts 	= array(
			'version'=>self::API_VERSION,
		);

		//
		$this->db_connected = false;

		// @todo this need to be loaded through some kind of generic loader, i.e. app->get_some_file('settings.ini'); that polls a shared directory
		$this->settings = parse_ini_file($_SERVER['DOCUMENT_ROOT'] . '/settings.ini', true);

		// set the host to whereever this is being called from, by default
		$this->set_host();

		// ref to the class so we can access it externally
		self::$instance = $this;
	}

	/**
	 * Connects to our DB of choice
	 */
	private function connect()
	{
		if ($this->db_connected) return;

		// create new db
		$this->db = new Database();

		try
		{
			// auth settings
			$this->db->set_credentials
			(
				$this->settings['mongo']['server'],
				$this->settings['mongo']['user'],
				$this->settings['mongo']['pass'],
				$this->settings['mongo']['db']
			);

			$this->db_connected = true;
		}
		catch(Exception $e)
		{
			throw $e;
		}


	}

	/**
	 * Singleton!
	 */
	public static function instance()
	{
		if (!isset(self::$instance))
		{
		    $c = __CLASS__;
		    self::$instance = new $c;
		}

		return self::$instance;
	}

	public static function call($method)
	{
		if (isset(self::$instance))
		{
			$foo = new ReflectionObject();
		}
	}

	protected function get_host()
	{
		return $this->host;
	}

	public function set_host($host=null)
	{
		$this->host = $this->normalise_host($host);
	}

	private function normalise_host($host)
	{
		if (is_null($host)) return $this->normalise_host($_SERVER['SERVER_NAME']);

		$host = strtolower($host);
		$host = ltrim($host, 'http://');
		$host = ltrim($host, 'www.');
		$host = rtrim($host, '/');

		return $host;
	}

	public function get_last_error()
	{
		return $this->error[sizeof($this->error) -1];
	}

	public function auth($key, $request_hash, $request_method)
	{
		// connect to db
		$this->connect();

		$user = $this->db->find(
			'owners',
			array(
				'api.key'=>$key
			),
			array(),
			array(),
			1
		);

		if (!$user)
		{
			throw new Exception('API authentication failed. Ensure all credentials were supplied.');
		}
		else
		{
			$this->user = PacoUtils::transform($user);

			// handy to have the api user persisted
			$this->user_id = new MongoId($this->user['id']);

			//print($this->user_id);

			// get the api secret
			$user_secret = $this->user['api']['secret'];

			//print("Secret " . $user_secret . "\n");
			//print("Method " . $request_method . "\n");

			$expected_hash  = md5($user_secret . $request_method);

			//print("Expected: $expected_hash\n");
			//print("Got: $request_hash\n");

			if ($this->use_auth && $expected_hash != $request_hash)
			{
				$this->error = new PacoError('Authentication failed hash check', 190);
				return false;
			}

			return true;
		}
	}

	public function set_error($err, $code=null)
	{
		$this->error[] = array($err, $code);
	}

	public function find(
			$collection,
			array $filter=array(),
			array $fields=array(),
			array $sort=array(),
			$limit=null
		)
	{
		$this->connect();
		return $this->db->find($collection, $filter, $fields, $sort, $limit);
	}
}
?>
