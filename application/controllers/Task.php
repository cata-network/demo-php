<?php
require_once 'vendor/autoload.php';
use Abraham\TwitterOAuth\TwitterOAuth;

session_start();

/**
 * 任务相关
 *
 */
class Task extends CI_Controller {
	public function __construct() {
		parent::__construct();

		$this->config->load('twitter');

		$this->load->model("Candy_model");
		$this->load->model("Task_model");
		$this->load->model('User_model');
		$this->load->model("Rest_model");
	}

	public function auth() {
		$consumer_key = $this->config->item('consumer_token');
        $consumer_secret = $this->config->item('consumer_secret');
		$url_callback = $this->config->item('url_callback');
        
		// create TwitterOAuth object
		$twitteroauth = new TwitterOAuth($consumer_key, $consumer_secret);

		// request token of application
		$request_token = $twitteroauth->oauth(
			'oauth/request_token', [
				'oauth_callback' => $url_callback,
			]
		);

        // throw exception if something gone wrong
		if ($twitteroauth->getLastHttpCode() != 200) {
			throw new \Exception('There was a problem performing this request');
		}

        // save token of application to session
		$_SESSION['oauth_token'] = $request_token['oauth_token'];
		$_SESSION['oauth_token_secret'] = $request_token['oauth_token_secret'];

        // generate the URL to make request to authorize our application
		$url = $twitteroauth->url(
			'oauth/authorize', [
				'oauth_token' => $request_token['oauth_token'],
			]
		);

        // and redirect
		header('Location: ' . $url);
	}

	// 获取项目信息
	public function get_projects() {
		// $email = $this->check_login_state();
		$result = $this->Task_model->get_projects();
		$this->Rest_model->print_rest_json($result);
	}

	// 获取一个项目信息
	public function get_project() {
		$email = $this->check_login_state();
		$project_id = $this->Rest_model->get_request("project_id");
		$result = $this->Task_model->get_project($project_id);
		$this->Rest_model->print_rest_json($result);
	}

	// 创建项目
	public function create_project() {
		$email = $this->Rest_model->get_request("email");

		// $this->User_model->check_login_restful($email);
		// $email = $this->check_login_state();

		$logo = $this->Rest_model->get_request("logo");
		$name = $this->Rest_model->get_request("name");
		$intro = $this->Rest_model->get_request("intro");
		$token_name = $this->Rest_model->get_request("token_name");
		$project_type = $this->Rest_model->get_request("project_type");
		$project_type = $this->Rest_model->get_request("end_time");
		$end_time = $this->Rest_model->get_request("end_time");

		$result = $this->Task_model->create_project($email, $logo, $name, $intro, $token_name, $project_type);
		$this->Rest_model->print_rest_json($result);
	}

	// 更新项目
	public function edit_project() {
		$id = $this->Rest_model->get_request("id");

		$email = $this->Rest_model->get_request("email");

		// $this->User_model->check_login_restful($email);
		// $email = $this->check_login_state();

		// $logo = $this->Rest_model->get_request("logo");
		// $name = $this->Rest_model->get_request("name");
		// $intro = $this->Rest_model->get_request("intro");
		// $token_name = $this->Rest_model->get_request("token_name");
		// $project_type = $this->Rest_model->get_request("project_type");

		$result = $this->Task_model->edit_project($id);
		$this->Rest_model->print_rest_json($result);
	}

	// 删除项目
	public function delete_project() {
		// $email = $this->Rest_model->get_request("email");

		$project_id = $this->Rest_model->get_request("project_id");

		$result = $this->Task_model->delete_project($project_id);
		$this->Rest_model->print_rest_json($result);
	}

	/**************** 邀请码 *******************/
	// 创建邀请码
	public function create_code() {
		$user_id = $this->Rest_model->get_request("user_id");
		$result = $this->Task_model->create_code($user_id);
		$this->Rest_model->print_rest_json($result);
	}

	// 邀请码
	public function decode() {
		$code = $this->Rest_model->get_request("code");
		$result = $this->Task_model->decode($code);
		$this->Rest_model->print_rest_json($result);
	}

	/************私有函数***************/
	//登陆态检测
	private function check_login_state() {
		$email = $this->Rest_model->get_request("email");
		//检查用户是否登录,如果未登录则直接跳转到错误页面
		// $this->user_provider->check_login_restful($email);
		return $email;
	}

}