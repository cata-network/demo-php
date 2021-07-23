<?php
/**
 * Created by PhpStorm.
 * User: ckx
 * Date: 2018/11/6
 * Time: 下午4:00
 */

class Market_goods extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model("Market_goods_model");
        $this->load->model("Rest_model");
    }

    /****************集市商品列表*******************/
    public function get_market_goods_list()
    {
        $start=$this->Rest_model->get_request("start");
        $limit=$this->Rest_model->get_request("limit");
        $result = $this->Market_goods_model->get_market_goods_list($start,$limit);
        $this->Rest_model->print_rest_json($result);
    }

    //模糊搜索 商品 title brief tag
    public function search_like_market_goods(){
		$word=$this->Rest_model->get_request("word");
		$start=$this->Rest_model->get_request("start");
		$limit=$this->Rest_model->get_request("limit");
		$result = $this->Market_goods_model->search_like_market_goods($word, $start, $limit);
		$this->Rest_model->print_rest_json($result);
	}

    //搜索集市物品
    public function search_market_goods(){
		$word=$this->Rest_model->get_request("word");
		$start=$this->Rest_model->get_request("start");
		$limit=$this->Rest_model->get_request("limit");
		$result = $this->Market_goods_model->search_market_goods($word, $start, $limit);
		$this->Rest_model->print_rest_json($result);
	}

	public function get_market_goods_info()
	{
		$id = $this->Rest_model->get_request("id");
		$result = $this->Market_goods_model->get_market_goods_info($id);
		$this->Rest_model->print_rest_json($result);
	}

    //获取用户交易记录
    public function get_user_orders()
    {
        $email = $this->Rest_model->get_request("email");
        $start = $this->Rest_model->get_request("start");
        $limit = $this->Rest_model->get_request("limit");
        $result = $this->Market_goods_model->get_user_orders("", $email, $start, $limit);
        $this->Rest_model->print_rest_json($result);
    }

	//获取商品交易记录
	public function get_goods_orders()
	{
		$create_id = $this->Rest_model->get_request("create_id");
		$start = $this->Rest_model->get_request("start");
		$limit = $this->Rest_model->get_request("limit");
		$result = $this->Market_goods_model->get_user_orders($create_id, "", $start, $limit);
		$this->Rest_model->print_rest_json($result);
	}

    //获取用户余额
    public function get_balance()
    {
		$email = $this->Rest_model->get_request("email");
        $result = $this->Market_goods_model->get_balance($email);
        $this->Rest_model->print_rest_json($result);
    }

    /************私有函数***************/
    //登陆态检测
    private function check_login_state()
    {
        $email = $this->Rest_model->get_request("email");
        //检查用户是否登录,如果未登录则直接跳转到错误页面
        // $this->user_provider->check_login_restful($email);
        return $email;
    }

}
