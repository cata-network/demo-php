<?php
/**
 * Created by PhpStorm.
 * User: ckx
 * Date: 2018/11/6
 * Time: 下午4:02
 */

require_once "resource/aliyun-oss/aliyun-oss-php-sdk-2.0.7.phar";
//include_once $_SERVER['DOCUMENT_ROOT']."/resource/aliyun-oss/aliyun-oss-php-sdk-2.0.7.phar";
class Market_goods_model extends CI_Model
{
    public function __construct()
    {
        $this->load->database();
        // $this->db2 = $this->load->database('token', TRUE);

    }
    public function share_test()
    {
       var_dump("12w3534545");
    }

    public  function upload_note_content($title,$content,$price,$email,$imgUrl,$brief){
        $fetch_time = date('Y-m-d H:i:s');
        //$imgUrl="http://token60.oss-cn-hangzhou.aliyuncs.com/token_icos/152752mb3lwo8vw1cqpbp3.jpg";
        if ($imgUrl==""){
            $imgUrl="https://token60.oss-cn-hangzhou.aliyuncs.com/6c31ed4f5e8aa7359ef76e134ac8e510.jpeg";
        }
        $sql = "insert into market(email,title,content,price,img_url,fetch_time,brief,status)
                values('$email','$title','$content','$price','$imgUrl','$fetch_time','$brief',0)";
        //var_dump($sql);
        $this->db->query($sql);
        return array("status" => 0, "msg" => "success");
    }

    public function upload_file()
    {

        //var_dump($_FILES);


        $array = explode('.', $_FILES['file']['name']); //获得扩展名
        $extension = end($array); //真实的文件扩展名
//        $file_name = $_FILES["file"]["name"]; //原始文件名
//        $file_type = $_FILES["file"]["type"];//
//        $file_size = $_FILES["file"]["size"];//
        $file_path = $_FILES["file"]["tmp_name"];//实际上传的文件名,带路径
//        $ssh_pem = file_get_contents($file_path);



        //初始化链接
        $accessKeyId = "HoFZrmdnBheFen1y";
        $accessKeySecret = "hagWeBWw6s9270Avjjni933KiGvIgh";
        $endpoint = "oss-cn-hangzhou.aliyuncs.com";
        $ossClient = new \OSS\OssClient($accessKeyId, $accessKeySecret, $endpoint);

        //写入文档到oss
        $bucket = "dshare";
        //$object = "uploads/" . md5(time() . mt_rand(1,1000000)) . '.' . $extension;
        $object = md5(time() . mt_rand(1,1000000)) . '.' . $extension;
        //var_dump($object);
        $result = $ossClient->uploadFile($bucket, $object, $file_path);
        //var_dump($result);
        //$url = "https://dshare.oss-cn-hangzhou.aliyuncs.com/".$object;
        return array("url"=>$object);
    }


    /****************获取集市商品列表*******************/
    public function get_market_goods_list($start,$limit)
    {
        $sql = "select *
                from market where status=1 limit $start,$limit";
//        echo $sql;
        $result = $this->db->query($sql)->result_array();
        $sql_num ="select count(*) as num from market where status=1";
        $num = $this->db->query($sql_num)->result_array();
        return array("status" => 0, "msg" => "success", "num" => $num[0]['num'], "result" =>$result);
    }

    //获取集市商品详情
	public function get_market_goods_info($id)
	{
		$sql = "select * 
                from market where id = $id";
		$result = $this->db->query($sql)->result_array();
		return array("status" => 0, "msg" => "success", "result" =>$result);
	}

	public function search_like_market_goods($word, $start, $limit){
		$sql = "select * from market where 
				 title like '%$word%' or brief like '%$word%' or tag like '%$word%' LIMIT $start,$limit ";
		$result = $this->db->query($sql)->result_array();
		return array("status" => 0, "msg" => "success", "result" =>$result);
	}


    //获取用户纸条(我的纸条)
    public function get_mynote($email, $start, $limit)
    {
        $sql_num="select count(*) as num from market  where  email='$email'";
        $num = $this->db->query($sql_num)->result_array();
        $sql = "select * from market  where  email='$email'  limit $start,$limit";
        $result = $this->db->query($sql)->result_array();
        return array("status" => 0, "msg" => "success", "count"=>$num[0]['num'], "result" =>$result);
    }

