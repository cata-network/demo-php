<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Rest {

	// Success

	const HTTP_OK = 200;

	// Redirection

	const HTTP_MULTIPLE_CHOICES = 300;
	const HTTP_MOVED_PERMANENTLY = 301;
	const HTTP_FOUND = 302;
	const HTTP_SEE_OTHER = 303;

	// Client Error

	const HTTP_BAD_REQUEST = 400;

	const HTTP_UNAUTHORIZED = 401;
	const HTTP_PAYMENT_REQUIRED = 402;

	const HTTP_FORBIDDEN = 403;

	const HTTP_NOT_FOUND = 404;

	// Server Error

	const HTTP_INTERNAL_SERVER_ERROR = 500;

	const HTTP_NOT_IMPLEMENTED = 501;
	const HTTP_BAD_GATEWAY = 502;
	const HTTP_SERVICE_UNAVAILABLE = 503;

	protected $CI;

	public function __construct() {
		$this->CI = &get_instance();

        // header("content-type:text/json;charset=utf-8");
		//header('Access-Control-Allow-Origin: *');
		header('Access-Control-Allow-Methods: GET, POST, PATCH, PUT, DELETE, OPTIONS');
		// header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token, X-CSRF-TOKEN');
		header('Access-Control-Allow-Headers: X-Content-Type-Options, Cache-Control, Content-Type, Authorization, X-Requested-With');
		// header('Access-Control-Allow-Headers: X-ACCESS_TOKEN, Access-Control-Allow-Origin, Authorization, Origin, x-requested-with, Content-Type, Content-Range, Content-Disposition, Content-Description, Access-Token, X-Auth-Token, X-CSRF-TOKEN');

		// 设置no-cache
		// header("Pragma:no-cache");
		// header("Cache-Control: no-cache, must-revalidate");
		// header("Access-Control-Allow-Credentials: true");

        // OPTIONS 备用 HEADER
		// header('Access-Control-Allow-Origin: *');
		// header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
		// header('Access-Control-Allow-Headers: Authorization, Origin, Content-Type, Accept');
		// 避免 OPTIONS 请求时触发逻辑。
		if (strtoupper($_SERVER['REQUEST_METHOD']) == 'OPTIONS') {
			exit;
		}
	}

	// 输出
	public function response($data = NULL, $http_code = NULL, $continue = FALSE) {

		if ($http_code !== NULL) {
			// So as to be safe later on in the process
			$http_code = (int) $http_code;
		}

		if ($data === NULL && $http_code === NULL) {
			$http_code = self::HTTP_NOT_FOUND;
		}

		$http_code > 0 || $http_code = self::HTTP_OK;

		// $result = array("status" => $http_code, "message" => "update success");

		$this->CI->output
			->set_status_header($http_code)
		// ->set_header('Access-Control-Allow-Origin: *')
		// ->set_header('Cache-Control: no-store, no-cache, must-revalidate')
		// ->set_header('Pragma: no-cache')
		// ->set_header('Expires: 0')
			->set_content_type('application/json', 'utf-8')
			->set_output(json_encode($data))
		// ->set_output(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES))
			->_display();
		exit;
	}
}
