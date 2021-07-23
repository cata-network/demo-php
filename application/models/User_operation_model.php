<?php


class User_operation_model extends CI_Model
{
	public function __construct()
	{
		$this->load->database();
		$this->load->library('COS');
	}

	//用户上传一个图片
	public function upload_pic($pic, $email, $templet_id, $tag, $title)
	{
		$base64_string= explode(',', $pic); //截取data:image/png;base64, 这个逗号后的字符
		$data = base64_decode($base64_string[1]); //对截取后的字符使用base64_decode进行解码

		//step 2，上传图片
		$key = "img/user/". md5(time() . mt_rand(1, 1000000)) . ".jpg"; //图片cos上的路径名称
		$result = $this->cos->cosClient->putObject(array(
			'Bucket' => $this->cos->bucket,
			'Key' => $key,
			'Body' => $data,
		));
		//var_dump($result);
		$cos_url =	$this->cos->cosClient->getObjectUrl($this->cos->bucket, $key);

		//新增 user_item_create 表
		$data = array(
			'email' => $email,
			'img_url' => $cos_url,
			'templet_id' => $templet_id,
			'fetch_time' => date("Y-m-d H:i:s"),
            'tag'=>$tag,
            'title'=>$title,
		);
		$this->db->insert('user_item_create',$data);
		$create_id = $this->db->insert_id('user_item_create');
		return array("status" => 0, "msg" => "success", "url" => $cos_url, "create_id" => $create_id);
	}

	//用户新增的图片 入库 user_item
	public function stock_pic($create_id, $email, $title, $brief, $price, $tag){
		$sql = "select img_url from user_item_create where id = $create_id";
		$result_img_url = $this->db->query($sql)->result_array();

		//新增 user_item 用户库房表
		$data = array(
			'create_id' => $create_id,
			'email' => $email,
			'title' => $title,
			'brief' => $brief,
			'price' => $price,
            'tag' => $tag,
            'img_url' => $result_img_url[0]['img_url'],
			'fetch_time' => date("Y-m-d H:i:s"),
			'status' => 0
		);
		$this->db->insert('user_item',$data);

		//修改新建图片表 status = 1 已入库
		$sql_create = "update user_item_create set is_stock = 1, update_time = now(),
		            title='$title',brief='$brief',tag='$tag'
					where id = $create_id and email = '$email'";
		$this->db->query($sql_create);


		return array("status" => 0, "msg" => "success" );
	}

	//上架发布 插入 market 表
	public function publish_pic($create_id, $email, $title, $brief, $price, $tag){
		$sql = "select img_url from user_item_create where id = $create_id";
		$result = $this->db->query($sql)->result_array();

		//新增 user_item 用户库房表  入库接口拆出来
//		$data = array(
//			'create_id' => $create_id,
//			'email' => $email,
//			'title' => $title,
//			'brief' => $brief,
//			'price' => $price,
//			'img_url' => $result[0]['img_url'],
//			'fetch_time' => date("Y-m-d H:i:s"),
//			'status' => 0
//		);
//		$this->db->insert('user_item',$data);

		$data = array(
			'create_id' => $create_id,
			'email' => $email,
			'title' => $title,
			'brief' => $brief,
			'price' => $price,
            'tag' => $tag,
            'img_url' => $result[0]['img_url'],
			'status' => 1,
			'fetch_time' => date("Y-m-d H:i:s")
		);
		$this->db->insert('market', $data);

		//修改仓库表 user_item 表 status = 1 已入库
		$sql = "update user_item set status = 1, update_time = now()
					where create_id = $create_id and email = '$email'";
		$this->db->query($sql);
		return array("status" => 0, "msg" => "success" );
	}


	//查询用户创作列表 user_item_create
	public function get_user_item_create_list($email, $start, $limit){
		$sql = "select * from user_item_create where email = '$email' 
                order by id desc limit $start, $limit";
		$result = $this->db->query($sql)->result_array();

		$sql_num = "select count(*) as num from user_item_create where email = '$email'";
		$num = $this->db->query($sql_num)->result_array();
		return array("status" => 0, "msg" => "success", "num" => $num[0]['num'], "result" => $result);
	}

