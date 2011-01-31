<?php
/**
 * Error objects
 */
class PacoError
{
	
	const ERROR_EMPTY_ARGS 			= 201;
	const ERROR_MISSING_ARGS 		= 302;
	const ERROR_CLASS_NO_METHOD 		= 303;
	const ERROR_NO_CLASS 			= 304;
	const ERROR_SECURITY_ARGS 		= 401;
	const ERROR_SECURITY_AUTH_FAILED 	= 402;
	const ERROR_HTML_TEMPLATE_NOT_FOUND	= 501;
	const ERROR_HTML_COMPILE_FAIL		= 502;
	
	private $error;
	
	public function __construct($msg, $code=null)
	{
		$this->error['message'] = $msg;
		$this->error['code'] = $code;
	}
	
	/**
	 * Takes mongo data and transforms into more normal data structures
	 *
	 */
	public function get()
	{
		return $this->error;
	}
}
?>
