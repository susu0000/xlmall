<?php
//zend by  QQ:2172298892  瑾梦网络
namespace app\classes;

class Compile
{
	static public $savePath = '';

	static public function init()
	{
		self::$savePath = ROOT_PATH . 'storage/app/diy';

		if (!is_dir(self::$savePath)) {
			$fs = new \Symfony\Component\Filesystem\Filesystem();
			$fs->mkdir(self::$savePath);
		}
	}

	static public function setModule($file = 'index', $data = array())
	{
		self::init();

		if (!empty($data)) {
			$data = '<?php exit("no access");' . serialize($data);
			file_put_contents(self::$savePath . '/' . $file . '.php', $data);
		}
	}

	static public function getModule($file = 'index')
	{
		self::init();
		$filePath = self::$savePath . '/' . $file . '.php';

		if (is_file($filePath)) {
			$data = file_get_contents($filePath);
			$data = str_replace('<?php exit("no access");', '', $data);
			return unserialize($data);
		}

		return false;
	}

	static public function cleanModule($file = 'index')
	{
		self::init();
		$filePath = self::$savePath . '/' . $file . '.php';

		if (is_file($filePath)) {
			return unlink($filePath);
		}

		return true;
	}

	static public function initModule()
	{
		$data = array();
		$search = self::load('search');
		$data[] = $search;
		$slider = self::load('picture');
		$res = insert_ads(array('id' => 256, 'num' => 10), true);
		$picImgList = array();

		foreach ($res as $key => $vo) {
			$picImgList[$key] = array('desc' => '', 'img' => get_data_path($vo['ad_code'], 'afficheimg'), 'link' => $vo['ad_link']);
		}

		$slider['data']['imgList'] = $picImgList;
		$data[] = $slider;
		$nav = self::load('nav');
		$res = dao('touch_nav')->where('ifshow=1')->order('vieworder asc, id asc')->select();
		$navImgList = array();

		foreach ($res as $key => $vo) {
			$navImgList[$key] = array('desc' => $vo['name'], 'img' => get_image_path('data/attached/nav/' . $vo['pic']), 'link' => $vo['url']);
		}

		$nav['data']['imgList'] = $navImgList;
		$data[] = $nav;
		$notice = self::load('Announcement');
		$condition = array('is_open' => 1, 'cat_id' => 12);
		$list = dao('article')->field('article_id, title, author, add_time, file_url, open_type')->where($condition)->order('article_type DESC, article_id DESC')->limit(5)->select();
		$res = array();

		foreach ($list as $key => $vo) {
			$res[$key]['text'] = $vo['title'];
			$res[$key]['url'] = build_uri('article', array('aid' => $vo['article_id']));
		}

		$notice['data']['contList'] = $res;
		$data[] = $notice;
		$blank = self::load('Blank');
		$blank['data']['valueHeight'] = 5;
		$data[] = $blank;
		$spike = self::load('Spike');
		$list = self::goodsList(array('intro' => 'promotion'));
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

		$spike['data']['moreLink'] = url('category/index/search', array('intro' => 'new'));
		$spike['data']['imgList'] = $res;
		$spike['data']['endTime'] = date('Y-m-d H:i:s', $endtime);
		$data[] = $spike;
		$blank = self::load('Blank');
		$blank['data']['valueHeight'] = 5;
		$data[] = $blank;
		$slider = self::load('picture');
		$res = insert_ads(array('id' => 257, 'num' => 10), true);
		$picImgList = array();

		foreach ($res as $key => $vo) {
			$picImgList[$key] = array('desc' => '', 'img' => get_data_path($vo['ad_code'], 'afficheimg'), 'link' => $vo['ad_link']);
		}

		$slider['data']['imgList'] = $picImgList;
		$data[] = $slider;
		$blank = self::load('Blank');
		$blank['data']['valueHeight'] = 5;
		$data[] = $blank;
		$title = self::load('Title');
		$title['data']['title'] = '精品推荐';
		$data[] = $title;
		$product = self::load('Product');
		$list = self::goodsList(array('intro' => 'best'));
		$res = array();

		foreach ($list as $key => $vo) {
			$res[$key]['desc'] = $vo['name'];
			$res[$key]['sale'] = $vo['sales_volume'];
			$res[$key]['stock'] = $vo['goods_number'];
			$res[$key]['price'] = $vo['shop_price'];
			$res[$key]['marketPrice'] = $vo['market_price'];
			$res[$key]['img'] = $vo['goods_thumb'];
			$res[$key]['link'] = $vo['url'];
		}

		$product['data']['imgList'] = $res;
		$data[] = $product;
		$blank = self::load('Blank');
		$blank['data']['valueHeight'] = 5;
		$data[] = $blank;
		$slider = self::load('picture');
		$res = insert_ads(array('id' => 258, 'num' => 10), true);
		$picImgList = array();

		foreach ($res as $key => $vo) {
			$picImgList[$key] = array('desc' => '', 'img' => get_data_path($vo['ad_code'], 'afficheimg'), 'link' => $vo['ad_link']);
		}

		$slider['data']['imgList'] = $picImgList;
		$data[] = $slider;
		$blank = self::load('Blank');
		$blank['data']['valueHeight'] = 5;
		$data[] = $blank;
		$title = self::load('Title');
		$title['data']['title'] = '猜你喜欢';
		$title['data']['isStyleSel'] = 1;
		$title['data']['isShowStyle'] = 'text-center';
		$data[] = $title;
		$product = self::load('Product');
		$list = self::goodsList(array('intro' => 'hot'));
		$res = array();

		foreach ($list as $key => $vo) {
			$res[$key]['desc'] = $vo['name'];
			$res[$key]['sale'] = $vo['sales_volume'];
			$res[$key]['stock'] = $vo['goods_number'];
			$res[$key]['price'] = $vo['shop_price'];
			$res[$key]['marketPrice'] = $vo['market_price'];
			$res[$key]['img'] = $vo['goods_thumb'];
			$res[$key]['link'] = $vo['url'];
		}

		$product['data']['imgList'] = $res;
		$data[] = $product;
		self::setModule('index', $data);
		return $data;
	}

	static public function load($module = '')
	{
		$modulePath = BASE_PATH . 'modules/components/' . ucfirst($module) . '.php';
		if (!empty($module) && is_file($modulePath)) {
			return require $modulePath;
		}

		return false;
	}

	static public function goodsList($param = array())
	{
		$data = array('id' => 0, 'brand' => 0, 'intro' => '', 'price_min' => 0, 'price_max' => 0, 'filter_attr' => 0, 'sort' => 'goods_id', 'order' => 'desc', 'keyword' => '', 'isself' => 0, 'hasgoods' => 0, 'promotion' => 0, 'page' => 1, 'type' => 1, 'size' => 10, config('VAR_AJAX_SUBMIT') => 1);
		$data = array_merge($data, $param);
		$cache_id = md5(serialize($data));
		$list = cache($cache_id);

		if ($list === false) {
			$url = url('category/index/products', $data, false, true);
			$res = \ectouch\Http::doGet($url);

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
