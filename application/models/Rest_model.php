<?php
/**
 * Created by PhpStorm.
 * User: ckx
 * Date: 2018/11/6
 * Time: 上午11:59
 */

class Rest_model extends CI_Model {
    public function __construct()
    {
        $this->load->database();
      //  $this->db2 = $this->load->database('user', TRUE); //用户相关的数据，需要读写库
    }

    //获得http请求参数，如果没有，赋一个初始值
    /*
     *常用的请求参数说明：
        app_id, app的id,默认值728200220
        c，app的类别category，默认值 应用
        gc，游戏类别game category，默认值，动作游戏
        n, app的name，默认值， 天天飞车
        d，游戏的描述，默认值，天天飞车介绍，
        email，用户的email，默认值，null@appbk.com
        start, 记录开始位置，默认值 0
        limit，记录个数，默认值 10
        注意，需要在web端保证输入的正确性，php端设置默认值，主要是为了防止代码出错。
     */
    public function get_request($para)
    {
        if ( isset($_REQUEST[$para]) )
        {
            return $_REQUEST[$para];
        }
        else //如果收到的参数为空，给出默认值
        {
            if ( "app_id" == $para )
            {
                return "728200220";
            }
            elseif ("c" == $para)
            {
                return "应用";
            }
            elseif ("gc" == $para)
            {
                return "动作游戏";
            }
            elseif ("n" == $para)
            {
                return "天天飞车";
            }
            elseif ("d" == $para)
            {
                return "天天飞车介绍";
            }
            elseif ("email"== $para)
            {
                return "null@appbk.com";
            }
            elseif ("start" == $para)
            {
                return 0;
            }
            elseif ("limit" == $para)
            {
                return 10;
            }
            else //如果不是上述参数，返回一个空字符串
            {
                return "";
            }
        }
    }

    //输出rest标准的json数据
    public function print_rest_json($result)
    {

		header("content-type:text/json;charset=utf-8");
        //设置no-cache
        //header("Pragma:no-cache");
        echo json_encode($result);
    }

    //输出add和del操作的正确信息
    public function print_success_json()
    {
        $result = array("status"=>200,"message"=>"update success");
        $this->print_rest_json($result);
    }
}