    //获取余额(暂未用)
     public function get_balance_x($email)
     {
         $sql = "select * from user_balance  where  user_email='$email' ";
         $result = $this->db->query($sql)->result_array();
         return array("status" => 0, "msg" => "success", "result" =>$result);
     }
     //获取用户余额
     public function get_balance($email)
     {
        $sql = "select * from user_balance where email = '$email' ";
        $result = $this->db->query($sql)->result_array();
        return array("status" => 0, "msg" => "success", "result" =>$result);
     }
    /****************交易*******************/
    //购买
    public function item_transaction($id,$buyer)
    {
        $sql = "select market.`id` as note_id ,`email` as seller,`price` ,`fetch_time`,balance as seller_balance
                from market 
                left join user_balance
                on market.email=user_balance.user_email
                where market.id ='$id' ";
        $result = $this->db->query($sql)->result_array();
        $price = (int)$result[0]['price'];   //纸条价格
        $seller = $result[0]['seller'];  //卖方
        $seller_balance = (int)$result[0]['seller_balance'];

        //判断交易双方不是同一个人
        if($seller==$buyer)
        {
            return array("status" => -1, "msg" => "it is your note ,no need to repeat purchases");
        }

        //step0  获取buyer信息，看余额是否充足
        $sql_balance = "select balance from user_balance where user_email='$buyer'";
        $result_balance = $this->db->query($sql_balance)->result_array();
        $buyer_balance =(int)$result_balance[0]['balance'];
        if ($buyer_balance<$price)//如果钱不够,返回错误,前台也做预判
        {
            return array("status" => -2, "msg" => "user has no enough money");
        }

        //step0.0  判断用户是否已经购买过
        $sql_isRepeat = "select  count(*) as num  from item_transaction  
                         where seller='$seller' and note_id='$id' and buyer='$buyer'";
        $result_isRepeat = $this->db->query($sql_isRepeat)->result_array();
        if ($result_isRepeat[0]['num']>0)//如果已购买,返回错误,提示信息
        {
            return array("status" => -3, "msg" => "user has bought this note");
        }

        //step1 插入交易记录表 item_transaction ;
        $fetch_time = date('Y-m-d H:i:s');
        $sql_item="insert into item_transaction(seller,buyer,note_id,price,action_time)
                values('$seller','$buyer','$id','$price','$fetch_time')";
        $this->db->query($sql_item);

        //step2 扣除buyer价格，更新用户余额表 user_balance
        $buyer_balance = $buyer_balance - $price;
        $sql_balance_new_1 = " update user_balance
                            set balance='$buyer_balance'
                            where user_email='$buyer'";
        $this->db->query($sql_balance_new_1);

        //step3 增加seller余额，更新用户余额表 user_balance
        $seller_balance = $seller_balance + $price;
        $sql_balance_new_2 = " update user_balance
                            set balance='$seller_balance'
                            where user_email='$seller'";
        $this->db->query($sql_balance_new_2);

        //step 4,更新buyer的购买记录,把note分配给buyer用户
        $sql_new = "insert into user_item
                (email,note_id) VALUES
                ('$buyer',$id)";
        $this->db->query($sql_new);

        return array("status" => 0, "msg" => "success");
    }



    //获取用户交易记录（交易包括买卖）
    public function get_user_orders($create_id, $email, $start, $limit)
    {
    	//商品交易记录
    	if ("" != $create_id){
			$if_where_num = "`market` .create_id = '$create_id' ";
		}
    	//用户交易记录
    	if ("" != $email){
			$if_where_num = " buyer='$email' or seller='$email' ";
		}
        $sql_num="select count(0) as num from item_transaction
                LEFT JOIN market ON
                `item_transaction` .user_item_create_id= `market` .create_id
                where $if_where_num ";
        $num = $this->db->query($sql_num)->result_array();
        $sql = "select * from item_transaction
                LEFT JOIN market ON
                `item_transaction` .user_item_create_id= `market` .create_id
                where $if_where_num 
                ORDER BY action_time DESC
                LIMIT $start,$limit";
        $result = $this->db->query($sql)->result_array();

        //商品交易记录
        if("" != $create_id){
			return array("status" => 0, "msg" => "success", "count"=>$num[0]['num'], "result"=>$result);
		}

        //处理结果,判断买方还是卖方, 增加trade字段
        $final_result = array();
        $i = 0;
        foreach ($result as $item)
        {
            if ($email == $item["buyer"]) //如果是购买者
            {
                $result[$i]["trade"] = "买入";
                $result[$i]["price"] = -1 * (int)$item["price"];
            }
            else //如果是出售
            {
                $result[$i]["trade"] = "卖出";
                $result[$i]["price"] = (int)$item["price"];
            }
            $i++;
        }

        return array("status" => 0, "msg" => "success", "count"=>$num[0]['num'], "result"=>$result);
    }

