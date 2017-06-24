<?php
//zend by  QQ:2172298892  瑾梦网络
namespace app\http\index\controllers;

class Index extends \app\http\base\controllers\Frontend
{
	public function __construct()
	{
		parent::__construct();
		header('Access-Control-Allow-Origin: *');
		header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
		header('Access-Control-Allow-Headers: X-HTTP-Method-Override, Content-Type, x-requested-with, Authorization');
	}

	public function actionIndex()
	{
		if (IS_POST) {
			$preview = input('preview', 0);

			if ($preview) {
				$module = \app\classes\Compile::getModule('preview');
			}
			else {
				$module = \app\classes\Compile::getModule();
			}

			if ($module === false) {
				$module = \app\classes\Compile::initModule();
			}

			$this->response(array('error' => 0, 'data' => $module ? $module : ''));
		}
		
		$this->assign('page_title', config('shop.shop_name'));
		$this->assign('description', config('shop.shop_desc'));
		$this->assign('keywords', config('shop.shop_keywords'));
		$this->display();
	}

	public function actionNotice()
	{
		$condition = array('is_open' => 1, 'cat_id' => 12);
		$list = $this->db->table('article')->field('article_id, title, author, add_time, file_url, open_type')->where($condition)->order('article_type DESC, article_id DESC')->limit(5)->select();
		$res = array();

		foreach ($list as $key => $vo) {
			$res[$key]['text'] = $vo['title'];
			$res[$key]['url'] = build_uri('article', array('aid' => $vo['article_id']));
		}

		$this->response(array('error' => 0, 'data' => $res));
	}

	public function actionGoods()
	{
		$condition = array('intro' => input('post.type', ''));
		$list = $this->getGoodsList($condition);
		$res = array();
		$endtime = gmtime();

		foreach ($list as $key => $vo) {
			$res[$key]['desc'] = $vo['name'];
			$res[$key]['sale'] = $vo['sales_volume'];
			$res[$key]['stock'] = $vo['goods_number'];
			$res[$key]['price'] = $vo['shop_price'];
			$res[$key]['marketPrice'] = $vo['market_price'];
			$res[$key]['img'] = $vo['goods_thumb'];
			$res[$key]['link'] = $vo['url'];
			$endtime = ($endtime < $vo['promote_end_date'] ? $vo['promote_end_date'] : $endtime);
		}

		$this->response(array('error' => 0, 'data' => $res, 'endtime' => date('Y-m-d H:i:s', $endtime)));
	}

	private function getGoodsList($param = array())
	{
		$data = array('id' => 0, 'brand' => 0, 'intro' => '', 'price_min' => 0, 'price_max' => 0, 'filter_attr' => 0, 'sort' => 'goods_id', 'order' => 'desc', 'keyword' => '', 'isself' => 0, 'hasgoods' => 0, 'promotion' => 0, 'page' => 1, 'type' => 1, 'size' => 10, config('VAR_AJAX_SUBMIT') => 1);
		$data = array_merge($data, $param);
		$cache_id = md5(serialize($data));
		$list = cache($cache_id);

		if ($list === false) {
			$url = url('category/index/products', $data, false, true);
			$res = \ectouch\Http::doGet($url);

			if ($res === false) {
				$res = file_get_contents($url);
			}

			if ($res) {
				$data = json_decode($res, 1);
				$list = (empty($data['list']) ? false : $data['list']);
				cache($cache_id, $list, 600);
			}
		}

		return $list;
	}
}

?>
