<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Opencc {

	protected $CI;

	public function __construct() {
		$this->CI = &get_instance();

		$this->CI->load->library('utf8_chinese');
	}

	/**
	 * 简体转换为繁体
	 *
	 * @access    public
	 * @param     string $str 字符串
	 * @return    string
	 */
	public function s2t($str) {
		// $od = opencc_open("s2t.json"); // 简体到繁体
		// $str_t = opencc_convert($str, $od);
		// opencc_close($od);

		$str_t = $this->CI->utf8_chinese->gb2312_big5($str);

		return $str_t;
	}

	/**
	 * 繁体转换为简体
	 *
	 * @access    public
	 * @param     string $str 字符串
	 * @return    string
	 */
	public function t2s($str) {
		// $od = opencc_open("t2s.json"); // 繁体到简体
		// $str_t = opencc_convert($str, $od);
		// opencc_close($od);

		$str_t = $this->CI->utf8_chinese->big5_gb2312($str);

		return $str_t;
	}
}
