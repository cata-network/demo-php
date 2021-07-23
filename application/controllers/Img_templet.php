<?php


class Img_templet extends CI_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->model("img_templet_model");
		$this->load->model("rest_model");
	}

	//随机获取图片库里的一张图片模板
	public function get_random_img(){
		$result = $this->img_templet_model->get_random_img();
		$this->rest_model->print_rest_json($result);
	}

	//获取模板详情
	public function get_templet_info(){
		$pic_id = $this->rest_model->get_request("pic_id");
		$result = $this->img_templet_model->get_templet_info($pic_id);
		$this->rest_model->print_rest_json($result);
	}

}
