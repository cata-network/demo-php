<?php
/**
 * Created by PhpStorm.
 * User: ckx
 * Date: 2018/11/6
 * Time: 下午3:48
 */

//sina weibo api
include_once( 'resource/weibo_api/config.php' );
include_once( 'resource/weibo_api/saetv2.ex.class.php' );
require_once "resource/aliyun-php-sdk-core/Config.php";
use Dm\Request\V20151123 as Dm;


class User_model extends CI_Model {
    public function __construct()
    {
        $this->load->database();
//        $this->db2 = $this->load->database('user', TRUE); //用户相关的数据，需要读写库
//        $this->db = $this->load->database('cata_network', TRUE);
    }

    //微信用户登录检测，登录信息写入数据库
    public function weixin_user_login($code)
    {
        //step 1, 获取用户的uid
        $user_info = $this->get_openid($code);

        if ($user_info["status"]<0) {
            return array("status"=>-1,"msg"=>"error, token could be only used once");

        }

        //var_dump($user_info);
        $uid = $user_info["results"]["openid"];

        //step 2, 查看是否已经存在
        $email = $uid . "@weixin.dappbk.com";
        $password = $uid;
        $nickname = $user_info["results"]["openid"];

        $sql = "select count(*) as num from member where email='$email'";
        $result = $this->db->query($sql)->result_array();
        $result_number = $result[0]["num"];

        if ( 0 == $result_number ) //如果email没注册过
        {
            $data["email"] = $email;
            $data["password"] = $password;
            $data["nickname"] = $nickname;
            //注册账号，并写session
            $user_reg = $this->reg_user($data);

            //分配钱包，初始balance为0
            $sql_new = "insert into user_balance  (user_email,balance)   
                         values ('$email',0)";
            $this->db->query($sql_new);

            return array("status"=>0,"msg"=>"success","email"=>$email,"token"=>$user_reg["token"]);
        }
        else //如果已经注册过
        {
            $token = $this->write_user_login_info($email);
            return array("status"=>0,"msg"=>"success","email"=>$email,"token"=>$token);
        }
    }

    /****************微信服务*******************/
    private function get_openid($code)
    {
        $appid='wx9183058357f5f4f2';
        $secret='2c22bb0c322e90f03e6eefa623ef2049';
        //$appid='wxe7cc1f2f2844ea4e'; //dshare个人
        //$secret='72323ba23e17d32042e04baaba9bc962';
        //$appid ='wx5dbd046f6953ff07';  今日有解
        //$secret='c4c3f4a1fd652f108bd87ff9c8a4ddf3';
        $url="https://api.weixin.qq.com/sns/jscode2session?appid=$appid&secret=$secret&js_code=$code&grant_type=authorization_code";
        //echo $url;
        $ch = curl_init();
        curl_setopt ($ch,CURLOPT_URL,$url);
        curl_setopt ($ch,CURLOPT_RETURNTRANSFER,1);
        curl_setopt ($ch,CURLOPT_TIMEOUT,30);
        $content=curl_exec($ch);
        $status=(int)curl_getinfo($ch,CURLINFO_HTTP_CODE );
        if( $status==404)
        {
            return  "";
        }
        curl_close($ch) ;
        //var_dump($content);


            $mm = json_decode($content,true);

            if (array_key_exists('openid',$mm)) {
                $results['openid'] = $mm['openid'];
                $results['session_key'] = $mm['session_key'];
                return array("status" => 0, "msg" => "success", "results" =>$results);
            } else
            {
                return array("status" => -1, "msg" => "false", "results" =>$mm);
            }
    }



    #获得weibo登录的url
    public function get_weibo_login_url()
    {
        $o = new SaeTOAuthV2( WB_AKEY , WB_SKEY );
        $code_url = $o->getAuthorizeURL(WB_CALLBACK_URL);
        return $code_url;
    }