	//查询用户创作详情 user_item_create
	public function get_user_item_create_info($create_id, $email){
		$sql = "select * from user_item_create where email = '$email' and id = $create_id ";
		$result = $this->db->query($sql)->result_array();
		return array("status" => 0, "msg" => "success", "result" => $result);
	}

	//查询用户库存表 user_item
	public function get_user_item_list($email, $start, $limit){
		$sql = "select * from user_item where email = '$email' 
                order by id desc limit $start, $limit";
		$result = $this->db->query($sql)->result_array();

		$sql_num = "select count(0) as num from user_item where email = '$email'";
		$num = $this->db->query($sql_num)->result_array();
		return array("status" => 0, "msg" => "success", "num" => $num[0]['num'], "result" => $result);
	}

	//查询用户库存图片详情 user_item_info
	public function get_user_item_info($email, $id){
		$sql = "select * from user_item where email = '$email' and id = $id ";
		$result = $this->db->query($sql)->result_array();
		return array("status" => 0, "msg" => "success", "result" => $result);
	}

	//购买物品,$create_id:商品id, $email:买家email
	public function buy_pic($create_id, $email){
		//step 0, 获得市场上的商品信息得到卖家信息 email,可能有多个历史数据，要当前在售的那个
        //status=1，保证不超卖
		$sql = "select * from market where create_id = $create_id and status=1";
		$result = $this->db->query($sql)->result_array();

		$goods_info = $result[0];
		$seller_email = $goods_info['email'];	//卖家email
		$seller_title = $goods_info['title'];
		$seller_brief = $goods_info['brief'];
		$seller_img_url = $goods_info['img_url'];
		$seller_price = $goods_info['price'];
        $seller_tag = $goods_info['tag'];

//		var_dump($seller_price);

		//开启事务, 失败自动回滚
		$this->db->trans_start();

		//step1 插入交易记录表 item_transaction ;
		$fetch_time = date('Y-m-d H:i:s');
		$sql_item="insert into item_transaction(seller,buyer,user_item_create_id,price,action_time)
                values('$seller_email','$email','$create_id','$seller_price','$fetch_time')";
		$this->db->query($sql_item);

		//step 2 修改市场商品状态. market 表status 状态为 2, $seller_email 卖家邮箱
		$sql = "update market set status = 2
					where create_id = $create_id and email = '$seller_email'";
		$this->db->query($sql);
		//判断执行成功条数
		$number = $this->db->affected_rows();

		//step3 交货: 用户A, user_item.status = 2,用户B插入一条物品 user_item.status = 1;
		//
		$sql = "update user_item set status = 2, update_time = now() 
				 where create_id = $create_id and email = '$seller_email'";
		$result = $this->db->query($sql);

		//
		$data = array(
			'create_id' => $create_id,
			'email' => $email,
			'title' => $seller_title,
			'brief' => $seller_brief,
			'price' => $seller_price,
			'img_url' => $seller_img_url,
            'tag' => $seller_tag,
            'fetch_time' => date("Y-m-d H:i:s"),
			'status' => 0
		);
		$this->db->insert('user_item',$data);

		//step4、付款 user_balance 用户A加钱,用户B减钱
        //判断是否有足够的余额
        $sql = "select * from user_balance where email='$email'";
        $result = $this->db->query($sql)->result_array();
        $buyer_balance = $result[0]["balance"];
        //如果钱不够
        if ($buyer_balance-$seller_price<0) {
            return array("status" => -1, "msg" => "not enough balance");
        }

		$sql = "update user_balance set balance = balance - $seller_price where email = '$email'";
        $this->db->query($sql);


		$sql = "update user_balance set balance = balance + $seller_price where email = '$seller_email'";
		$this->db->query($sql);

		//事务结束
		$this->db->trans_complete();

		return array("status" => 0, "msg" => "success");
	}

}
