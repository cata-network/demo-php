<?php

class adminlogin_service extends MY_Service
{
	private $noNeedLoginMethod = ['login'];
	private $menuAuth = [
		// 用户管理
		1 => [
			'get_users' => 1,
			'get_user_info' => 1,
			'update_user_info' => 3,
		],
		// 会员配置
		2=>[
			'get_products' => 1,
			'get_level_detail' => 1,
			'update_level_price' => 3,
			'update_level_detail' => 3,
		],
		// 兑换码
		3=>[
			'get_ticket_list' => 1,
			'get_ticket_use' => 1,
			'get_ticket_use_excel' => 1,
			'get_ticket' => 2,
		],
		// banner
	];

	public function __construct()
	{
		$this->load->model("user_model");

	}

	public function checkAuth()
	{
		if (!in_array($this->router->fetch_method(), $this->noNeedLoginMethod)) {
			if (!$this->loginMiddleware()) {
				return jsonFormat(401, '需要用户登录');
			};
		}
	}



	public function loginMiddleware()
	{
		$token = $this->input->get_request_header('Access-Token');
		if (empty($token)){ //如果没输入$token，检测cookie是
			$token = $this->input->cookie('token');
		}

		if (empty($token)) {
			return false;
		} else {
			//检查token
			$sql = "select * from user_token where token='{$token}'";
			$result = $this->db->query($sql, $token);
			if (!$result) {
				return false;
			}
			$result = $result->row_array();

			if ( strtotime($result['login_time'])+86400 < time() )
			{
				// 过期
				return false;
			}else{
				// 更新用户信息
				$this->userRoleInfo = [];


				return true;
			}

		}
	}



}