    //微博用户登录检测，登录信息写入数据库
    public function weibo_user_login($code)
    {
        //step 1, 获取微博用户的uid
        $user_info = $this->get_weibo_user_info($code);
        $uid = $user_info["id"];

        //step 2, 查看是否已经存在
        $email = $uid . "@weibo.com";
        $password = $uid;
        $nickname = $user_info["screen_name"];

        $sql = "select count(*) as num from member where email='$email'";
        $result = $this->db->query($sql)->result_array();
        $result_number = $result[0]["num"];

        if ( 0 == $result_number ) //如果email没注册过
        {
            $data["email"] = $email;
            $data["password"] = $password;
            $data["nickname"] = $nickname;
            //注册账号，并写session
            $this->reg_user($data);
        }
        else //如果已经登录过
        {
            $token = $this->write_user_login_info($email);
            return array("status"=>0, "token"=>$token);
        }
    }

    //获得weibo用户的信息
    public function get_weibo_user_info($code)
    {
        $o = new SaeTOAuthV2( WB_AKEY , WB_SKEY );
        $code_url = $o->getAuthorizeURL( WB_CALLBACK_URL );

        #step 1, get token
        $o = new SaeTOAuthV2( WB_AKEY , WB_SKEY );
        $keys = array();
        $keys['code'] = $code;
        $keys['redirect_uri'] = WB_CALLBACK_URL;
        $token = $o->getAccessToken( 'code', $keys ) ;

        #step 2, get uid
        $c = new SaeTClientV2( WB_AKEY , WB_SKEY , $token['access_token'] );
        $uid_get = $c->get_uid();
        $uid = $uid_get['uid']; //当前用户的uid
        $user_info = $c->show_user_by_id($uid);
        return $user_info;
    }

    //注册用户
    public function reg_user($data)
    {
        $email = $data["email"];
        if ( strlen($data["password"]) < 2 )
        {
            return array("status"=>-1, "message"=>"password too short");
        }

        $password = md5($data["password"]); //md5加密

        //如果没有设置nickname，则取email中@前的部分
        if ( !isset($data["nickname"]) )
        {
            $item = explode("@", $email);
            $nickname = $item[0];
        }
        else
        {
            $nickname = $data["nickname"];
        }

		$date_time =  date("Y-m-d H:i:s ");
        $data = array(
			'email' => $email,
			'password' => $password,
			'regdate' => $date_time,
			'nickname' => $nickname
		);
		$this->db->insert('member',$data);
		$member_id = $this->db->insert_id('member');

        //记录token
        $token = $this->write_user_login_info($email);

		//分配钱包，初始balance为 10
		$sql_new = "insert into user_balance  (member_id,email,balance)   
                         values ($member_id,'$email',10)";
		$this->db->query($sql_new);

        return array("status"=>0, "token"=>$token);
    }

    //手机验证码登陆／注册
    public function login_tel_user($phone_num, $code)
    {
        $email = $phone_num."@appbk.com";
        $password = md5("appbk.com|".time());

        //step1,验证手机号和验证码
        $check_result = $this->verify_sms_code($email, $phone_num, $code);
        $check_status = $check_result["status"];

        if (-1==$check_status)
        {
            return array("status"=>-1, "message"=>"check phone message return code error");
        }

        //step2,判断用户手机号是否存在
        $sql = "select count(*) as num from member where email='$email'";
        $result = $this->db2->query($sql)->result_array();
        $get_num = $result[0]["num"];
        if ($get_num<1)  //若不存在,存入数据库
        {
            //注册
            $data = array();
            $data["email"] = $email;
            $data["password"] = $password;
            $reg_result = $this->reg_user($data);

            if (-1==$reg_result["status"])
            {
                return $reg_result;
            }
            $token =$reg_result["token"];
        }
        else {
            $token = $this->write_user_login_info($email);
        }
        return array("status"=>0, "token"=>$token,"email"=>$email);
    }


    //将用户登录信息写入数据库,返回一个token
    public function write_user_login_info($email)
    {
        $salt = "cata.show";
        $cur_time = (string)time();
        $token = md5($email.$salt.$cur_time);
        $ip = $this->input->ip_address();
        //插入数据库
        $sql = "insert into member_token (email,token,login_time,ip)
                values ('$email', '$token', now(), '$ip')";
        $this->db->query($sql);
        return $token;
    }


