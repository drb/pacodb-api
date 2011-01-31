<?php

/**
 * Exposes a data construct as JSON
 *
 * @package pako
 */
 
class PacoHtml extends PacoAPI
{
	
	private $page_html = array('basic'=>'basic data');
	
	/**
	 * Returns page data for a specific page under a domain
	 *
	 * [WebMethod]
	 */
	public function get($uri, $host=null)
	{		
		$html = false;
		$page = array();

		$site = PacoUtils::transform(
			$this->find_one(
				'sites',
				array(
					'host'=>$host
				)
			)
		);
		
		if ($site)
		{
			$site = $site[0];
			
			$page = PacoUtils::transform(
					$this->find(
						'pages',
						array(
							'site_id' => new MongoId($site['id']),
							'url'=>$uri
						)
					)
			);
			
			if ($page)				
			{
				$page = $page[0];
				
			}
		}
		
		return array('page'=>$page);
	}

	
	/**
	 * Returns a requested uri, rendered as html
	 * 
	 * [WebMethod]
	 */
	public function render($uri, $incoming_params=null, $host=null)
	{
		$html = '';
		$request = array();
		$status = 200;
		$apps_root = '/home/dave/Websites/paco/apps/sites/' . $host;
		
		if ($uri != '/')
		{
			$uri = ltrim($uri, '/');
		}
		
		// test for incoming params from the connector (qs/post data provided to the site connector)
		if (!is_null($incoming_params))
		{
			$request = @unserialize($incoming_params);			
		}
		
		if (is_dir($apps_root))
		{
			// check the site belongs to this user
			$site = $this->find(
				'sites',
				array(
					'owner_id'=>$this->user_id,
					'host'=>$host
				)
			);
			
			if ($site)
			{
				
				$site = PacoUtils::transform($site);
				
				// get page
				$page = $this->find(
					'pages',
					array(
						'site_id'=>new MongoId($site['id']),
						'url'=>$uri
					)
				);
								
				if ($page)
				{
					$page = PacoUtils::transform($page);
					
					$template = $page['template'];
					$template_path = $apps_root . '/templates/' . $template;
					
					if(file_exists($template_path))
					{					
						$vars = array();
						
						// load template engine
						$dwoo = new Dwoo(); 
						
						// add the plugins dir
						$dwoo->getLoader()->addDirectory('../codebase/plugins');
						 
						// data object, populate with default data, page bindings and environment data
						$data = new Dwoo_Data(); 
						foreach($site['environment']['variables'] as $key=>$value)
						{
							$vars[$key] = $value;
						}
												
						// assign data in blocks @todo sanitise the blocks so we don't expose everything (i.e. only show miniumum values)
						$data->assign('vars', 	$vars);
						$data->assign('page', 	$page);						
						$data->assign('site', 	$site);
						$data->assign('request', $request);
						
						$data->assign('paco', array
							(
								'version'=>self::API_VERSION,
								'homepage'=>self::API_HOMEPAGE
							)
						);
						
						// if there's html assocoated, add to dwoo
						if (array_key_exists('html', $page))
						{
							$data->assign('html', 	$page['html']);
						}
												
						$tpl = new Dwoo_Template_File($template_path);
						$tpl->forceCompilation();
						
						// get the transformed data here, return to user
						try
						{
							$html = $dwoo->get($tpl, $data);
						} 
						catch (Exception $e)
						{
							$html = $e->getMessage();
						}
					} 
					else 
					{
						return new PacoError('The supplied template is not one associated with this site', PacoError::ERROR_HTML_TEMPLATE_NOT_FOUND);
					}
				} 
				else 
				{
					$status = 404;
					$html = 'The requested resource ' . $uri . ' could not be found';
				}
			} 
			else 
			{
				return new PacoError('Host does not match API credentials, or does not exist');
			}
		} 
		else 
		{
			return new PacoError('No such host');
		}
		return array(
			'http_status'=>$status, 
			'http_request_params'=>$request, 
			'page_status'=>array(
				'last_modify'=>'somedate',
				'hash'=>md5('something')
			), 
			'html'=> $html
		);
	}
	
	protected function html_block($id)
	{
		return implode(',', array_keys($this->page_html));
		return $id;
	}
}
?>
