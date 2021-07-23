<?php
/**
 * Created by PhpStorm.
 * User: mac
 * Date: 2020/9/27
 * Time: 2:21 PM
 */

class Rabq
{
    protected $CI;
    public function __construct() {
        $this->CI = &get_instance();
    }


    //向队列里面加数据，$data为array格式
    public function add_queue($data)
    {
        $conn = [
            // Rabbitmq 服务地址
            'host' => '49.232.164.146',
            // Rabbitmq 服务端口
            'port' => '5672',
            // Rabbitmq 帐号
            'login' => 'root',
            // Rabbitmq 密码
            'password' => 'Rootali1',
            'vhost'=>'/'
        ];

        //创建连接和channel
        $conn = new AMQPConnection($conn);
        if(!$conn->connect()) {
            die("Cannot connect to the broker!\n");
        }
        $channel = new AMQPChannel($conn);

        // 用来绑定交换机和队列
        $routingKey = 'real_time_get_info';
        $ex = new AMQPExchange($channel);
        //交换机名称
        $exchangeName = 'real_time_get_info';
        $ex->setName($exchangeName);

        //设置交换机类型
        $ex->setType(AMQP_EX_TYPE_DIRECT);
        //设置交换机是否持久化消息
        $ex->setFlags(AMQP_DURABLE);
        $ex->declareExchange();
        $message = json_encode($data);
        #$message = "hello world";
        return $ex->publish( $message, $routingKey );
    }


    //向队列里面添加video_id
    public function add_video($video_id) {
        $data = array("id"=>$video_id, "type"=>"video"); //视频信息
        return $this->add_queue($data);
    }

    //向队列里面添加author_id
    public function add_daren($author_id) {
        $data = array("id"=>$author_id, "type"=>"author"); //视频信息
        return $this->add_queue($data);
    }

    //向队列里面添加author_id
    public function add_daren_live($author_id) {
        $sql = "insert into user_room_id_online 
                (add_time, author_id)
                values 
                (NOW(), '$author_id')";

        $result = $this->CI->db->query($sql);
        return 0;
    }


    //向队列里面添加room_id

    //获得302跳转的真实url
    function get_real_url($url){
        $header = get_headers($url,1);
        if (strpos($header[0],'301') || strpos($header[0],'302')) {
            if(is_array($header['Location'])) {
                return $header['Location'][count($header['Location'])-1];
            }else{
                return $header['Location'];
            }
        }else {
            return $url;
        }
    }


    //读取队列数据
    public function get_queue()
    {
        $conn = [
            // Rabbitmq 服务地址
            'host' => '49.232.164.146',
            // Rabbitmq 服务端口
            'port' => '5672',
            // Rabbitmq 帐号
            'login' => 'root',
            // Rabbitmq 密码
            'password' => 'Rootali1',
            'vhost'=>'/'
        ];

        //创建连接和channel
        $conn = new AMQPConnection($conn);
        if(!$conn->connect()) {
            die("Cannot connect to the broker!\n");
        }
        $channel = new AMQPChannel($conn);

        $exchangeName = 'real_time_get_info';

//创建交换机
        $ex = new AMQPExchange($channel);
        $ex->setName($exchangeName);

        $ex->setType(AMQP_EX_TYPE_DIRECT); //direct类型
        $ex->setFlags(AMQP_DURABLE); //持久化
        $ex->declareExchange();

//  创建队列
        $queueName = 'real_time_get_info';
        $q = new AMQPQueue($channel);
        $q->setName($queueName);
        $q->setFlags(AMQP_DURABLE);
        $q->declareQueue();

// 用于绑定队列和交换机，跟 send.php 中的一致。
        $routingKey = 'real_time_get_info';
        $q->bind($exchangeName,  $routingKey);

//接收消息
        $q->consume(function ($envelope, $queue) {
            $msg = $envelope->getBody();
            echo $msg."\n"; //处理消息
        }, AMQP_AUTOACK);

        $conn->disconnect();
    }
}