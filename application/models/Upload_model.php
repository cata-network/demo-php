<?php
/**
 * Upload
 */

require_once "resource/aliyun-oss/aliyun-oss-php-sdk-2.0.7.phar";

class Upload_model extends CI_Model {
	public function __construct() {
		$this->load->database();
	}

	// 测试上传文件
	public function upload_file_test() {

		var_dump($_FILES);
		$file_name = $_FILES["file"]["name"]; //原始文件名
		$file_type = $_FILES["file"]["type"]; //
		$file_size = $_FILES["file"]["size"]; //
		$file_path = $_FILES["file"]["tmp_name"]; //新的文件名,带路径
		$ssh_pem = file_get_contents($file_path);

		//初始化链接
		$accessKeyId = "HoFZrmdnBheFen1y";
		$accessKeySecret = "hagWeBWw6s9270Avjjni933KiGvIgh";
		$endpoint = "oss-cn-hangzhou-internal.aliyuncs.com";
		$ossClient = new \OSS\OssClient($accessKeyId, $accessKeySecret, $endpoint);

		//写入文档到oss
		$bucket = "token60";
		$object = $file_name;

		$ossClient->uploadFile($bucket, $object, $file_path);
		return array("url" => "https://token60.oss-cn-hangzhou.aliyuncs.com/" . $object);
	}

	// 上传文件
	public function upload_file() {

		$file_name = $_FILES["file"]["name"];
		// $file_type = $_FILES["file"]["type"];
		// $file_size = $_FILES["file"]["size"];
		$file_path = $_FILES["file"]["tmp_name"];
		// $ssh_pem = file_get_contents($file_path);

		//初始化链接
		$accessKeyId = "LTAIUq11M3dGNyQN";
		$accessKeySecret = "yQA0C4kaBxmT54ySrReBwYf6X0uH3u";
		$endpoint = "oss-cn-beijing.aliyuncs.com";
		$ossClient = new \OSS\OssClient($accessKeyId, $accessKeySecret, $endpoint);


		//写入文档到oss
		$bucket = "yaofei";

		$type = strtolower(substr($file_name, strrpos($file_name, '.') + 1));
		$object = md5(time() . mt_rand(1, 1000000)) . '.' . $type;

		$result = $ossClient->uploadFile($bucket, $object, $file_path);

		// return array("url" => $object);

        return array("bucket"=>$bucket,"object"=>$object,"file_path"=>$file_path,"result"=>$result);
	}

}
