<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Created by PhpStorm.
 * User: ckx
 * Date: 2018/11/5
 * Time: 下午3:23
 */

class Test extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Test_model');
        $this->load->model('Rest_model');
    }



    public function test_1(){
		$data = array(
			'email' => 'lf@163.com',
		);
		$id = $this->db->insert('user_item',$data);
		var_dump($id);
    }
}

