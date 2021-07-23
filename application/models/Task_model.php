<?php
/**
 * 任务相关
 *
 */
class Task_model extends CI_Model {

	private $task_table_map = array(
		6 => 'candy_task_invite',
		3 => 'candy_task_telegram',
		4 => 'candy_task_twitter',
	);

	public function __construct() {
		$this->load->database();

		//$this->load->helper('array');
		//$this->db = $this->load->database('candy', TRUE);
		// $this->db2 = $this->load->database('token', TRUE);
	}

	// 获取项目列表
	public function get_projects() {
		$sql = "SELECT * FROM `project` WHERE project_type=3";
		$result = $this->db->query($sql)->result_array();

		foreach ($result as $key => $value) {
			$project_id = $value['id'];
			$result[$key]['task_invite'] = $this->get_task_by_project($project_id, 6);
			$result[$key]['task_telegram'] = $this->get_task_by_project($project_id, 3);
			$result[$key]['task_twitter'] = $this->get_task_by_project($project_id, 4);
		}

		return array("status" => 0, "msg" => "success", "results" => $result);
	}

	// 获取一个项目
	public function get_project($project_id) {
		$sql = "SELECT * FROM `project` WHERE id=$project_id";
		$project = $this->db->query($sql)->row_array();
		$project_id = $project['id'];

		// 邀请注册任务
		// $sql = "SELECT candy_task.id, task_num, task_token_num, intro, start_time, end_time
		//               FROM candy_task
		//               LEFT JOIN candy_task_invite
		//               on candy_task.id=candy_task_invite.task_id
		//               where candy_task.task_type=1 AND candy_task.project_id='$project_id'";
		// $project['task_invite'] = $this->db->query($sql)->row_array();

		$project['task_invite'] = $this->get_task_by_project($project_id, 6);
		$project['task_telegram'] = $this->get_task_by_project($project_id, 3);
		$project['task_twitter'] = $this->get_task_by_project($project_id, 4);

		return array("status" => 0, "msg" => "success", "results" => $project);
	}

	// 创建项目
	public function create_project() {
		$project = $_REQUEST;

		// 创建项目
		$data = elements(array('email', 'logo', 'banner', 'name', 'intro',
			'token_name', 'supply', 'coin_type', 'project_type',
			'start_time', 'end_time'), $project);
		$sql = $this->db->insert_string('project', $data);
		$result = $this->db->query($sql);

		// 获取项目 id
		// $project = $this->db->query("SELECT LAST_INSERT_ID()")->row_array();
		$project = $this->db->query("SELECT * FROM project WHERE `id` = LAST_INSERT_ID()")->row_array();

		// 创建或更新任务
		// $this->update_project_tasks($project);

		return array("status" => 0, "msg" => "success", "results" => $result);
	}

	// 更新项目
	public function edit_project($id) {
		$project = $_REQUEST;

		// 更新项目
		$data = elements(array('email', 'logo', 'banner', 'name', 'intro',
			'token_name', 'supply', 'coin_type', 'project_type',
			'start_time', 'end_time'), $project);
		$sql = $this->db->update_string('project', $data, "id = '$id'");
		$result = $this->db->query($sql);

		// 创建或更新任务
		$this->update_project_tasks($project);

		// 获取项目
		// $project = $this->get_project($id);

		return array("status" => 0, "msg" => "success", "results" => $result);
	}

	// 删除项目
	public function delete_project($project_id) {
		$sql = "DELETE FROM project WHERE id = '$project_id'";
		$result = $this->db->query($sql);

		return array("status" => 0, "msg" => "success", "results" => $result);
	}

	/**
	 * 获取任务
	 */
	public function get_task_by_project($project_id, $task_type) {
		$table_name = $this->task_table_map[$task_type];

		$sql = "SELECT * FROM " . $table_name . " WHERE project_id = '$project_id'";
		$result = $this->db->query($sql)->row_array();

		// $task_id = $_REQUEST["task_id"];
		// $sql = "SELECT * FROM candy_task_invite WHERE task_id = '$task_id'";
		// $result = $this->db->query($sql)->row_array();

		return $result;
	}

	/**
	 * 创建或更新项目内的所有任务
	 */
	public function update_project_tasks($project) {
		// Invite
		if ($project['task_invite']) {
			$task = array(
			);
			// if (isset($project['task_invite']['id'])) {
			// 	$task['id'] = $project['task_invite']['id'];
			// }
			// if (isset($project['task_invite']['task_id'])) {
			// 	$task['task_id'] = $project['task_invite']['task_id'];
			// }

			$this->update_task($task, $project['id'], 6);
		} else {
			$this->delete_task($project['id'], 6);
		}

		// Telegram
		if ($project['task_telegram']) {
			$task = array(
				// 'tgt_group' => $project['task_telegram']['tgt_group'],
				'url' => $project['task_telegram']['url'],
			);
			// if (isset($project['task_telegram']['id'])) {
			// 	$task['id'] = $project['task_telegram']['id'];
			// }
			// if (isset($project['task_telegram']['task_id'])) {
			// 	$task['task_id'] = $project['task_telegram']['task_id'];
			// }

			$this->update_task($task, $project['id'], 3);
		} else {
			$this->delete_task($project['id'], 3);
		}

		// Twitter
		if ($project['task_twitter']) {
			$task = array(
				// 'tgt_group' => $project['task_twitter']['tgt_group'],
				'url' => $project['task_twitter']['url'],
			);
			// if (isset($project['task_twitter']['id'])) {
			// 	$task['id'] = $project['task_twitter']['id'];
			// }
			// if (isset($project['task_twitter']['task_id'])) {
			// 	$task['task_id'] = $project['task_twitter']['task_id'];
			// }

			$this->update_task($task, $project['id'], 4);
		} else {
			$this->delete_task($project['id'], 4);
		}
	}