    //restful调用中检查用户是否已经登录,并且调用的账号和登录账号一致
    //如果不一致，跳转到错误页面,输出一个展示错误的json数据后续设计检测token来验证
    //注： admin账号58100533@qq.com不需要检测登录
    public function check_login_restful($email)
    {
        //admin账号，不需要登录
        if ($email == "58100533@qq.com")
        {
            return 0;
        }

        $token = "";
        if ( isset($_REQUEST["token"])  )//如果没输入$token，检测cookie是
        {
            $token = $_REQUEST["token"];
        }
        else
        {
            if ( isset($_COOKIE["token"])  )
            {
                $token = $_COOKIE["token"];
            }
        }

        $error_url = base_url() . "rest/error?ec=-2&ei=no_log_in";
        if ( ""==$token )
        {
            header("location:$error_url");//如果没token，发错误信息
            exit;//防止跳转后继续执行
        }
        else
        {
            //检查token
            $sql = "select count(*) as num from member_token where email='$email' and token='$token'";
            $result = $this->db2->query($sql)->result_array();
            $result_number = (int)$result[0]["num"];
            if ( 0 == $result_number )
            {
                //错误,没有颁发过次token
                header("location:$error_url");//转到错误页面，发错误信息
                exit; //防止跳转后继续执行
            }
            else
            {
                return 0;
            }

        }
    }

    //判断用户是否登录，返回用户信息email，否则，返回空字符串
    public function get_login_user_email()
    {
        $email = $this->session->userdata('email');

        if ( $email ) //如果session设置了email,返回用户信息
        {
            $user_info = $this->get_user_info($email);
            return $user_info["email"];
        }
        else //否则返回一个空字符串
        {
            return "";
        }
    }

    //根据mail，获得用户信息
    public function get_user_info($email)
    {
        $sql = "select id,email,username,regdate,user_platform,nickname
            ,level, phone_num, company
            from member 
            where email='$email'";
        $result = $this->db->query($sql)->result_array();

        return $result;
    }

    //根据email,获得用户vip信息
    public function get_vip_info($email)
    {
        $final_result = array();
        //step 1,查看用户等级
        $sql = "select level_name,member.level,level_icon
                from member
                left join member_level
                on member.level=member_level.level
                where email='$email'";
        $result = $this->db->query($sql)->result_array();

        if  ($result)
        {
            $final_result["level"] = $result[0]["level"];
            $final_result["level_name"] = $result[0]["level_name"];
            $final_result["level_icon"] = $result[0]["level_icon"];
        }
        else
        {
            $final_result["level"] = 21;
            $final_result["level_name"] = "";
            $final_result["level_icon"] = "";
        }

        //查看vip截至时间
        //如果用户level=7,也就是购买了vip的用户,判定是否过期
        if ($result)
        {
            if (7 == (int)$result[0]["level"] || 8 == (int)$result[0]["level"])
            {
                $level = (int)$result[0]["level"];
                //如果level=7,查看member_level_time中的end_time看是否过期
                $sql = "select * from member_level_time
                        where email='$email' and level=$level";//目前一个用户只能购买vip7权限
                $level_result = $this->db->query($sql)->result_array();

                if ($level_result)
                {
                    $level_time = strtotime($level_result[0]["end_time"]);
                    if (time()>$level_time) //如果已经过期
                    {
                        $final_result["end_time"] = date("Y-m-d", $level_time) . "已过期";//1,表示过期
                    }
                    else //如果没有过期
                    {
                        $final_result["end_time"] = date("Y-m-d", $level_time) ;//0 ,未能过期
                    }
                }
                else //没有订购记录
                {
                    $final_result["end_time"] = "";//当前日期
                }
            }
            else
            {
                $final_result["end_time"] = "2046-01-01";//其他的级别均不过期
            }
        }

        return $final_result;
    }

    //更新用户信息
    public function  update_user_info($email, $company, $app, $phone, $qq)
    {
        $sql = "update member set company='$company',
                app_name='$app',phone_num='$phone',qq='$qq'
                 where email='$email'";
        $this->db->query($sql);
        return 0;
    }