	//获取商品交易记录
	public function get_goods_orders($create_id, $start, $limit)
	{
		$create_id = $this->Rest_model->get_request("create_id");
		$start = $this->Rest_model->get_request("start");
		$limit = $this->Rest_model->get_request("limit");
		$result = $this->Market_goods_model->get_goods_orders($create_id, $start, $limit);
		$this->Rest_model->print_rest_json($result);
	}

    /****************获取纸条详情*******************/
    //列表点击详情页
    public function get_note_details($id)
    {
        $sql = "select `id` ,`email`,`title`,`brief` ,`price` ,`img_url` `fetch_time` from market  where id='$id'";
        $result = $this->db->query($sql)->result_array();
        return array("status" => 0, "msg" => "success", "result" =>$result);
    }

    //已购买的详情页
    public function user_get_note_details($id)
    {
        $sql = "select `id` ,`email`,`title`,`brief` ,`price` ,`content`,`img_url` `fetch_time` from market  where id='$id'";
        $result = $this->db->query($sql)->result_array();
        return array("status" => 0, "msg" => "success", "result" =>$result);
    }

    /****************任务页面*******************/
    //任务列表
    public function get_task_list_x()
    {
        $sql = "select id as task_id ,task_content,reward from task";
        $result = $this->db->query($sql)->result_array();
        return array("status" => 0, "msg" => "success", "result" =>$result);
    }