	public function update_candy_task($task, $project_id, $task_type) {
		// project + task_type 唯一
		// $sql = "INSERT INTO candy_task (project_id, task_type, intro)
		//         VALUES ('$project_id', '$task_type', '$intro')
		//         ON DUPLICATE KEY UPDATE `intro` = '$intro'";

		$sql = "SELECT * FROM candy_task WHERE project_id = '$project_id' AND task_type = '$task_type'";
		$result = $this->db->query($sql)->row_array();

		$data = elements(array('project_id', 'task_type'), $task);
		if (isset($result)) {
			$sql = $this->db->update_string('candy_task', $data, "project_id = '$project_id' AND task_type = '$task_type'");
			$this->db->query($sql);
		} else {
			$sql = $this->db->insert_string('candy_task', $data);
			$this->db->query($sql);
			// $result = $this->db->query("SELECT LAST_INSERT_ID()")->row_array();
			$result = $this->db->query("SELECT * FROM candy_task WHERE `id` = LAST_INSERT_ID()")->row_array();
		}
		return $result;
	}

	public function update_task($task, $project_id, $task_type) {
		$task['project_id'] = $project_id;
		$task['task_type'] = $task_type;

		// 更新 candy_task 表
		$candy_task = $this->update_candy_task($task, $project_id, $task_type);

		// task_id 唯一
		// $sql = "INSERT INTO candy_task_invite (project_id, task_id, invite_url)
		//               VALUES ('$project_id', '$task_id', '$invite_url')
		//               ON DUPLICATE KEY UPDATE `invite_url` = 'ddd'";
		// $result = $this->db->query($sql);

		// 更新 子任务 表
		$table_name = $this->task_table_map[$task_type];
		$result = $this->get_task_by_project($project_id, $task_type);

		if ($task_type == 6) {
			$data = elements(array('project_id'), $task);
		} else if ($task_type == 3) {
			$data = elements(array('project_id', 'url'), $task);
		} else if ($task_type == 4) {
			$data = elements(array('project_id', 'url'), $task);
		}

		// 更新下 task_id
		if ($candy_task) {
			$data['task_id'] = $candy_task['id'];
		}
		if (isset($result)) {
			$sql = $this->db->update_string($table_name, $data, "project_id = '$project_id'");
			$this->db->query($sql);
		} else {
			$sql = $this->db->insert_string($table_name, $data);
			$this->db->query($sql);
			// $result = $this->db->query("SELECT LAST_INSERT_ID()")->row_array();
			$result = $this->db->query("SELECT * FROM " . $table_name . " WHERE `id` = LAST_INSERT_ID()")->row_array();
		}

		return $result;
	}

	// 删除任务
	public function delete_task($project_id, $task_type) {
		$sql = "DELETE FROM candy_task WHERE project_id = '$project_id' AND task_type = '$task_type'";
		$result = $this->db->query($sql);

		$table_name = $this->task_table_map[$task_type];
		$sql = "DELETE FROM " . $table_name . " WHERE project_id = '$project_id'";
		$result = $this->db->query($sql);

		return $result;
	}

	// 创建邀请任务
	/*public function create_task_invite($project_id, $start_time, $end_time, $task_num, $task_token_num) {
			$sql = "INSERT INTO candy_task_invite
	                (project_id, start_time, end_time, task_num, task_token_num)
	                VALUES
	                ($project_id, '$start_time', '$end_time', $task_num, $task_token_num)";
			$result = $this->db->query($sql);
			return array("status" => 0, "msg" => "success");
*/

	// 邀请任务
	public function create_code($user_id) {
		static $source_string = 'J2RSTUV67MWX89KLYZE5FCDG3HQA4B1NOPI';
		$num = $user_id;
		$code = '';
		while ($num > 0) {
			$mod = $num % 35;
			$num = ($num - $mod) / 35;
			$code = $source_string[$mod] . $code;
		}
		if (empty($code[3])) {
			$code = str_pad($code, 4, '0', STR_PAD_LEFT);
		}

		return $code;
	}

	public function decode($code) {
		static $source_string = 'J2RSTUV67MWX89KLYZE5FCDG3HQA4B1NOPI';
		if (strrpos($code, '0') !== false) {
			$code = substr($code, strrpos($code, '0') + 1);
		}

		$len = strlen($code);
		$code = strrev($code);
		$num = 0;
		for ($i = 0; $i < $len; $i++) {
			$num += strpos($source_string, $code[$i]) * pow(35, $i);
		}
		return $num;
	}

}
