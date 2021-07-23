<?php
/**
 * Created by PhpStorm.
 * User: ckx
 * Date: 2018/11/6
 * Time: 下午4:00
 */

class Candy extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model("Candy_model");
        $this->load->model("Rest_model");
    }

    /****************钱包服务*******************/
    //创建钱包
    public function create_wallet()
    {
        $email = $this->check_login_state();
        $password = $this->Rest_model->get_request("password");
        $result = $this->Candy_model->create_wallet($email, $password);
        $this->Rest_model->print_rest_json($result);
    }

    //导出钱包私钥
    public function export_private_key()
    {
        $email = $this->check_login_state();
        $result = $this->Candy_model->export_private_key($email);
        $this->Rest_model->print_rest_json($result);
    }

    //导出钱包keystore
    public function export_keystore()
    {
        $email = $this->check_login_state();
        $result = $this->Candy_model->export_keystore($email);
        $this->Rest_model->print_rest_json($result);
    }

    //更新用户idfa
    public function update_idfa()
    {
        $email = $this->check_login_state();
        $idfa = $this->Rest_model->get_request("idfa");
        $result = $this->Candy_model->update_idfa($email, $idfa);
        $this->Rest_model->print_rest_json($result);
    }

    /****************candy相关服务*******************/

    //获得任务列表
    public function get_tasks()
    {
        //无需登录
        $email = $this->Rest_model->get_request("email");
        $result = $this->Candy_model->get_tasks($email);
        $this->Rest_model->print_rest_json($result);
    }

    //领取candy
    public function get_candy()
    {
        $email = $this->check_login_state();
        //$email = $this->Rest_model->get_request("email");
        $task_id = $this->Rest_model->get_request("task_id");
        $result = $this->Candy_model->get_candy($email, $task_id);
        $this->Rest_model->print_rest_json($result);
    }

    //获得用户的candy列表
    public function get_user_candies()
    {
        $email = $this->check_login_state();
        $result = $this->Candy_model->get_user_candies($email);
        $this->Rest_model->print_rest_json($result);
    }

    /****************candy 商家相关服务*******************/
    //创建一个candy token
    public function create_candy()
    {
        $email = $this->check_login_state();
        $supply = $this->Rest_model->get_request("supply");
        $token_name = $this->Rest_model->get_request("token_name");
        $token_symbol = $this->Rest_model->get_request("token_symbol");

        $result = $this->Candy_model->create_candy($email, $supply, $token_name, $token_symbol);
        $this->Rest_model->print_rest_json($result);
    }

    //创建candy task任务,使用合约的allow功能
    public function create_task()
    {
        $email = $this->check_login_state();
        $project_id = $this->Rest_model->get_request("project_id");//token address
        $start_time = $this->Rest_model->get_request("start_time");//开始时间
        $end_time = $this->Rest_model->get_request("end_time");//结束时间
        $task_num = $this->Rest_model->get_request("task_num");//任务总数
        $task_token_num = $this->Rest_model->get_request("task_token_num");//每个任务领取的token数

        $result = $this->Candy_model->create_task($email, $project_id, $start_time, $end_time, $task_num, $task_token_num);
        $this->Rest_model->print_rest_json($result);
    }


    /****************价格曲线*******************/
    public function get_token_price_trend()
    {
        $id = $this->Rest_model->get_request("id"); //唯一id, 取new id
        $start = $this->Rest_model->get_request("start");
        $end = $this->Rest_model->get_request("end");

        $result = $this->Candy_model->get_token_price_trend($id, $start, $end);
        $this->Rest_model->print_rest_json($result);
    }

    //价格曲线, 简化模式,只显示曲线本身
    public function get_token_price_trend_simple()
    {
        $id = $this->Rest_model->get_request("id"); //唯一id, 取new id
        $start = $this->Rest_model->get_request("start");
        $end = $this->Rest_model->get_request("end");

        $result = $this->Candy_model->get_token_price_trend_simple($id, $start, $end);
        $this->Rest_model->print_rest_json($result);
    }

    //获得bty的收入趋势图
    public function bty_income_trend()
    {
        $start = $this->Rest_model->get_request("start");
        $end = $this->Rest_model->get_request("end");

        $result = $this->Candy_model->bty_income_trend($start, $end);
        $this->Rest_model->print_rest_json($result);
    }

    //测试上传文件
    public function upload_file()
    {
        $result = $this->Candy_model->upload_file();
    }


    /************电报群相关*************/
    //生成uuid链接
    public function telegram()
    {

        $email = $this->Rest_model->get_request("email");
        $telegram_task_id = $this->Rest_model->get_request("telegram_task_id");

        $result = $this->Candy_model->telegram($email, $telegram_task_id);

        $data["url"] = $result["url"];
        //输出views
        $this->load->view('telegram',$data);
    }

    //get telegram job info
    public function get_telegram_job_info()
    {
        $job_token = $this->Rest_model->get_request("job_token");
        $result = $this->Candy_model->get_telegram_job_info($job_token);
        $this->Rest_model->print_rest_json($result);
    }

    //finish telegram job
    public function finish_telegram_job()
    {
        $user_id = $this->Rest_model->get_request("user_id");
        $invite_url = $this->Rest_model->get_request("invite_url");
        $result = $this->Candy_model->finish_telegram_job($user_id, $invite_url);
        $this->Rest_model->print_rest_json($result);
    }

    public function get_job_status()
    {
        $telegram_task_id = $this->Rest_model->get_request("telegram_task_id");
        $result = $this->Candy_model->get_job_status($telegram_task_id);
//        var_dump($result);
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