    //检查用户注册的输入
    //input : 用户输入的数据
    //return : 正确，返回"0"，else，返回错误信息文本
    public function check_user_register_input($email)
    {
        //step 1,检测email是否符合规范,bootstrap已经检测
        //step 2，检测两次输入的密码是否一致,js检查
        //step 3，检测email是否已经存在
        $sql = "select count(0) as num from member where email = '$email'";
        $result = $this->db->query($sql)->result_array();
        $result_number = $result[0]["num"];
        if ( 0 != $result_number )
        {
            //如果错误，返回一个错误信息
            $error = "该Email已经注册，请输入新的Email，或者直接登录 ";
            return array("status"=>-1, "message"=>$error);
        }
        else
        {
            return array("status"=>0, "message"=>"success");
        }
    }

    //检查用户登陆的输入
    //input : 用户输入的数据
    //return : 正确，返回一个token，同时数据库写入用户登录信息，else，返回错误信息文本
    public function check_user_login_input($email, $password)
    {
        //step 1，检测帐号或者密码是否正确
        $password = md5($password); //md5加密
        $sql = "select count(0) as num from member where
            email='$email' and password='$password'";
        $result = $this->db->query($sql)->result_array();
        $result_number = $result[0]["num"];
        if ( 0 == $result_number )
        {
            //如果错误，返回一个错误信息
            $error = "帐号或密码错误";
            return array("status"=>-2, "message"=>$error);
        }
        else
        {
            $token = $this->write_user_login_info($email);
            $expire_time = date("Y-m-d H:i:s",time() + 30*24*60*60);//过期时间，一个月后
            return array("status"=>0, "token"=>$token, "expire"=>$expire_time);
        }
    }

    //短信验证,给某个手机号码发送验证短信
    //暂时不进行用户账号验证,注册/修改密码 时也可使用
    public function request_sms_code($phone_num)
    {
        $ch = curl_init();
        $url = 'https://api.leancloud.cn/1.1/requestSmsCode';
        $header = array(
            'X-LC-Id: wdVkR3HBdEm5JuxvUwx7a5ye',
            'X-LC-Key: 9F3g8WJXJaPlmGJXxBVU8BgV',
            'Content-Type: application/json'
        );
        $post_data = array("mobilePhoneNumber"=>$phone_num);
        $post_date_string = json_encode($post_data);
        // 添加apikey到header
        curl_setopt($ch, CURLOPT_HTTPHEADER  , $header);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_date_string);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        // 执行HTTP请求
        curl_setopt($ch , CURLOPT_URL , $url);
        $res = curl_exec($ch);


