<?php
namespace app\http\drp\controllers;

use app\http\base\controllers\Frontend;

class Shop extends Frontend {
    private $region_id;
    private $area_id;
    //自营
    private $isself = 0;
    //促销
    private $promotion = 0;

    public function __construct()
    {
        parent::__construct();
        $this->assign('custom',  C(custom)); //原分销
        $this->assign('customs',  C(customs)); //原分销商
        //L(require(LANG_PATH  . C('shop.lang') . '/drp.php'));
    }

    /**
     * 分销店铺
     */
    public function actionIndex() {
        $province_id = isset($_COOKIE['province']) ? $_COOKIE['province'] : 0;
        $area_info = get_area_info($province_id);
        $this->area_id = $area_info['region_id'];

        $where = "regionId = '$province_id'";
        $date = array('parent_id');
        $this->region_id = get_table_date('region_warehouse', $where, $date, 2);

        if(isset($_COOKIE['region_id']) && !empty($_COOKIE['region_id'])){
            $this->region_id = $_COOKIE['region_id'];
        }
        $shop_id = intval(I('id'));  // 获取参数
        $status=I('status', 1, 'intval');
           // 查询分销店铺
        $shop_info = $this->getShop($shop_id);
        $size = 10;
        $page = I('page', 1, 'intval');
        $status=I('status', 1, 'intval');
        //$cat_id=I('cat_id');

        $type = drp_type($_SESSION['user_id']);//选择分销商品类型
        if($type == 2){
            $goodsid = drp_type_goods($_SESSION['user_id'],$type);//选中分销商品
            foreach ($goodsid as $key) {
                $goods_id.=$key['goods_id'].',';
            }
            $goods_id = substr($goods_id,0,-1) ;
            $where = " AND g.goods_id " . db_create_in($goods_id);
        }elseif($type == 1){
            $catid = drp_type_cat($_SESSION['user_id'],$type);//选中分销商品分类
            foreach ($catid as $key) {
                $cat_id.=$key['cat_id'].',';
            }
            $cat_id = substr($cat_id,0,-1) ;
            $where = " AND g.cat_id " . db_create_in($cat_id);
        }else{
            $where ='';
        }
        if (IS_AJAX) {

            $goodslist = get_goods($where, $this->region_id, $this->area_id, $size, $page, $status,$type);
            exit(json_encode(array('list' => $goodslist['list'], 'totalPage' => $goodslist['totalpage'])));
        }
        $this->assign('shop_info', $shop_info);
        $res = $this->checkShop($shop_id);    // 检测店铺状态
        $this->assign('status', $status);
        $this->assign('page_title', $shop_info['shop_name'].'的店铺');
        $this->assign('description', '快来参观我的店铺吧，惊喜多多优惠多多'); //分享描述
        $this->assign('page_img', $shop_info['headimgurl']); // 分享图片 分销商微信头像
        $this->display();
    }

    /**
     * 获取分销店铺信息
     */
    private function getShop($shop_id = 0) {
        $time = gmtime();
        $sql="SELECT * FROM {pre}drp_shop WHERE id=$shop_id";
        $res=$this->db->getRow($sql);
        $sql="SELECT headimgurl FROM {pre}wechat_user WHERE ect_uid='$res[user_id]'";
        $headimgurl=$this->db->getOne($sql);
        $shop_info = '';
        if ($headimgurl) {
            $shop_info['headimgurl'] =$headimgurl;
        } else {
            $sql="SELECT user_picture FROM {pre}users WHERE user_id='$res[user_id]'";
            $user_picture = $this->db->getOne($sql);
            $shop_info['headimgurl'] = get_image_path($user_picture);
        }
        $shop_info['id'] = $res['id'];
        $shop_info['shop_name'] = C('shop_name') . $res['shop_name'];
        $shop_info['real_name'] = $res['real_name'];
        $shop_info['audit'] = $res['audit'];
        $shop_info['status'] = $res['status'];
        if(empty($res['shop_img'])){
           $shop_info['shop_img'] = elixir('img/user-shop.png');
        }else{
           $shop_info['shop_img'] = get_image_path($res['shop_img']);
        }
        $shop_info['user_id'] = $res['user_id'];
        $shop_info['create_time'] = date("Y-m-d", $res['create_time']);
        if ($res['user_id'] = $_SESSION['user_id']) {
            $shop_info['url'] = url('drp/user/index', array('id' => $res['user_id']));
        }
        $cat=substr($res['goods_id'],0,-1);
        $shop_info['goods_id'] =$cat;

        $type = drp_type($_SESSION['user_id']);//选择分销商品类型
        if($type == 2){
            $goodsid = drp_type_goods($_SESSION['user_id'],$type);//选中分销商品
            foreach ($goodsid as $key) {
                $goods_id.=$key['goods_id'].',';
            }
            $goods_id = substr($goods_id,0,-1) ;
            $where = " AND goods_id " . db_create_in($goods_id);
        }elseif($type == 1){
             $catid = drp_type_cat($_SESSION['user_id'],$type);//选中分销商品分类
            foreach ($catid as $key) {
                $cat_id.=$key['cat_id'].',';
            }
            $cat_id = substr($cat_id,0,-1) ;
            $where = " AND cat_id " . db_create_in($cat_id);
        }else{
            $where = "" ;
        }

        //全部商品数量
        $sql="SELECT count(goods_id) as sum from {pre}goods WHERE is_on_sale = 1 AND is_distribution = 1 AND dis_commission >0 AND is_alone_sale = 1 AND is_delete = 0 $where";
        $sum['all']=$this->db->getOne($sql);
        $shop_info['sum']=$sum['all'];
        //新品商品数量
        $sql="SELECT count(goods_id) as sum FROM {pre}goods WHERE  is_new = 1 AND is_distribution = 1 AND is_on_sale = 1 AND dis_commission >0 AND is_alone_sale = 1 AND is_delete = 0 $where";
        $sum['new']=$this->db->getOne($sql);
        $shop_info['new']=$sum['new'];
        //促销商品数量
        $sql="SELECT count(goods_id) as sum FROM {pre}goods WHERE is_promote = 1 AND is_distribution = 1 AND dis_commission >0 AND promote_start_date <= '$time' AND promote_end_date >= '$time' AND is_on_sale = 1 AND is_alone_sale = 1 AND is_delete = 0 $where";
        $sum['promote']=$this->db->getOne($sql);
        $shop_info['promote']=$sum['promote'];
        return $shop_info;
    }

    /**
     * 检测店铺状态
     */
    private function checkShop($shop_id = 0) {
        $sql = "SELECT * FROM {pre}drp_shop WHERE id='$shop_id'";
        $res = $this->db->getRow($sql);
        if ($res['audit'] != 1) {
            show_message(L('admin_check'), L('in_shop'), url('/'),'fail');
        }
        if ($res['status'] != 1) {
            show_message(L('shop_close'), L('in_shop'), url('/'),'fail');
        }
        return ture;
    }







}
