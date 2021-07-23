<?php
/**
 * Created by PhpStorm.
 * User: ckx
 * Date: 2018/11/6
 * Time: 下午4:02
 */

require_once "resource/aliyun-oss/aliyun-oss-php-sdk-2.0.7.phar";
//include_once $_SERVER['DOCUMENT_ROOT']."/resource/aliyun-oss/aliyun-oss-php-sdk-2.0.7.phar";
class Candy_model extends CI_Model
{
    public function __construct()
    {
        $this->load->database(); //百度文库相关的数据
        // $this->db2 = $this->load->database('token', TRUE); //百度文库相关的数据

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

    /****************candy相关服务*******************/


    //获得任务列表
    public function get_tasks($email)
    {
        //注意时间,已经过期的也给前台,前台判断
        $sql = "SELECT `candy_task`.id as task_id,project_id, `logo`, token_name, candy_task.intro,
                url,`candy_task`.`start_time`,`candy_task`.`end_time`
                FROM `candy_task`
                LEFT JOIN `project`
                on `candy_task`.`project_id` =`project` .`id`
                where task_type=1";

        $result = $this->db->query($sql)->result_array();

        //获得用户领取情况
        $user_task_dict = $this->get_user_candy_dict($email);

        //遍历result,添加用户领取情况,以及剩余情况
        $i = 0;
        foreach ($result as $item)
        {
            $task_id = $item["task_id"];
            if (array_key_exists($task_id, $user_task_dict))
            {
                $obtained = 1;
            }
            else
            {
                $obtained = 0;
            }
            $result[$i]["is_obtained"] = $obtained;
            $task_left = $this->get_task_left($task_id);
            $result[$i]["task_left"] = $task_left;
            $i++;
        }

        return array("status" => 0, "msg" => "success", "results" =>$result);
    }

    //领取candy,需要先在链上同步,然后在数据库同步
    public function get_candy($email, $task_id)
    {
        //先保证只能领取一次
        $sql = "select * from user_candy
                where email='$email' and task_id=$task_id";
        $result = $this->db->query($sql)->result_array();
        if (!empty($result))
        {
            return array("status" => -1, "msg" => "已经领取过此token");
        }

        //判断还没有领取完
        $task_left = $this->get_task_left($task_id);
        if ($task_left<=0)
        {
            return array("status" => -1, "msg" => "任务已经领完");
        }

        //获得token_address信息
        $sql = "SELECT address,project.email,task_token_num FROM `candy_task`
                LEFT JOIN `project`
                on `candy_task`.`project_id`=`project`.`id`
                WHERE `candy_task`.`id` = $task_id";
        $token_result = $this->db->query($sql)->result_array();
        $token_address = $token_result[0]["address"];
        $value = $token_result[0]["task_token_num"];

        //插入数据库
        $sql = "insert into user_candy
                (email, task_id, amount)
                VALUES
                ('$email', '$task_id', '$value')";
        $result = $this->db->query($sql);
        return array("status" => 0, "msg" => "success");
    }

    //获得用户的candy列表
    public function get_user_candies($email)
    {
        $sql = "SELECT project_id,logo,token_name,name, amount,task_id  FROM `project` RIGHT JOIN
                (select project_id,amount,task_id from user_candy
                LEFT JOIN `candy_task`
                on `user_candy`.`task_id`=`candy_task`.`id`
                where user_candy.email='$email')
                as user_task
                on `project`.`id`=user_task.project_id";
        $result = $this->db->query($sql)->result_array();
        return array("status" => 0, "msg" => "success", "results" =>$result);
    }

    /****************candy 商家相关服务*******************/
    //创建一个candy token,需要限制使用,一个账号一个月只能建立一个
    public function create_candy($email, $supply, $token_name, $token_symbol)
    {
        $result = $this->export_private_key($email);
        $privatekey = $result["results"];


        $sql = "insert into project
                (email, supply, name, token_name)
                VALUES
                ('$email', $supply, 'token_name', 'token_symbol')";
        $result = $this->db->query($sql);
        return array("status" => 0, "msg" => "success");

    }

    //创建candy task任务,使用合约的allow功能
    public function create_task($email, $project_id, $start_time, $end_time, $task_num, $task_token_num)
    {
        //步骤1, 获得用户的私钥
        $result = $this->export_private_key($email);
        $privatekey = $result["results"];

        $value = (int)$task_num * (int)$task_token_num;

        //步骤2, 获得合约授信,默认为admin账号
        //先根据项目id,获得项目address
        $project_result = $this->get_project($project_id);
        $address = $project_result["results"][0]["address"];


        //步骤3, 插入数据库
        $sql = "insert into candy_task
                (project_id, start_time, end_time, task_num, task_token_num)
                VALUES
                ($project_id, '$start_time', '$end_time', $task_num, $task_token_num)";
        $result = $this->db->query($sql);
        return array("status" => 0, "msg" => "success");

    }

    /****************价格曲线*******************/
    public function get_token_price_trend($id, $start, $end)
    {
        $start = $start . " 00:00:00";
        $end = $end . " 23:59:59";
        $sql = "select price_usd, fetch_time from coincheckup_price_hourly
                where fetch_time>'$start'
                and fetch_time<'$end'
                and new_id='$id'";
        $result = $this->db2->query($sql)->result_array();
        //var_dump($price_result);

        //highcharts图
        $start_time = strtotime($start);
        $end_time = strtotime($end);

        $limit = round(($end_time - $start_time)/3600-24,0);

        for ($i=$start_time;$i<$end_time;$i=$i+60*60)
        {

            $fetch_date = date("Y-m-d H", $i);
            $date_list[] = $fetch_date;
        }

        #构造图表数据
        $data = array();
        $data["chart"]["type"] = "spline";
        $data["title"]["text"] ="'" . $id . "' --价格度趋势图(最近" .(string)$limit ."小时/". (int)($limit/24)."天)";
        $data["tooltip"]["crosshairs"] = array(array("enabled"=>"true","width"=>1,"color"=>"#d8d8d8"));
        $data["tooltip"]["pointFormat"] = '<span style="color:{series.color}">{series.name}</span>: {point.y} <br/>';
        $data["tooltip"]["shared"] = "true";
        $data["tooltip"]["borderColor"] = "#d8d8d8";
        $data["plotOptions"]["series"]["marker"]["radius"] = 2;
        $data["tooltip"]["xDateFormat"] = "%Y-%m-%d %H:%M";
        $data["plotOptions"]["spline"]["states"]["hover"]["enabled"] = false; //禁用曲线的选择状态

        $data["title"]["style"] = "fontFamily:'微软雅黑', 'Microsoft YaHei',Arial,Helvetica,sans-serif,'宋体',";
        $data["yAxis"] = array(
            array("title"=>array("text"=>"价格趋势")),
        );

        $data["xAxis"]["labels"]["step"] = 1;
        //$data["xAxis"]["startOnTick"] = true; //x轴开始位置对齐
        //$data["xAxis"]["endOnTick"] = true; //x轴结束位置对齐
        $data["xAxis"]["gridLineWidth"] = 1; //纵向网格线宽度
        $data["xAxis"]["tickWidth"] = 0; //设置X轴坐标点是否出现占位及其宽度
        $data["xAxis"]["type"] = "datetime";//设置x轴为时间

        $data["xAxis"]["dateTimeLabelFormats"] = array("millisecond" => "%Y-%m-%d %H\u70b9",
            "second" => "%Y-%m-%d %H点",
            "minute" => "%Y-%m-%d %H点",
            "hour" => "%d日 %H点",
            "day" => "%d日",
            "week" => "%m月%d日",
            "month" => "%y年%m月",
            "year" => "%Y年");


        $data["yAxis"]["title"]["text"] = "价格(USD)";
        $data["yAxis"]["reversed"] = "false";


        //版权信息
        $data["credits"]["text"] = "加密世界";
        $data["credits"]["href"] = "http://www.appbk.com/";
        $data["credits"]["position"]["align"] = "right";
        $data["credits"]["position"]["x"] = -10;
        $data["credits"]["position"]["verticalAlign"] = "bottom";
        $data["credits"]["position"]["y"] = -5;

        //构造y轴数据
        #构造数据key是日期， 内容是内容是
        $hot_rank_data = array();
        foreach ($result as $item)
        {
            $fetch_time = date("Y-m-d H",strtotime($item["fetch_time"]));
            $hot_rank_data[ $fetch_time ] = $item["price_usd"];
        }

        //图表y轴真实数据
        $y_hot_data = array();
        $y_hot_data["name"] = "价格趋势";
        $y_hot_data["yAxis"] = 0;

        $pre_rank_value = NULL; //前一个时间的值
        foreach ( $date_list as $fetch_date )
        {
            //热度数据
            if ( isset( $hot_rank_data[$fetch_date] ) )
            {
                $hot_rank_value = (float)$hot_rank_data[$fetch_date];
            }
            else
            {
                //$hot_rank_value = $pre_rank_value; //如果没有对应的数据，热度假设为1
                $hot_rank_value = NULL;
            }
            $y_hot_data["data"][] = array(strtotime($fetch_date.":00:00")*1000 + 8*60*60*1000, $hot_rank_value);
            $pre_rank_value = $hot_rank_value;
        }
        $data["series"][] = $y_hot_data;
        return $data;
    }

    //简化模式
    public function get_token_price_trend_simple($id, $start, $end)
    {
        if (""==$start) //如果没有选择日期
        {
            $start = date("Y-m-d H:i:s",time()-7*24*60*60);// 7 days ago
            $end = date("Y-m-d H:i:s");
        }
        else
        {
            $start = $start . " 00:00:00";
            $end = $end . " 23:59:59";
        }

        $sql = "select price_usd, fetch_time from coincheckup_price_hourly
                where fetch_time>'$start'
                and fetch_time<'$end'
                and new_id='$id'";
        $result = $this->db2->query($sql)->result_array();


        //highcharts图
        $start_time = strtotime($start);
        $end_time = strtotime($end);

        $limit = round(($end_time - $start_time)/3600-24,0);

        for ($i=$start_time;$i<$end_time;$i=$i+60*60)
        {

            $fetch_date = date("Y-m-d H", $i);
            $date_list[] = $fetch_date;
        }

        #构造图表数据
        $data = array();
        $data["chart"]["type"] = "spline";
        $data["title"]["text"] = null; //标题为空
        $data["tooltip"]["crosshairs"] = array(array("enabled"=>"true","width"=>1,"color"=>"#d8d8d8"));
        $data["tooltip"]["pointFormat"] = '<span style="color:{series.color}">{series.name}</span>: {point.y} <br/>';
        $data["tooltip"]["shared"] = true;
        $data["tooltip"]["borderColor"] = "#d8d8d8";
        $data["plotOptions"]["series"]["marker"]["radius"] = 2;
        $data["tooltip"]["xDateFormat"] = "%Y-%m-%d %H:%M";
        $data["plotOptions"]["spline"]["states"]["hover"]["enabled"] = false; //禁用曲线的选择状态



        $data["xAxis"]["labels"]["step"] = 1;
        //$data["xAxis"]["startOnTick"] = true; //x轴开始位置对齐
        //$data["xAxis"]["endOnTick"] = true; //x轴结束位置对齐
        $data["xAxis"]["gridLineWidth"] = 0; //纵向网格线宽度
        $data["xAxis"]["tickWidth"] = 0; //设置X轴坐标点是否出现占位及其宽度
        $data["xAxis"]["type"] = "datetime";//设置x轴为时间
        $data["xAxis"]["lineColor"] = "#FFFFFF";//设置为白色,不展示
        $data["xAxis"]["labels"]["enabled"] = false;//设置刻度值不显示
        $data["xAxis"]["enabled"] = false;//不显示x轴


        $data["yAxis"]["gridLineWidth"] = 0;
        $data["yAxis"]["lineColor"] = "#FFFFFF";//设置为白色,不展示
        $data["yAxis"]["title"] = null;
        $data["yAxis"]["enabled"] = false;//不显示y轴
        $data["yAxis"]["labels"]["enabled"] = false;//设置刻度值不显示
        $data["legend"]["enabled"] = false;//不显示图例

        //版权信息
        $data["credits"] = null;

        //构造y轴数据
        #构造数据key是日期， 内容是内容是
        $hot_rank_data = array();
        foreach ($result as $item)
        {
            $fetch_time = date("Y-m-d H",strtotime($item["fetch_time"]));
            $hot_rank_data[ $fetch_time ] = $item["price_usd"];
        }

        //图表y轴真实数据
        $y_hot_data = array();
        //$y_hot_data["name"] = "价格趋势";
        $y_hot_data["yAxis"] = 0;

        $pre_rank_value = NULL; //前一个时间的值
        foreach ( $date_list as $fetch_date )
        {
            //热度数据
            if ( isset( $hot_rank_data[$fetch_date] ) )
            {
                $hot_rank_value = (float)$hot_rank_data[$fetch_date];
            }
            else
            {
                //$hot_rank_value = $pre_rank_value; //如果没有对应的数据，热度假设为1
                $hot_rank_value = NULL;
            }
            $y_hot_data["data"][] = array(strtotime($fetch_date.":00:00")*1000 + 8*60*60*1000, $hot_rank_value);
            $pre_rank_value = $hot_rank_value;
        }
        $data["series"][] = $y_hot_data;
        return $data;
    }


    //获得bty的收入趋势图,默认一周的
    public function bty_income_trend($start, $end)
    {
        if (""==$start) //如果没有选择日期
        {
            $start = date("Y-m-d",time()-7*24*60*60);// 7 days ago
            $end = date("Y-m-d");
        }
        else
        {
            $start = $start;
            $end = $end;
        }

        $sql = "select income, date from bty_income_trend
                where date>='$start'
                and date<='$end'";
        $result = $this->db2->query($sql)->result_array();


        //var_dump($result);
        //highcharts图
        $start_time = strtotime($start);
        $end_time = strtotime($end);

        $limit = round(($end_time - $start_time)/3600-24,0);

        for ($i=$start_time;$i<$end_time;$i=$i+24*60*60)
        {

            $fetch_date = date("Y-m-d", $i);
            $date_list[] = $fetch_date;
        }


        #构造图表数据
        $data = array();
        $data["chart"]["type"] = "area";
        $data["title"]["text"] = null; //标题为空
        $data["tooltip"]["crosshairs"] = array(array("enabled"=>"true","width"=>1,"color"=>"#d8d8d8"));
        $data["tooltip"]["pointFormat"] = '{point.y}% <br/>';
        $data["tooltip"]["shared"] = "true";
        $data["tooltip"]["borderColor"] = "#d8d8d8";
        $data["plotOptions"]["series"]["marker"]["radius"] = 2;
        $data["tooltip"]["xDateFormat"] = " ";//空格表示不显示
        $data["plotOptions"]["spline"]["states"]["hover"]["enabled"] = false; //禁用曲线的选择状态

        $data["title"]["style"] = "fontFamily:'微软雅黑', 'Microsoft YaHei',Arial,Helvetica,sans-serif,'宋体',";
        $data["yAxis"] = array(
            array("title"=>array("text"=>"趋势")),
        );

        $data["xAxis"]["labels"]["step"] = 1;
        $data["xAxis"]["dateTimeLabelFormats"] = array("millisecond" => "%Y-%m-%d %H\u70b9",
            "second" => "%Y-%m-%d %H点",
            "minute" => "%Y-%m-%d %H点",
            "hour" => "%d日 %H点",
            "day" => "%m-%d",
            "week" => "%m月%d日",
            "month" => "%y年%m月",
            "year" => "%Y年");
        $data["xAxis"]["gridLineWidth"] = 0; //纵向网格线宽度
        $data["xAxis"]["tickWidth"] = 0; //设置X轴坐标点是否出现占位及其宽度
        $data["xAxis"]["type"] = "datetime";//设置x轴为时间
        $data["xAxis"]["lineColor"] = "#FFFFFF";//设置为白色,不展示
        $data["xAxis"]["labels"]["enabled"] = true;//设置刻度值不显示
        $data["xAxis"]["enabled"] = true;//不显示x轴


        $data["yAxis"]["title"]["text"] = "";
        $data["yAxis"]["reversed"] = false;
        $data["yAxis"]["gridLineWidth"] = 0;
        $data["yAxis"]["lineColor"] = "#FFFFFF";//设置为白色,不展示
        $data["yAxis"]["title"] = null;
        $data["yAxis"]["enabled"] = false;//不显示y轴
        $data["yAxis"]["labels"]["enabled"] = false;//设置刻度值不显示
        $data["legend"]["enabled"] = false;//不显示图例



        //版权信息
        $data["credits"] = null;

        //构造y轴数据
        #构造数据key是日期， 内容是内容是
        $hot_rank_data = array();
        foreach ($result as $item)
        {
            $fetch_time = date("Y-m-d",strtotime($item["date"]));
            $hot_rank_data[ $fetch_time ] = $item["income"];
        }


        //图表y轴真实数据
        $y_hot_data = array();
        $y_hot_data["name"] = "价格趋势";
        $y_hot_data["yAxis"] = 0;

        $pre_rank_value = NULL; //前一个时间的值
        foreach ( $date_list as $fetch_date )
        {
            //数据
            if ( isset( $hot_rank_data[$fetch_date] ) )
            {
                $hot_rank_value = (float)$hot_rank_data[$fetch_date];
            }
            else
            {
                //$hot_rank_value = $pre_rank_value; //如果没有对应的数据，热度假设为1
                $hot_rank_value = NULL;
            }
            $y_hot_data["data"][] = array(strtotime($fetch_date." 00:00:00")*1000 + 8*60*60*1000, $hot_rank_value);
            $pre_rank_value = $hot_rank_value;
        }
        $data["series"][] = $y_hot_data;
        return $data;


    }

    //测试上传文件
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

    /************电报群相关*************/
    //生成uuid链接
    public function telegram($email, $telegram_task_id)
    {
        //step 1,获得任务基础信息
        $sql = "select * from candy_task_telegram where
                id=$telegram_task_id";
        $result = $this->db->query($sql)->result_array();

        $url = "https://t.me/forrest_ruyi_bot";//和bot聊天

        //step 2, build task token and insert the job
        $token = md5("appbk.com|".time());

        $sql = "insert into candy_task_telegram_record
                (telegram_task_id, email, job_token)
                VALUES
                ($telegram_task_id, '$email', '$token')";
        $result = $this->db->query($sql);

        $job_url = $url . "?start=" . $token;
        return array("status" => 0, "msg" => "success", "url"=>$job_url);


    }

    //get telegram job info
    public function get_telegram_job_info($job_token)
    {
        $sql = "SELECT * FROM candy_task_telegram_record
                LEFT JOIN candy_task_telegram
                on candy_task_telegram.id=candy_task_telegram_record.telegram_task_id
                WHERE job_token='$job_token'";
        $result = $this->db->query($sql)->result_array();
        $final_result = array();
        $final_result["status"] = 0;
        $final_result["msg"] = "success";

        $final_result["user_id"] = $result[0]["email"];
        $final_result["invite_link"] = $result[0]["invite_url"];
        $final_result["tgt_group"] = $result[0]["tgt_group"];
        return $final_result;
    }

    //finish telegram job
    public function finish_telegram_job($email, $invite_url)
    {
        //step 1,任务的telegram job id
        $sql = "select * from candy_task_telegram where
                invite_url='$invite_url'";
        $result = $this->db->query($sql)->result_array();

        $job_id = $result[0]["id"];

        //step 2,任务变成完成状态
        $sql = "update candy_task_telegram_record set is_finish=1,
                finish_time=NOW() where email='$email'
                and telegram_task_id=$job_id";
        $result = $this->db->query($sql);

        return array("status" => 0, "msg" => "success");

    }

    public function get_job_status($telegram_task_id)
    {
        $sql = "select  obtain_time,finish_time,is_finish
  from candy_task_telegram_record WHERE telegram_task_id =$telegram_task_id and id=(select MAX(id) from candy_task_telegram_record where telegram_task_id =$telegram_task_id )";
        $result = $this->db->query($sql)->result_array();
        return array("status" => 0, "msg" => "success", "data"=>$result);
    }

    /****************公共服务*******************/
    //syn=1,表示默认的同步,为0,则表示异步,不关心结果
    private function http_get($method, $data, $syn = 1)
    {
        $param_list = array();
        foreach ($data as $key => $value) {
            $param_list[] = $key . "=" . $value;
        }
        $params = implode($param_list, "&");

        $url = "http://112.124.49.17:8006/" . $method . "?" . $params;
        //echo $url;

        if ($syn) //同步
        {

            $text = @file_get_contents($url); //加了@,不报警告
            if (FALSE === $text) {
                return array("status" => -1, "msg" => "get api error");
            }
            $result = json_decode($text, true);
        } else   //异步
        {
            $info = parse_url($url);

            if (!array_key_exists("query", $info)) {
                $info["query"] = "";
            }

            if (!array_key_exists("port", $info)) {
                $info["port"] = 80;
            }

            $fp = fsockopen($info["host"], $info["port"], $errno, $errstr, 3);
            $head = "GET " . $info['path'] . "?" . $info["query"] . " HTTP/1.0\r\n";
            $head .= "Host: " . $info['host'] . "\r\n";
            $head .= "\r\n";
            $write = fputs($fp, $head);


            /*//我们不关心服务器返回,这样就相当于异步运行了
            while (!feof($fp))
            {
                $line = fread($fp,4096);
                echo $line;
            }
            */

            fclose($fp);

            $result = array("status" => 0, "msg" => "success!");
        }
        return $result;
    }

    //获得用户钱包信息,默认为管理员email
    private function get_user_wallet($email="admin@dappbk.com")
    {
        $sql = "SELECT * FROM `user_wallet`
                WHERE `email` ='$email'";
        $result = $this->db->query($sql)->result_array();

        return $result[0];
    }

    //获得candy task剩余情况
    private function get_task_left($task_id)
    {
        //获得已经领取的任务数
        $sql = "SELECT count(*) as num from user_candy
                where task_id=$task_id";
        $result = $this->db->query($sql)->result_array();
        $finished_task_num = $result[0]["num"];

        //获得task的任务数
        $sql = "SELECT task_num from candy_task
                where id=$task_id";
        $result = $this->db->query($sql)->result_array();
        $task_num = $result[0]["task_num"];

        $task_left = $task_num - $finished_task_num;
        return $task_left;

    }

    //获得用户candy列表dict
    private function get_user_candy_dict($email)
    {
        $result = $this->get_user_candies($email);

        $result_dict = array();
        foreach ($result["results"] as $item)
        {
            $task_id =$item["task_id"];
            $result_dict[$task_id] = 1;
        }

        return $result_dict;
    }
}
