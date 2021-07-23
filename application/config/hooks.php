<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
| -------------------------------------------------------------------------
| Hooks
| -------------------------------------------------------------------------
| This file lets you define "hooks" to extend CI without hacking the core
| files.  Please see the user guide for info:
|
|	https://codeigniter.com/user_guide/general/hooks.html
|
*/
$hook['post_controller_constructor'][] = array(
	'class'    => 'MyClass',        //钩子调用的类名，可以为空
	'function' => 'userCheck',    //钩子调用的函数名
	'filename' => 'Myclass.php',   //该钩子的文件名
	'filepath' => 'hooks',         //钩子的目录
	'params'   => array('beer', 'wine', 'snacks'),  //传递给钩子的参数
);
