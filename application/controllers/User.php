<?php
/**
 * Created by PhpStorm.
 * User: ckx
 * Date: 2018/11/6
 * Time: 下午3:47
 */

class User extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('User_model');
        $this->load->model('Rest_model');
    }


    public function weixin_user_login()
    {
        $code = $this->Rest_model->get_request("code");
        $result = $this->User_model->weixin_user_login($code);
        //如果正确，返回一个token
        $this->Rest_model->print_rest_json($result);
    }

	//用户输入的登录信息是否正确
    public function check_user_login_input()
    {
        $email = $this->Rest_model->get_request("email");
        $password = $this->Rest_model->get_request("password");
        $result = $this->User_model->check_user_login_input($email, $password);
        //如果正确，返回一个token
        $this->Rest_model->print_rest_json($result);
    }

    //检测用户输入的注册信息是否正确
    //主要检查email是否已经注册
    public function check_user_register_input()
    {
        $email = $this->Rest_model->get_request("email");
        $result = $this->User_model->check_user_register_input($email);
        $this->Rest_model->print_rest_json($result);
    }

    //用户邮箱注册
    public function reg_user()
    {
        $data["email"] = $this->Rest_model->get_request("email");
        $data["password"] = $this->Rest_model->get_request("password");
        //用户注册
        $result = $this->User_model->reg_user($data);
        $this->Rest_model->print_rest_json($result);
    }

    //手机验证码登陆（包含注册）
    public function login_tel_user()
    {
        $phone_num = $this->Rest_model->get_request("phone_num");
        $code = $this->Rest_model->get_request("code");
        //用户手机登录
        $result = $this->User_model->login_tel_user($phone_num, $code);
        $this->Rest_model->print_rest_json($result);
    }

    //获得用户基本信息
    public function get_user_info()
    {
        $email = $this->Rest_model->get_request("email");
        //获得用户信息
        $result = $this->User_model->get_user_info($email);
        $this->Rest_model->print_rest_json($result);
    }

    //获得用户vip信息
    public function get_vip_info()
    {
        $email = $this->Rest_model->get_request("email");
        //检查用户是否登录,如果未登录则直接跳转到错误页面
        $this->User_model->check_login_restful($email);
        //获得用户信息
        $result = $this->User_model->get_vip_info($email);
        $this->Rest_model->print_rest_json($result);
    }

    //更新用户信息,目前主要更新用户的公司和app
    public function update_user_info()
    {
        $email = $this->Rest_model->get_request("email");
        //检查用户是否登录,如果未登录则直接跳转到错误页面
        $this->User_model->check_login_restful($email);

        $company = $this->Rest_model->get_request("company");
        $app = $this->Rest_model->get_request("app");
        $phone = $this->Rest_model->get_request("phone");
        $qq = $this->Rest_model->get_request("qq");


        //更新用户信息
        $this->User_model->update_user_info($email, $company, $app, $phone, $qq);
        $this->Rest_model->print_success_json();
    }


    //短信验证,给某个手机号码发送验证短信
    //暂时不进行用户账号验证,注册/修改密码 时也可使用
    public function request_sms_code()
    {
        //获得手机号码
        $phone_num = $this->Rest_model->get_request("phone_num");
        $result = $this->User_model->request_sms_code($phone_num);
        $this->Rest_model->print_rest_json($result);
    }

    //验证收到的 6 位数字验证码是否正确
    //如果正确,目前直接更新用户个人档的手机信息
    public function verify_sms_code()
    {
        //获得手机号码
        $email = $this->Rest_model->get_request("email");
        $phone_num = $this->Rest_model->get_request("phone_num");
        //获得的6位数字验证码
        $code = $this->Rest_model->get_request("code");
        $result = $this->User_model->verify_sms_code($email, $phone_num, $code);
        $this->Rest_model->print_rest_json($result);
    }

    //发送密码找回邮件
    public function find_pwd()
    {
        $email = $this->Rest_model->get_request("email");
        $result = $this->User_model->find_pwd($email);
        $this->Rest_model->print_rest_json($result);
    }

    //根据找回邮件中的code,修改对应email的密码
    public function update_pwd()
    {
        $code = $this->Rest_model->get_request("code"); //找回码
        $pwd = $this->Rest_model->get_request("pwd"); //md5加密之后的密码
        $result = $this->User_model->update_pwd($code, $pwd);//更新密码
        $this->Rest_model->print_rest_json($result);
    }

    //生成兑换码
    public function get_ticket()
    {
        $num = $this->Rest_model->get_request("num"); //获得多少个兑换码
        $level = $this->Rest_model->get_request("level"); //用户级别
        $result = $this->User_model->get_ticket($num, $level);//更新密码
        $this->Rest_model->print_rest_json($result);
    }

    //使用兑换码进行兑换
    public function use_ticket()
    {
        $email = $this->Rest_model->get_request("email");
        //检查用户是否登录,如果未登录则直接跳转到错误页面
        $this->User_model->check_login_restful($email);
        $code = $this->Rest_model->get_request("code"); //兑换码

        $result = $this->User_model->use_ticket($email, $code);//更新密码
        $this->Rest_model->print_rest_json($result);

    }

}
