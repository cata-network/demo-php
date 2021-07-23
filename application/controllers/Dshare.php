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


    /****************上传纸条信息*******************/

    //上传纸条详情
    public function upload_note_content()
    {
        //$data = file_get_contents("php://input"); //取得json数据
        //$data = json_decode($data, TRUE);
        $email = $this->Rest_model->get_request("email");
        $title = $this->Rest_model->get_request("title");
        $content = $this->Rest_model->get_request("content");
        $price = $this->Rest_model->get_request("price");
        $imgUrl = $this->Rest_model->get_request("img_url");
        $brief = $this->Rest_model->get_request("brief");

        $result = $this->Dshare_model->upload_note_content($title,$content,$price,$email,$imgUrl,$brief);
        $this->Rest_model->print_rest_json($result);
    }

    //上传文件
    public function upload_file()
    {
        $result = $this->Dshare_model->upload_file();
        $this->Rest_model->print_rest_json($result);
    }


    /****************纸条列表*******************/
    public function get_market_goods_list()
    {
        //$email = $this->check_login_state();
        //$password = $this->Rest_model->get_request("password");
        $start=$this->Rest_model->get_request("start");
        $limit=$this->Rest_model->get_request("limit");
        $result = $this->Market_goods_model->get_market_goods_list($start,$limit);
        $this->Rest_model->print_rest_json($result);
    }


    //获取用户交易记录
    public function get_user_orders()
    {
        $email = $this->check_login_state();
        $start = $this->Rest_model->get_request("start");
        $limit = $this->Rest_model->get_request("limit");
        $result = $this->Dshare_model->get_user_orders($email, $start, $limit);
        $this->Rest_model->print_rest_json($result);
    }
    //获取用户纸条(我的纸条)
    public function get_mynote()
    {
        $email = $this->check_login_state();
        $start = $this->Rest_model->get_request("start");
        $limit = $this->Rest_model->get_request("limit");
        $result = $this->Dshare_model->get_mynote($email, $start, $limit);
        $this->Rest_model->print_rest_json($result);
    }


    //获取用户余额(暂时未用)
    public function get_balance_x()
    {
        $email = $this->check_login_state();
        $result = $this->Dshare_model->get_balance_x($email);
        $this->Rest_model->print_rest_json($result);
    }
    //获取用户余额（加和为两部分，加延迟发放的积分）
    public function get_balance()
    {
        $email = $this->check_login_state();
        $result = $this->Dshare_model->get_balance($email);
        $this->Rest_model->print_rest_json($result);
    }

    /****************纸条详情*******************/
    //未购买时的详情页
    public function get_note_details()
    {
        //$email = $this->check_login_state();
        //$password = $this->Rest_model->get_request("password");
        $id =  $this->Rest_model->get_request("id");
        $result = $this->Dshare_model->get_note_details($id);
        $this->Rest_model->print_rest_json($result);
    }

    //已购买用户可见详情页
    public function user_get_note_details()
    {
        //$email = $this->check_login_state();
        $id =  $this->Rest_model->get_request("id");
        $result = $this->Dshare_model->user_get_note_details($id);
        $this->Rest_model->print_rest_json($result);
    }

    /****************微信服务*******************/
    //获取用户openid
    public function get_openid()
    {
        //$email = $this->check_login_state();
        $code =  $this->Rest_model->get_request("code");
        //$appid =  $this->Rest_model->get_request("appid");
        //$secret =  $this->Rest_model->get_request("code");
        $result = $this->Dshare_model->get_openid($code);
        $this->Rest_model->print_rest_json($result);
    }

    /****************交易*******************/
    //购买(web端)
    public function item_transaction()
    {
        //$email = $this->check_login_state();
        $id =  $this->Rest_model->get_request("id");
        //$buyer =$email;
        $buyer = $this->Rest_model->get_request("email");
        $result = $this->Dshare_model->item_transaction($id,$buyer);
        $this->Rest_model->print_rest_json($result);
    }

    /****************任务页面*******************/
    //任务列表,(暂时未用,前版）
    public function get_task_list_x()
    {
        //$email = $this->check_login_state();
        //$buyer =$email;
        $result = $this->Dshare_model->get_task_list_x();
        $this->Rest_model->print_rest_json($result);
    }

    //任务列表，做了的任务不显示
    public function get_task_list()
    {
        $email = $this->check_login_state();
        $result = $this->Dshare_model->get_task_list($email);
        $this->Rest_model->print_rest_json($result);
    }

    //用户获取任务奖励详情
    public function task_rewards()
    {
        $email = $this->check_login_state();
        $result = $this->Dshare_model->task_rewards($email);
        $this->Rest_model->print_rest_json($result);
    }

    //用户点击领取奖励（暂未用，前版）
    public function get_rewards_x()
    {
        $email = $this->check_login_state();
        $task_id = $this->Rest_model->get_request("id");
        $reward = $this->Rest_model->get_request("reward");
        $result = $this->Dshare_model->get_rewards_x($email,$task_id,$reward);
        $this->Rest_model->print_rest_json($result);
    }
    //用户点击领取奖励
    public function get_rewards()
    {
        $email = $this->check_login_state();
        $task_id = $this->Rest_model->get_request("id");
        $reward = $this->Rest_model->get_request("reward");
        $result = $this->Dshare_model->get_rewards($email,$task_id,$reward);
        $this->Rest_model->print_rest_json($result);
    }


    //每日签到
    public function signed_record()
    {
        $email = $this->check_login_state();
        $reward = $this->Rest_model->get_request("reward");
        $result = $this->Dshare_model->signed_record($email,$reward);
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
