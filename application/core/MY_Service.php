<?php
class MY_Service extends MY_Loader
{
	public function __construct()
	{

	}

	function __get($key)
	{
		$CI = &get_instance();
		return $CI->$key;


	}
}