    public function get_task_list($email)
    {
        $sql = "select  * FROM task LEFT JOIN (select distinct (task_id) from `task_rewards`  
                WHERE email='$email' ) as b
                on `task`.id=b.task_id  ";
        $result = $this->db->query($sql)->result_array();
        return array("status" => 0, "msg" => "success", "result" =>$result);
    }

    //用户获取任务奖励详情
    public function task_rewards($email)
    {
        $sql="select task_id,`task`.`reward`,`task_content`,`task_rewards`.`fetch_time`     
              from task_rewards 
              LEFT JOIN `task` on `task`.`id` =`task_rewards`.`task_id` 
              where `task_rewards`.`email` =  '$email'";
        $result = $this->db->query($sql)->result_array();
        return array("status" => 0, "msg" => "success", "result" =>$result);

    }
    //用户点击领取奖励（暂未用，前版）
    public function get_rewards_x($email,$task_id,$reward)
    {

        //step0:判断是否领取过
        $sql_num="select count(*) as num from task_rewards where email='$email' and task_id = '$task_id'";
        $num = $this->db->query($sql_num)->result_array();

        if ($num[0]['num']>0)
        {
            //提示领取过
            return array("status" => -1, "msg" => "user have been rewarded.");
        }
        $this->receive_rewards($email,$task_id,$reward);
    }


    public function receive_rewards($email,$task_id,$reward)
    {
        //step1:将领取记录插入表task_rewards
        $fetch_time = date('Y-m-d H:i:s');
        $sql="insert into task_rewards
                (email,task_id,reward,fetch_time) VALUES
                ('$email',$task_id,'$reward','$fetch_time')";
        $this->db->query($sql);

        //step2:更新用户币余额  //是否是新用户
        //$sql_user_count="select count(*) as num from user_balance where email='$email'";
        //$user_count = $this->db->query($sql_user_count)->result_array();

        $sql_new = "update user_balance  set  balance=`balance`+$reward   
                    where user_email='$email'";
        $this->db->query($sql_new);

        //return array("status" => 0, "msg" => "success");
    }

    public function get_rewards($email,$task_id,$reward)
    {

        //step0:判断是哪个任务
        if($task_id==2)    //id=2 首次发布成功，查表 market(发布记录)
        {
            $sql_num="select count(*) as num from market where email='$email' ";
            $num = $this->db->query($sql_num)->result_array();


            if ($num[0]['num']==0)
            {
                //提示
                return array("status" => -1, "msg" => "user have not been released the note.");
            }
            else
                {
                    $this->receive_rewards($email,$task_id,$reward);
                    return array("status" => 0, "msg" => "success");
                }
        }
        elseif($task_id==3)   //id=3 首次购买成功（购买记录）
        {
            $sql_num="select count(*) as num from item_transaction where buyer='$email' ";
            $num = $this->db->query($sql_num)->result_array();

            if ($num[0]['num']==0)
            {
                //提示
                return array("status" => -2, "msg" => "user have not been buy any note.");
            }
            else
            {
                $this->receive_rewards($email,$task_id,$reward);
                return array("status" => 0, "msg" => "success");
            }
        }
        elseif($task_id==4)   //id=3 加入QQ群，延迟加分
        {

            $fetch_time = date('Y-m-d H:i:s');
            $fetch_time_delay = date('Y-m-d H:i:s',strtotime('+3 minute'));  //+1minute  8 hour

            //任务信息加入任务记录
            $sql="insert into task_rewards
                (email,task_id,reward,fetch_time) VALUES
                ('$email',$task_id,'$reward','$fetch_time')";
            $this->db->query($sql);
            //延迟积分加入延迟记录表
            $sql="insert into user_balance_delay
                (user_email,balance,effect_time) VALUES
                ('$email',$reward,'$fetch_time_delay')";
            $this->db->query($sql);
            return array("status" => 0, "msg" => "success");
        }
        else
            {
                $this->receive_rewards($email,$task_id,$reward);
                return array("status" => 0, "msg" => "success");
            }

    }

    //每日签到
    public function signed_record($email,$reward)
    {
        //step1,判断是否为第一次签到，第一次签到，记录时间，次数，奖励,  更新用户钱包，奖励记录表
        $sql_num="select count(*) as num from signed_record where email='$email' ";
        $num = $this->db->query($sql_num)->result_array();
        if ($num==0)
        {
            //1.1 插入签到记录表
            $sql="insert into  signed_record(email,sign_time,count,reward) VALUES ('$email',curdate(),1,1)";
            $this->db->query($sql_num)->result_array();

            //1.2更新用户钱包
            $sql_balance="update user_balance  set  balance=`balance`+$reward   
                    where user_email='$email'";
            $this->db->query($sql_balance)->result_array();
            //1.3更新奖励记录表
            $sql_reward="insert into task_rewards(email,task_id,reward,)  set  task_id=0,reward=$reward,fetch_time= curtime()
                    where user_email='$email'";
            $this->db->query($sql_reward)->result_array();
        }
        //step2,判断当前时间与签到时间的差值，
        //      超过一天，返回个信息，次数更新为1，奖励
        //      未超过一天，更新表中信息，记录当日时间
    }

    /****************微信服务*******************/
    private function get_openid($code)
    {
        $appid='wxe7cc1f2f2844ea4e';
        $secret='72323ba23e17d32042e04baaba9bc962';
        #$appid ='wx5dbd046f6953ff07';
        #$secret='c4c3f4a1fd652f108bd87ff9c8a4ddf3';
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
            return array("status" => 0, "msg" => "success", "result" =>$results);
        } else
        {
            return array("status" => -1, "msg" => "false", "result" =>$mm);
        }
    }


    /****************钱包服务*******************/
    //创建钱包
    public function create_wallet($email, $password)
    {
        $sql = "select * from user_wallet
                 where email='$email'";
        $result = $this->db->query($sql)->result_array();

        if (!empty($result))
        {
            return array("status" => -1, "msg" => "已经注册过钱包");
        }


        $method = "create_wallet";
        $data = array("password" => $password);
        $content = $this->http_get($method, $data);
        //插入数据库
        $keystore = json_encode( $content["keystore"] );
        $privatekey = $content["private_key"];
        $address = $content["address"];
        //一个用户只能有一个钱包,暂时先这样
        $sql = "insert into user_wallet
                (email, keystore, password, privatekey, address)
                VALUES
                ('$email', '$keystore', '$password', '$privatekey', '$address')";
        $result = $this->db->query($sql);
        return array("status" => 0, "msg" => "success");
    }

    //导出钱包私钥
    public function export_private_key($email)
    {
        $sql = "SELECT privatekey FROM `user_wallet`
                WHERE `email` ='$email'";
        $result = $this->db->query($sql)->result_array();

        return array("status" => 0, "msg" => "success", "results" =>$result[0]["privatekey"]);
    }

    //导出钱包keystore
    public function export_keystore($email)
    {
        $sql = "SELECT keystore FROM `user_wallet`
                WHERE `email` ='$email'";
        $result = $this->db->query($sql)->result_array();
        return array("status" => 0, "msg" => "success", "results" =>$result[0]["keystore"]);
    }

    //更新用户idfa
    public function update_idfa($email, $idfa)
    {
        $sql = "replace into user_idfa (email, idfa)
                VALUES ('$email', '$idfa')";
        $result = $this->db->query($sql);
        return array("status" => 0, "msg" => "success");
    }




    //测试上传文件
    /*
    public function upload_file()
    {

        var_dump($_FILES);
        $file_name = $_FILES["file"]["name"]; //原始文件名
        $file_type = $_FILES["file"]["type"];//
        $file_size = $_FILES["file"]["size"];//
        $file_path = $_FILES["file"]["tmp_name"];//新的文件名,带路径
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
        return array("url"=>"https://token60.oss-cn-hangzhou.aliyuncs.com/" . $object);
    }
  */

}
