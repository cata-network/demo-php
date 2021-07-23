<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once 'resource/cos-php-sdk/cos-sdk-v5.phar';

class COS {

	// 指向控制器实例
	protected $CI;

	public $cosClient;
    public $bucket = "chia1-1300721637";

	public function __construct() {
		$this->CI = &get_instance();

		$secretId = "AKIDGn4DEBlMXRYT9sr0CIAnQoyDDzczuxYW"; //"云 API 密钥 SecretId";
		$secretKey = "ETSBhzoQuRuA9sLY4LhxxhBYjfM5jFcn"; //"云 API 密钥 SecretKey";
		$region = "ap-shanghai"; //设置一个默认的存储桶地域

		$this->cosClient = new Qcloud\Cos\Client(
			array(
				'region' => $region,
				// 'schema' => 'https', //协议头部，默认为http
				'credentials' => array(
					'secretId' => $secretId,
					'secretKey' => $secretKey)));
	}

}
