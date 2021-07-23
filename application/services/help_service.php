<?php

use Elasticsearch\ClientBuilder;

class help_service extends MY_Service
{
	public function __construct()
	{
		$this->load->database();
		$this->load->model("help_question");
		$this->load->model("help_search_log");
		$this->load->model("help_category");
	}


	public function test()
	{
		echo microtime(1)."\r\n";
		$hosts = [
			'192.168.5.105:9200',         // IP + Port
		];
		$client = ClientBuilder::create()           // Instantiate a new ClientBuilder
		->setHosts($hosts)      // Set the hosts
		->build();

		$params = [
			'index' => 'livedata'
		];
		$response = $client->indices()->create($params);
		$lastId = 0;
		for ($i=0; $i<=1000; $i++) {
			$sql = "SELECT
	distinct a.author_id, a.*, b.category,
	b.douyin_follower,
	b.aweme_count,
	CEIL(b.total_degg/b.aweme_count) as degg_avg,
	CEIL(b.douyin_follower/b.total_degg) * 100 as follower_rate,
	b.user_shop_status,
	b.is_vip,
  count(c.id) as sale_goods_num,
	sum(c.sales) as sale_total_num
FROM
	`video_live_hourly` a
LEFT JOIN `author_feed_data` b ON a.author_id = b.author_id where a.id > {$lastId} order by a.id asc limit 1000";
			$result = $this->db->query($sql)->result_array();

			$params = [];
			$params['body'] = [];
			foreach ($result as $k=>$v) {
				// 查询商品信息



				$v['birthday'] = empty($v['birthday'])?$v['birthday']:'1970-01-01';
				$v['fetch_day'] = empty($v['fetch_day'])?(isset($lastV['fetch_day'])?$lastV['fetch_day']:'1970-01-01'):'1970-01-01';

				$lastV = $v;
				$params['body'][] = [
					'index' => [
						'_index' => 'livedata',
						'_type' => 'live',
						'_id' => $v['id'],
					]
				];

				$params['body'][] = $v;
			}
			try{
				$responses = $client->bulk($params);
			}catch (Exception $e) {
				echo $e->getMessage()."\r\n";
				$previous = $e->getPrevious();
				if ($previous instanceof Elasticsearch\Common\Exceptions\MaxRetriesException) {
					echo "Max retries!";
				}
			}
			$lastId = $v['id'];
			echo $lastId."\r\n";

		}


	}

	/**
	 * 文章分类
	 */
	public function getCategory()
	{
		$result = $this->help_category->getQuery()->select('id, name, en_name, pid')->get();
		if(!$result) {
			return [];
		}
		$result = $result->result_array();

		$data = [];

		foreach ($result as $v) {
			if ($v['pid'] == 0) {
				$v['child'] = [];
				$data[$v['id']] = $v;
			}else{
				$data[$v['pid']]['child'][] = $v;
			}
		}
		return array_values($data);
	}

	/**
	 * 页面搜索
	 */
	public function questionSearch()
	{
		$searchKey = trim($this->input->get_post('key'));

		$query = $this->help_question->getQuery()->select('id, title, content, cate_id');
		if (!empty($searchKey)) {
			$query = $query->like('title', $searchKey);
			$query = $query->or_like('content', $searchKey);
		}
		$results = $query->order_by('sort', 'asc')->get()->result_array();

		if (!empty($searchKey)) {
			foreach ($results as &$v) {
				$v['content'] = $this->formatText($searchKey, $v['content']);
			}
		}
		$this->help_search_log->insertHotKey($searchKey);		// 更新搜索词
		return $results;
	}

	/**
	 * 获取搜索热词
	 */
	public function getSearchHotKeys()
	{
		$data = [];
		$result = $this->help_search_log->getQuery()->select('key')->order_by('num', 'desc')->limit(10)->get();
		if (!empty($result)) {
			$data = $result->result_array();
		}
		$data = array_column($data, 'key');
		return $data;
	}

	public function getArticleInfo()
	{
		$id = intval($this->input->get_post('id'));

		$result = $this->help_question->getQuery()->where('id', $id)->get();
		if (!$result) {
			throw new Exception('文章不存在');
		}
		return $result->row_array();
	}

	/**
	 * 格式化搜索内容
	 * @param $search
	 * @param $content
	 * @return string
	 */
	public function formatText($search, $content)
	{
		$pos = mb_strpos($content, $search);
		if (!$pos) {
			return '';
		}
		$start = ($pos-20)<0?0:$pos-10;
		$end = ($pos+25);
		return '...'.mb_substr($content, $start, $end).'...';
	}


	######################### 后台方法 ################################

	/**
	 * 添加修改文章
	 */
	public function questionAdd()
	{
		$id = intval($this->input->get_post('id'));

		$title = trim($this->input->get_post('title'));
		$content = trim($this->input->get_post('content'));
		$sort = intval($this->input->get_post('sort'));
		$cate_id = intval($this->input->get_post('cate_id'));

		if (empty($title)) throw new Exception('标题不能为空');
		if (mb_strlen($title) > 200) throw new Exception('标题过长');
		if (empty($content)) throw new Exception('内容不能为空');

		$data['title'] = $title;
		$data['content'] = $content;
		$data['sort'] = $sort;
		$data['cate_id'] = $cate_id;
		$cateInfo = $this->help_category->getQuery()->where(['id' => $v['pid']])->get()->row_array();
		$data['cate_name'] = $cateInfo['name'];
		$data['cate_en_name'] = $cateInfo['en_name'];

		if ($id > 0) {
			return $this->help_question->update_entry($id, $data);
		}else{
			return $this->help_question->insert_entry($data);
		}
	}

	public function questionDel()
	{
		$id = intval($this->input->get_post('id'));
		return $this->help_question->delete_entry($id);
	}

	public function getCateList()
	{
		$sql = "select a.id,a.name,a.pid,IF(ISNULL(b.num), 0, b.num) as num from `help_category` a LEFT JOIN (select cate_id,count(id) as num from `help_question` group by cate_id) b on a.id=b.cate_id where a.pid>0 order by a.sort asc";
		$results = $this->help_category->db->query($sql);
		if (!empty($results)) {
			$results = $results->result_array();
			foreach ($results as &$v) {
				$v['pName'] = $this->help_category->getQuery()->where(['id' => $v['pid']])->get()->row_array()['name'];
			}
			return $results;
		}else{
			return [];
		}
	}

	public function getParentCates()
	{
		return $this->help_category->getQuery()->select('id, name')->where(['pid' => 0])->get()->result_array();
	}

	public function categoryAdd()
	{
		$id = intval($this->input->get_post('id'));

		$name = trim($this->input->get_post('name'));
		$en_name = trim($this->input->get_post('en_name'));
		$pid = intval($this->input->get_post('pid'));
		$sort = intval($this->input->get_post('sort'));

		if (empty($name)) throw new Exception('分类名不能为空');
		if (empty($en_name)) throw new Exception('分类英文名不能为空');
		if (mb_strlen($name) > 200) throw new Exception('分类名过长');

		$data['name'] = $name;
		$data['en_name'] = $en_name;
		$data['pid'] = $pid;
		$data['sort'] = $sort;

		if ($id > 0) {
			unset($data['en_name']);
			return $this->help_category->update_entry($id, $data);
		}else{
			return $this->help_category->insert_entry($data);
		}
	}

	public function categoryDel()
	{
		$id = intval($this->input->get_post('id'));
		return $this->help_category->delete_entry($id);
	}

}
