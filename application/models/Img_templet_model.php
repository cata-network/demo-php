<?php


class Img_templet_model extends CI_Model
{
	public function __construct()
	{
		$this->load->database();
	}

	//随机获取图片库里的一张图片模板
	public function get_random_img(){
//		$sql = "SELECT * FROM `song_img` AS t1
//				 JOIN (SELECT ROUND(RAND() * (SELECT MAX(id) FROM `song_img`)) AS id) AS t2
//				 WHERE t1.id >= t2.id ORDER BY t1.id ASC LIMIT 1";
        $sql = "SELECT * FROM song_img where recommend=1 order by rand() limit 1";
		$result = $this->db->query($sql)->result_array();
		return array("status" => 0, "msg" => "success", "result" => $result);
	}

	//获取创作模板详情
	public function get_templet_info($pic_id){
		$sql = "select * from song_img where pic_id = '$pic_id' ";
		$result = $this->db->query($sql)->result_array();
		return array("status" => 0, "msg" => "success", "result" => $result);
	}


}