        $res = "{}";
        $return_result =  json_decode($res,true);
        if (empty($return_result))//如果为空,表示正确
        {
            $result = array("status"=>200,"message"=>"send message success");
        }
        else //如果错误
        {
            $result = array("status"=>-1,"message"=>"error,code error".$res);
        }
        return $result;
    }

    //验证收到的 6 位数字验证码是否正确
    public function verify_sms_code($email, $phone_num, $code)
    {
        $ch = curl_init();
        $url = "https://api.leancloud.cn/1.1/verifySmsCode/$code?mobilePhoneNumber=$phone_num";
        $header = array(
            'X-LC-Id: wdVkR3HBdEm5JuxvUwx7a5ye',
            'X-LC-Key: 9F3g8WJXJaPlmGJXxBVU8BgV',
            'Content-Type: application/json'
        );
        //echo $url;
        //$post_data = array("mobilePhoneNumber"=>$phone_num);
        //$post_date_string = json_encode($post_data);
        $post_date_string = "";
        // 添加apikey到header
        curl_setopt($ch, CURLOPT_HTTPHEADER  , $header);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_date_string);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        // 执行HTTP请求
        curl_setopt($ch , CURLOPT_URL , $url);
        $res = curl_exec($ch);
        $result = json_decode($res,true);
        if (empty($result))//如果为空,表示正确
        {
            //更新用户数据库的电话号码
            $sql = "update member set phone_num='$phone_num'
                    where email='$email'";
            $result = $this->db2->query($sql);
            $result = array("status"=>200,"message"=>"verify success,update phone num");
        }
        else //如果错误
        {
            $result = array("status"=>-1,"message"=>"error,code error".$res);
        }
        return $result;
    }

    //插入一条操作记录
    public function insert_action_record($email, $action)
    {
        //插入数据库
        $sql = "insert into member_action (email,action,fetch_time)
                values ('$email', '$action',now())";
        $this->db->query($sql);
        return 0;
    }

    //发送密码找回邮件
    public function find_pwd($email)
    {
        //step 1,判断email账号是否存在
        $sql = "select count(*) as num from member where
            email='$email'";
        $result = $this->db->query($sql)->result_array();

        if ( 0 == (int)$result[0]["num"]) //如果不存在
        {
            return array("status"=>-1, "msg"=>"用户邮件账号不存在");
        }

        //step 2,如果账号存在,发送邮件
        //生成找回code
        $code = md5($email . time() . "|appbk");
//        $url = "http://appbk.com/#/updatePwd/" . $code;

//        $url = "http://appbk.com/#/account/updatePwd/" . $code;
        $url = "http://wenku.appbk.com/#/update_password/" . $code;

        //存储到数据库
        $sql = "insert into member_find_pwd (email, code, fetch_time)
                values ('$email', '$code', NOW())";
        $this->db->query($sql);

        //发送邮件
        $title = "[APPBK]找回密码邮件";
        $content = "<p>重置密码链接在一个小时内有效</p>
                    <p><a href='$url' target='_blank'>重置密码</a></p>
                    <p>或者复制链接到浏览器: $url</p>
                    <p>如有疑问，可直接在APPBK用户群(39351116)反馈。</p>";

        $iClientProfile = DefaultProfile::getProfile("cn-hangzhou", "HoFZrmdnBheFen1y", "hagWeBWw6s9270Avjjni933KiGvIgh");
        $client = new DefaultAcsClient($iClientProfile);
        $request = new Dm\SingleSendMailRequest();
        $request->setAccountName("password@note.appbk.com");
        $request->setFromAlias("appbk_password");
        $request->setAddressType(1);
        $request->setTagName("APPBK");
        $request->setReplyToAddress("true");
        $request->setToAddress($email);
        $request->setSubject($title);
        $request->setHtmlBody($content);
        $response = $client->getAcsResponse($request);
        //print_r($response);

        return array("status"=>0, "msg"=>"邮件已发送");
    }

    //根据找回邮件中的code,修改对应email的密码
    public function update_pwd($code, $pwd)
    {
        //step 1,数据库中检索$code对应的记录
        $sql = "select * from member_find_pwd where
            code='$code'";

        $result = $this->db->query($sql)->result_array();

        if (!$result) //如果记录不存在
        {
            return array("status"=>-1, "msg"=>"用户找回码错误");
        }

        $fetch_time = strtotime($result[0]['fetch_time']);
        if ( (time()-$fetch_time) > 60*60) //如果超过一个小时
        {
            return array("status"=>-1, "msg"=>"用户找回码已经过期,请重新找回");
        }


        //更新用户密码, 默认pwd已经是md5加密的
        $email = $result[0]['email'];
        $sql = "update member set password='$pwd'
                where email='$email'"; //注意测试前先备份member表!!!
        //echo $sql;
        $this->db->query($sql);

        return array("status"=>0, "msg"=>"密码已经更新,请重新登录");

    }

    //生成兑换码
    //$num是多少个
    public function get_ticket($num,$level)
    {
        //step 1,生成兑换码
        $ticket_list = array();

        for ($i = 0; $i < $num; $i++)
        {
            $ticket = md5("tic" . time() . "|" . rand(0, 10000));//兑换码
            $ticket_list[] = $ticket;
        }

        //step 2, 插入数据库
        $value_list = array();
        if (""==$level || 7==$level)  //如果是level 7,也就是vip用户,默认都是3个月的
        {
            foreach ($ticket_list as $ticket) {
                $value_list[] = "('$ticket', 7, 3, 1,now(),0)";
            }
        }
        else //其他级别的,默认都是0.5个月的
        {
            foreach ($ticket_list as $ticket) {
                $value_list[] = "('$ticket', $level, 0.5, 1,now(),0)";
            }
        }

        $value_list_join = join(",",$value_list);

        $sql = "insert into member_ticket
                (ticket, product, num, published, publish_time, used)
                values $value_list_join";
        $result = $this->db->query($sql);
        if ($result)
        {
            return $ticket_list;
        }
        else
        {
            return array("status"=>-1,"msg"=>"get ticket error");
        }
    }

    //使用兑换码进行兑换
    public function use_ticket($email, $code)
    {
        //step 1,检测$code兑换码
        $sql = "select * from member_ticket
                  where ticket='$code'";
        $result = $this->db->query($sql)->result_array();
        if (!$result) //如果没有结果
        {
            return array("status" => -1, "msg" => "未找到对应的兑换码");
        }

        //判定是否过期
        $publish_time = strtotime($result[0]["publish_time"]);//获取兑换码的时间
        if ( (time()-$publish_time) > 31*24*60*60 ) //超过31天,算作过期
        {
            return array("status" => -2, "msg" => "兑换码已经过期(超过一个月未使用)");
        }

        //判定是否已经使用
        if (1 == (int)$result[0]["used"]) //如果已经使用
        {
            return array("status" => 1, "msg" => "兑换码已经使用过");
        }



        //没有问题,开通服务
        //step 1,将member库中的用户level置为$level,可以多次update,没有逻辑问题
        $level = (int)$result[0]["product"];
        $num = (float)$result[0]["num"];

        $sql = "update member set level=$level
                where email='$email'";
        $result = $this->db->query($sql);
        if (!$result) //如果执行错误
        {
            return array("status" => -3, "msg" => "升级用户权限出错");
        }

        //step2 ,开通服务,判定是否有过历史购买记录,如果有,则续期,修改结束时间
        $sql = "select * from member_level_time
                where email='$email' and level=$level";
        $result = $this->db->query($sql)->result_array();

        if ($result) //如果有过购买记录,续期, $num个月之后的日期
        {
            /* // 上一版本微信VIP兑换时间bug
             //$end_time = date('Y-m-d H:i:s',strtotime('+'.$num.' months', strtotime($result[0]["end_time"])));
             //  $pre_end_time = strtotime($result[0]["end_time"]);
             // $end_time = date('Y-m-d H:i:s', $pre_end_time + (int) ( $num*31*24*60*60) );
            */


            $pre_end_time = strtotime($result[0]["end_time"]);
            $cur_time = time();

            if ($pre_end_time>$cur_time)// 如果未过期,在此基础上续费,之前end_time大于当前时间
            {
                $end_time = date('Y-m-d H:i:s', $pre_end_time + (int) ( $num*31*24*60*60) );            }
            else //如果已经过期,在当前时间上加一个月
            {
                $end_time = date('Y-m-d H:i:s',time() + (int) ( $num*31*24*60*60));
            }


            $sql = "update member_level_time set end_time='$end_time',service_type='promote'
                    where email='$email' and level=$level";
        }
        else //如果没有购买记录,新插入一条记录
        {
            $start_time = date('Y-m-d H:i:s');
            //$end_time = date('Y-m-d H:i:s',strtotime('+'.$num.' months'));
            $end_time = date('Y-m-d H:i:s', time() + (int) ( $num*31*24*60*60) );
            $sql = "insert into member_level_time (email,level,start_time,end_time, service_type)
                    values ('$email', $level, '$start_time', '$end_time', 'promote')";
        }
        $result = $this->db->query($sql);
        if (!$result) //如果执行错误
        {
            return array("status" => -4, "msg" => "开通服务出错");
        }

        //step 3, 把兑换码置为已使用
        $sql = "update member_ticket
                set used=1,use_time=now(),email='$email'
                where  ticket='$code'";
        $result = $this->db->query($sql);
        if (!$result) //如果执行错误
        {
            return array("status" => -5, "msg" => "兑换码使用错误");
        }
        else
        {
            return array("status" => 0, "msg" => "兑换码使用成功! VIP". $num ."个月权限生效");
        }

    }
}
