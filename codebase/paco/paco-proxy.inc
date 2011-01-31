<?php

class PacoProxy
{
	const EXECUTE_PERMISSION 	= 'WebMethod';  	// method tag to look for to enable api expose
	
	private $class, $method, $namespace, $error, $output_type;
	private $object, $request, $ignored_params;
	private $start, $end, $version, $use_auth, $auth_tokens;
	
	/**
	 * Constructs the incoming data
	 */
	public function __construct($request=null)
	{
		// performance log
		$this->start = microtime(true);
		// ignored incoming data
		$this->ignored_params 	= array();
		$this->auth_tokens 	= array();
		$this->use_auth 	= true;
		
		// merge the incoming data		
		$defaultRequest = $_GET + $_POST;
		$requestArray = $defaultRequest;
		
		if(!is_null($request))
		{
			$request = trim($request);
			$request = str_replace(array("\n","\t"),'', $request);
			parse_str($request, $requestArray);
		
			$requestArray = $requestArray + $defaultRequest;			
		}
		
		foreach($requestArray as &$element)
		{						
			if(!is_array($element))
			{
				$element = trim($element);
			}
		}
		
		// sort the array so we get some consistency for API hashes.
		// i.e foo=bar&bar=foo, bar=foo&foo=bar actually are the same signature
		ksort($requestArray);
		//
		$this->request = $requestArray;
	}
	
	/**
	 * Tests incoming method request
	 */
	public function is_method($method)
	{
		if (preg_match("/^([a-zA-Z]+)\.([a-zA-Z]+)$/", $method))
		{
			// extract the class and method
			list($this->class, $this->method) = explode('.', $method);
			
			//
			return true;
		}
		return false;
	}
	
	/**
	 * Executes an API request from external sources
	 */
	public function execute($api_key='', $api_hash='')
	{
		
		$method 	= $this->method;
		$derived_class 	= $this->namespace . ucwords($this->class);
		$params		= $this->request;
			
		// ensure the class exists
		if (class_exists($derived_class))
		{
			$rc = new ReflectionClass($derived_class);			

			if ($rc->hasMethod($method))
			{
				$args = array();
				$m = $rc->getMethod($method);
				
				// get the permissions for this api method - we're looking for WebMethod. No rule applied, no execution
				$m_meta = $m->getDocComment();
				
				// test if accessible
				if (!strpos($m_meta, self::EXECUTE_PERMISSION))
				{
					throw new Exception('This method is not executable');
				} 
				// permission to execute this method are set
				else 
				{											
					// get the method's params
					$p = $m->getParameters();
					
					// loop them
					foreach($p as $param)
					{
						// if user has not supplied all required arguments we flag it
						if(!$param->isOptional() && !isset($params[$param->getName()]))
						{
							return new PacoError("Missing required parameter " . $param->getName() . " for method " . $this->class . ".$method", PacoError::ERROR_MISSING_ARGS);
						} 
						else 
						{
							if (isset($params[$param->getName()]))
							{
								// args are sent in order, so create an argument array here to send to the method
								$args[] = $params[$param->getName()];
							} else 
							{
								$args[] = $param->getDefaultValue();
							}
						}
					}
					
					// do the authentication
					$api = new $derived_class;

					if ($this->use_auth)
					{
						
						if (empty($api_key) || empty($api_hash))
						{
							return new PacoError('Authentication keys/hashes are required, but have not been supplied', PacoError::ERROR_SECURITY_ARGS);
						}
						
						// do the auth - this sets the user id, etc for the api calls
						$auth = $api->auth(
							$api_key, 
							$api_hash,
							($this->class . '.' .  $this->method) // must be the full class.method name here as that's what the api expects
						);
					}
					
					if (!$this->use_auth || ($this->use_auth && $auth))
					{
						$response = $m->invokeArgs($api, $args);
					} 
					else 
					{
						$response = new PacoError('Service authentication failed. Check your API key and method hash', PacoError::ERROR_SECURITY_AUTH_FAILED); 
					}
					return $response;
				}
			} 
			else 
			{
				return new PacoError("No such method '$method' in module " . $this->class, PacoError::ERROR_CLASS_NO_METHOD);
			}
		} 
		else 
		{
			return new PacoError("No such module '" . $this->class ."' exists", PacoError::ERROR_NO_CLASS);
		}

	}
	
	public function set_auth($auth)
	{
		if (is_bool($auth))
		{
			$this->use_auth = $auth;
		}
		else
		{
			throw new Exception('set_auth requires a boolean value');
		}
	}
	
	
	/**
	 * Outputs data in structured format
	 */
	public function output($response, $callback='', $format=false)
	{
		$data 	= array();
		$status = 'ok';
		
		$params = $this->request;
		
		if (!is_array($response) && get_class($response) == 'PacoError')
		{
			$status = 'error';
			$data	= array('error'=>$response->get());
		} 
		else 
		{
			$data = $response;
		}
		
		foreach(array_keys($params) as $p)			
		{
			if (in_array($p, array('sort', 'filter')))
			{
				$rule = array();
				switch ($p)
				{
				case 'sort':
					$rule = PacoUtils::sort_rules($params[$p]);
					break;
				case 'filter':
					$rule = PacoUtils::filter_rules($params[$p]);
					break;
				}
				$params[$p] = $rule;
			}
		}
		
		if (array_key_exists('endpoint', $params))
		{
			$this->version = $params['endpoint'];
		}
		
		// loop over params, remove any that we don't want to send back to the user
		foreach($params as $key=>$val)
		{		
			if (in_array($key, $this->ignored_params))
			{
				unset($params[$key]);
			}
		}
		
		// output bucket
		$output = array
		(			
			'response' => array
			(
				'status'=>$status,
				'content_type'=>$this->output_type,
				'execution_time'=>$this->get_execution_time(),
				'endpoint'=>$this->version,
				'params'=>$params
			)
		);
		
		// keys to remove from the dataset
		$exclude 	= array('_id');
		
		//var_dump($data);
		
		if (is_array($data))
		{
			// merge the trees 			
			$output = array_merge($output, $data);
		}
		
		// if json-p is required, append here
		if (!empty($callback))
		{		
			// check callback - only allow alphanumerics
			$callback = preg_replace("/([^a-zA-Z0-9\_])/", "", $callback);
			
			// append callback into json return data
			$json = "$callback($json)"; 
		}
		
		switch ($this->output_type)
		{
		case 'xml': 
			// returns xml header
			header('Content-type: text/xml;');
			break;
		case 'json': 
		default: 
			// print straight out as json data
			header('Content-type: application/json;');
			break;
		}
		
		// encode arrays into json
		$json = json_encode($output);
		
		// if formatting specified, apply it
		if ($format)
		{
			$json = PacoUtils::json_format($json);
		}
		
		// and out...
		print($json);
	}
	
	private function set_output($type='json')
	{
		if (in_array($type, array('json', 'xml')))
		{
			$this->output_type = $type;
		} 
		else
		{
			throw new Exception('Invalid output type');
		}
	}
	
	private function get_execution_time()
	{
		$this->end = microtime(true);
		return round($this->end - $this->start, 6);
	}
	
	public function results()
	{
		if ($this->error)
		{
			print('er');
		}
		if (!$this->results)
		{
			return false;
		}
		return $this->results;
	}
	
	public function set_namespace($namespace)
	{
		$this->namespace = $namespace;
	}
	
	public function set_ignored_params(array $params)
	{
		$this->ignored_params = $params;
	}
}

