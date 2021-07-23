<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
| -------------------------------------------------------------------------
| Hooks
| -------------------------------------------------------------------------
| This file lets you define "hooks" to extend CI without hacking the core
| files.  Please see the user guide for info:
|
|	https://codeigniter.com/user_guide/general/hooks.html
|
*/
class MyClass{

	public function __construct()
	{
		$this->CI = &get_instance();
	}
	public function userCheck(){
//		$this->checkToken();
//		$this->checkLoginState();
	}
	//token安全检测
	public function checkToken(){
		$token = $this->CI->input->server('HTTP_TOKEN');
		$phone = $this->CI->input->server('HTTP_PHONE');
		if($token=="") {
			if (isset($_REQUEST["token"]))//如果没输入$token，检测cookie是
			{
				$token = $_REQUEST["token"];
			}
		}
		if($phone=="") {
			if (isset($_REQUEST["phone"]))//如果没输入$token，检测cookie是
			{
				$phone = $_REQUEST["phone"];
			}
		}
		$this->CI->_userLoginStatus = false;
		$this->CI->_userLoginPhone = $this->CI->_userLoginToken = '';
		if ( $token )
		{
			//检查token
			$linux_time = date('Y-m-d H:i:s',time() - 60*60*24);
			$sql = "select * from user_token where action_time > '{$linux_time}' and token='{$token}' and account='{$phone}' order by action_time DESC limit 1 ";
			$tokenInfo = $this->CI->db->query($sql)->result_array();
			//var_dump($tokenInfo);
			if ( empty($tokenInfo))
			{
				$result = array("status" => -1, "msg" => "用户信息错误，请重新登录","results"=>array());
				header("content-type:text/json;charset=utf-8");
				echo json_encode($result, JSON_UNESCAPED_UNICODE);
				exit();//退出，不继续执行
			}
			$_GET["token"] = $_POST["token"] = $_REQUEST["token"] = $tokenInfo[0]['token'];
			$_GET["phone"] = $_POST["phone"] = $_REQUEST["phone"] = $tokenInfo[0]['account'];
			$this->CI->_userLoginInfo['phone'] = $this->CI->_userLoginPhone = $tokenInfo[0]['account'];
			$this->CI->_userLoginInfo['token'] = $this->CI->_userLoginToken = $tokenInfo[0]['token'];
			$this->CI->_userLoginStatus = true;
			//更新用户在线表
			$tkId = $tokenInfo[0]['id'];
			$sql = "update user_token set action_time = now() where id='{$tkId}'";
			$this->CI->db->query($sql);
		}else{
		}

	}
	//登陆状态检测
	public function checkLoginState()
	{
		$con = strtolower($this->CI->router->fetch_class());//获取控制器名
		$func = strtolower($this->CI->router->fetch_method());//获取方法名

		//是否需要登录校验
		$authorization = false;
		$conf = $this->actionConf();
		$conf = $conf['arrAuthorization'];
		$arrAction = isset($conf[$con]) ? $conf[$con]:'';
		if ($arrAction == '*') {
			$authorization = true;
		} elseif (is_array($arrAction)) {
			foreach ($arrAction as $k => $v) {
				$arrAction[$k] = strtolower($v);
			}
			if (in_array($func, $arrAction)) {
				$authorization = true;
			}
		}

		//登录校验
		if ($authorization === true) {
			if ($this->CI->_userLoginStatus === false) {
				$result = array("status" => -1, "msg" => "需要用户登录","results"=>array());
				header("content-type:text/json;charset=utf-8");
				echo json_encode($result, JSON_UNESCAPED_UNICODE);
				exit();//退出，不继续执行
			}
		}
	}


	public function actionConf(){
		/**
		 * 配置action权限检查
		 */
		return array(
			//不需要登录的action
			'arrNotNeedLogin' => array(),
			//需要登录的action
			'arrNeedLogin' => array(),
			//需要登录校验的action
			'arrAuthorization' => array(
				"monitor"=>'*',
				"user_item"=>'*',
				"pay"=>'*',
				"user"=>array('revise_password','get_user_info','update_user_info'),
			),
			//不用校验的action白名单
			'arrWhiteAction' => array(
				'user'=>		array('getWxTicket'),
			),
			//不需要过滤标签的action
			'arrNoHtml' => array(
				'post'       =>array('info'),
			),

		);
	}
}
