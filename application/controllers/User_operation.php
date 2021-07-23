<?php

//用户操作
class User_operation extends CI_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->model('user_operation_model');
		$this->load->model('rest_model');
	}

	/********************************发布************************************/
	//用户上传一个图片至 cos 服务器,返回一个图片地址
	public function upload_pic()
	{
		$pic = $this->rest_model->get_request("pic");//用户图片，base64图片字符串
		$email = $this->rest_model->get_request("email");
		$templet_id = $this->rest_model->get_request("templet_id");	//涂鸦模板ID
        $tag = $this->rest_model->get_request("tag");	//标签
        $title = $this->rest_model->get_request("title");	//标题

        $result = $this->user_operation_model->upload_pic($pic, $email, $templet_id,
                        $tag, $title);
		$this->rest_model->print_rest_json($result);
	}

	//用户图片 入库 user_item
	public function stock_pic()
	{
		$create_id = $this->rest_model->get_request("create_id");
		$email = $this->rest_model->get_request("email");
		$title = $this->rest_model->get_request("title");
		$brief = $this->rest_model->get_request("brief");
		$price = $this->rest_model->get_request("price");
        $tag = $this->rest_model->get_request("tag");

        $result = $this->user_operation_model->stock_pic($create_id, $email, $title, $brief, $price, $tag);
		$this->rest_model->print_rest_json($result);
	}

	//上架发布
	public function publish_pic(){
		$create_id = $this->rest_model->get_request("create_id");
		$email = $this->rest_model->get_request("email");
		$title = $this->rest_model->get_request("title");
		$brief = $this->rest_model->get_request("brief");
		$price = $this->rest_model->get_request("price");
        $tag = $this->rest_model->get_request("tag");

        $result = $this->user_operation_model->publish_pic($create_id, $email, $title, $brief, $price,$tag);
		$this->rest_model->print_rest_json($result);
	}

	/********************************购买************************************/

	//购买物品
	public function buy_pic(){
		$create_id = $this->rest_model->get_request("create_id");
		$email = $this->rest_model->get_request("email");
		$result = $this->user_operation_model->buy_pic($create_id, $email);
		$this->rest_model->print_rest_json($result);
	}

	/********************************查询服务************************************/
	//查询用户创作列表 user_item_create
	public function get_user_item_create_list(){
		$email = $this->rest_model->get_request("email");
		$start = $this->rest_model->get_request("start");
		$limit = $this->rest_model->get_request("limit");
		$result = $this->user_operation_model->get_user_item_create_list($email, $start, $limit);
		$this->rest_model->print_rest_json($result);
	}

	//查询用户创作详情 user_item_create
	public function get_user_item_create_info(){
		$create_id = $this->rest_model->get_request("create_id");
		$email = $this->rest_model->get_request("email");
		$result = $this->user_operation_model->get_user_item_create_info($create_id, $email);
		$this->rest_model->print_rest_json($result);
	}

	//查询用户库存列表 user_item
	public function get_user_item_list(){
		$email = $this->rest_model->get_request("email");
		$start = $this->rest_model->get_request("start");
		$limit = $this->rest_model->get_request("limit");
		$result = $this->user_operation_model->get_user_item_list($email, $start, $limit);
		$this->rest_model->print_rest_json($result);
	}

	//查询用户库存图片详情 user_item_info
	public function get_user_item_info(){
		$email = $this->rest_model->get_request("email");
		$id = $this->rest_model->get_request("id");
		$result = $this->user_operation_model->get_user_item_info($email, $id);
		$this->rest_model->print_rest_json($result);
	}


	public function test(){
		$data = array(
			'email' => 'lf@163.com',
		);
		$this->db->insert('member',$data);
		$id=$this->db->insert_id('member');
		var_dump($id);
	}
}
