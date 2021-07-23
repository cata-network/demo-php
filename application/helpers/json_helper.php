<?php

function jsonFormat($code = 1, $msg = '', $data = [])
{
	header("content-type:text/json;charset=utf-8");
	echo json_encode([
		'status' => $code,
		'msg' => $msg,
		'results' => $data,
	], JSON_UNESCAPED_UNICODE);
	exit;
}

function echoJson($res)
{
	header("content-type:text/json;charset=utf-8");
	echo json_encode($res, JSON_UNESCAPED_UNICODE);
	exit;
}
