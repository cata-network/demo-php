<?php
/**
 * Created by PhpStorm.
 * User: ckx
 * Date: 2018/11/5
 * Time: 下午3:24
 */

class Test_model extends CI_Model
{
    public function __construct()
    {
        $this->load->database();

    }
    public function test_1(){
        $sql = "select asm_member.orgName,asm_member.currency,asm_member.roleNames,adamId,asm_campaign.auto_id,asm_member.manager_level from asm_member
                left join asm_campaign
                on asm_member.email=asm_campaign.email
                WHERE asm_member.email='1214369615@qq.com' and is_delete=0";

        $result = $this->db->query($sql)->result_array();

        if ($result) {
            return array("status" => 0, "msg" => "获取信息成功",
                "results" => $result);
        } else {
            return array("status" => -1, "msg" => "没有该用户信息", "org_name" => null, "currency" => null);
        }

    }
}