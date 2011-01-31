<?php
/**
 * API handler file. Responds to all API requests in any form.
 *
 * @author Dave Bullough
 */

include('../codebase/shared/database.php');
include('../codebase/shared/form.php');
include('../codebase/paco/paco-proxy.inc');
include('../codebase/paco/paco-api.inc');

// grab incoming data, try post and get
$endpoint	= Form::both('endpoint');
$key 		= Form::both('api_key');
$hash		= Form::both('api_hash');
$api_method	= Form::both('method');
$callback 	= Form::both('callback');
$format_json 	= true;

// setup a proxy object, this will call the api
$proxy = new PacoProxy();

if ($key && $api_method && $hash)
{
	$proxy->set_namespace('Paco');
	$proxy->set_auth(true);
	$proxy->set_ignored_params(array('endpoint'));
	//
	if ($proxy->is_method($api_method))
	{
		try
		{
			$result = $proxy->execute($key, $hash);
			$proxy->output($result, $callback, $format_json);
		} 
		catch (Exception $e)
		{		
			$proxy->output(
				new PacoError($e->getMessage(), $e->getCode()), 
				$callback, 
				$format_json
			);
		}
	}
	else
	{
		$proxy->output(new PacoError('Method construct is not formed correctly'), $callback, $format_json);
	}
}
else
{
	$proxy->output(new PacoError('Missing minimum parameters api_key, api_hash or method'), $callback, $format_json);
}
?>
