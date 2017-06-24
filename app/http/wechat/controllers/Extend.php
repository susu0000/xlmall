<?php
namespace app\http\wechat\controllers;

use app\http\base\controllers\Backend;

class Extend extends Backend
{
    private $plugin_type = 'wechat';
    private $plugin_name = '';
    private $wechat_type = 0;
    private $wechat_id = 0;

    public function __construct()
    {
        parent::__construct();
        L(require(MODULE_PATH . 'language/' . C('shop.lang') . '/wechat.php'));
        $this->assign('lang', array_change_key_case(L()));

        $this->plugin_name = I('get.ks', '', 'trim');
        //平台微信公众号ID
        $wechat = dao('wechat')->Field('id,type')->where(array('default_wx' => 1))->find();
        $this->wechat_id = $wechat['id'];
        $this->wechat_type = $wechat['type'];
    }

    /**
     * 功能扩展
     */
    public function actionIndex()
    {
        // 数据库中的数据
        $extends = $this->model->table('wechat_extend')
            ->field('name, keywords, command, config, enable, author, website')
            ->where(array('type' => 'function', 'enable' => 1, 'wechat_id' => $this->wechat_id))
            ->order('id asc')
            ->select();
        if (!empty($extends)) {
            $kw = array();
            foreach ($extends as $key => $val) {
                $val['config'] = unserialize($val['config']);
                $kw[$val['command']] = $val;
            }
        }

        $modules = $this->read_wechat();
        if (!empty($modules)) {
            foreach ($modules as $k => $v) {
                $ks = $v['command'];
                // 数据库中存在，用数据库的数据
                if (isset($kw[$v['command']])) {
                    $modules[$k]['keywords'] = $kw[$ks]['keywords'];
                    $modules[$k]['config'] = $kw[$ks]['config'];
                    $modules[$k]['enable'] = $kw[$ks]['enable'];
                }
                if ($this->wechat_type == 0 || $this->wechat_type == 1) {
                    if ($modules[$k]['command'] == 'bd' || $modules[$k]['command'] == 'bonus' || $modules[$k]['command'] == 'ddcx' || $modules[$k]['command'] == 'jfcx' || $modules[$k]['command'] == 'sign' || $modules[$k]['command'] == 'wlcx' || $modules[$k]['command'] == 'zjd' || $modules[$k]['command'] == 'dzp' || $modules[$k]['command'] == 'ggk') {
                        unset($modules[$k]);
                    }
                }
            }
        }
        $this->assign('modules', $modules);
        $this->display();
    }

    /**
     * 功能扩展安装/编辑
     */
    public function actionEdit()
    {
        if (IS_POST) {
            $handler = I('post.handler');
            $cfg_value = I('post.cfg_value');
            $data = I('post.data');
            if (empty($data['keywords'])) {
                $this->message('请填写扩展词', NULL, 2);
            }

            $data['type'] = 'function';
            $data['wechat_id'] = $this->wechat_id;
            // 数据库是否存在该数据
            $rs = $this->model->table('wechat_extend')
                ->field('name, config, enable')
                ->where(array('command' => $data['command'], 'wechat_id' => $this->wechat_id))
                ->find();
            if (!empty($rs)) {
                // 已安装
                if (empty($handler) && !empty($rs['enable'])) {
                    $this->message('插件已安装', NULL, 2);
                } else {
                    //缺少素材
                    if (empty($cfg_value['media_id'])) {
                        $media_id = $this->model->table('wechat_media')->where(array('command' => $this->plugin_name, 'wechat_id' => $this->wechat_id))->getField('id');
                        if ($media_id) {
                            $cfg_value['media_id'] = $media_id;
                        } else {
                            //安装sql(暂时只提供素材数据表)
                            $sql_file = ADDONS_PATH . $this->plugin_type . '/' . $this->plugin_name . '/install.sql';
                            if (file_exists($sql_file)) {
                                //添加素材
                                $sql = file_get_contents($sql_file);
                                $sql = str_replace(array('ecs_wechat_media', '(0', 'http://', 'views/images'), array('{pre}wechat_media', '(' . $this->wechat_id, __HOST__ . url('wechat/index/plugin_show', array('name' => $this->plugin_name)), 'app/modules/' . $this->plugin_type . '/' . $this->plugin_name . '/views/images'), $sql);
                                $this->model->query($sql);
                                //获取素材id
                                $cfg_value['media_id'] = $this->model->table('wechat_media')->where(array('command' => $this->plugin_name, 'wechat_id' => $this->wechat_id))->getField('id');
                            }
                        }
                    }
                    $data['config'] = serialize($cfg_value);
                    $data['enable'] = 1;
                    $this->model->table('wechat_extend')
                        ->data($data)
                        ->where(array('command' => $data['command'], 'wechat_id' => $this->wechat_id))
                        ->save();
                }
            } else {
                //安装sql(暂时只提供素材数据表)
                $sql_file = ADDONS_PATH . $this->plugin_type . '/' . $this->plugin_name . '/install.sql';
                if (file_exists($sql_file)) {
                    //添加素材
                    $sql = file_get_contents($sql_file);
                    $sql = str_replace(array('ecs_wechat_media', '(0', 'http://', 'views/images'), array('{pre}wechat_media', '(' . $this->wechat_id, __HOST__ . url('wechat/index/plugin_show', array('name' => $this->plugin_name)), 'app/modules/' . $this->plugin_type . '/' . $this->plugin_name . '/views/images'), $sql);
                    $this->model->query($sql);
                    //获取素材id
                    $cfg_value['media_id'] = $this->model->table('wechat_media')->where(array('command' => $this->plugin_name, 'wechat_id' => $this->wechat_id))->getField('id');
                }
                $data['config'] = serialize($cfg_value);
                $data['enable'] = 1;
                $this->model->table('wechat_extend')->data($data)->add();
            }
            $this->message('安装编辑成功', url('index'));
        }
        $handler = I('get.handler', '', 'trim');
        // 编辑操作
        if (!empty($handler)) {
            // 获取配置信息
            $info = $this->model->table('wechat_extend')
                ->field('name, keywords, command, config, enable, author, website')
                ->where(array('command' => $this->plugin_name, 'wechat_id' => $this->wechat_id, 'enable' => 1))
                ->find();
            // 修改页面显示
            if (empty($info)) {
                $this->message('请选择要编辑的功能扩展', NULL, 2);
            }
            $info['config'] = unserialize($info['config']);
        }

        $plugin = '\\app\\modules\\'. $this->plugin_type . '\\' . $this->plugin_name . '\\' . ucfirst($this->plugin_name);
        if (class_exists($plugin)) {
            //编辑
            if (!empty($info['config'])) {
                $config = $info;
                $config['handler'] = 'edit';
            } else {
                $config_file = ROOT_PATH . dirname(str_replace('\\', '/', substr($plugin, 1))) . '/config.php';
                $config = require_once($config_file);
            }
            if (!is_array($config)) {
                $config = array();
            }
            // 设置初始起止时间 默认当前时间后一个月
            $current_time = gmtime();
            $config['config']['starttime'] = empty($config['config']['starttime']) ? date('Y-m-d', $current_time) : $config['config']['starttime'];
            $config['config']['endtime'] = empty($config['config']['endtime']) ? date('Y-m-d', strtotime("+1 months")) : $config['config']['endtime'];
            $obj = new $plugin($config);
            $obj->install();
        }
    }


    /**
     * 功能扩展卸载
     */
    public function actionUninstall()
    {
        $keywords = I('get.ks');
        if (empty($keywords)) {
            $this->message('请选择要卸载的功能扩展', NULL, 2);
        }
        $config = $this->model->table('wechat_extend')
            ->where(array('command' => $keywords, 'wechat_id' => $this->wechat_id))
            ->getField('enable');
        $data['enable'] = 0;

        $this->model->table('wechat_extend')
            ->data($data)
            ->where(array('command' => $keywords, 'wechat_id' => $this->wechat_id))
            ->save();
        //删除素材
        $media_count = $this->model->table('wechat_media')->where(array('command' => $keywords, 'wechat_id' => $this->wechat_id))->count();
        if ($media_count > 0) {
            $this->model->table('wechat_media')->where(array('command' => $keywords, 'wechat_id' => $this->wechat_id))->delete();
        }

        $this->message('卸载成功', url('index'));
    }

    /**
     * 获取中奖记录
     */
    public function actionWinnerList()
    {
        $ks = I('get.ks', '', 'trim');
        if (empty($ks)) {
            $this->message('请选择插件', NULL, 2);
        }
        // 初始化 每页分页数量
        $page_num = 10;
        $this->assign('page_num', $page_num);
        // 分页
        $filter['page'] = '{page}';
        $filter['ks'] = $ks;
        $offset = $this->pageLimit(url('winner_list', $filter), $page_num);

        $sql_count = 'SELECT count(*) as number FROM {pre}wechat_prize p LEFT JOIN {pre}wechat_user u ON p.openid = u.openid WHERE p.activity_type = "' . $ks . '" and p.prize_type = 1 and u.subscribe = 1 and u.wechat_id = ' . $this->wechat_id . ' ORDER BY dateline desc ';
        $total = $this->model->query($sql_count);

        $sql = 'SELECT p.id, p.prize_name, p.issue_status, p.winner, p.dateline, p.openid, u.nickname FROM {pre}wechat_prize p LEFT JOIN {pre}wechat_user u ON p.openid = u.openid WHERE p.activity_type = "' . $ks . '" and u.wechat_id = ' . $this->wechat_id . ' and p.prize_type = 1 and u.subscribe = 1 ORDER BY dateline desc  limit ' . $offset;
        $list = $this->model->query($sql);
        if (empty($list)) {
            $list = array();
        }
        foreach ($list as $key => $val) {
            $list[$key]['winner'] = unserialize($val['winner']);
            $list[$key]['dateline'] = local_date($GLOBALS['_CFG']['time_format'], $val['dateline']);
        }
        $this->assign('activity_type', $ks);

        $this->assign('page', $this->pageShow($total[0]['number']));
        $this->assign('list', $list);
        $this->display();

    }

    /**
     * 发放奖品
     */
    public function actionWinnerIssue()
    {
        $id = I('get.id', 0, 'intval');
        $cancel = I('get.cancel');
        $activity_type = I('get.ks', '', 'trim');
        if (empty($id)) {
            $this->message('请选择中奖记录', NULL, 2);
        }
        if (!empty($cancel)) {
            $data['issue_status'] = 0;
            $this->model->table('wechat_prize')->data($data)->where(array('id' => $id, 'wechat_id' => $this->wechat_id))->save();

            $this->message('取消成功', url('winner_list', array('ks' => $activity_type)));
        } else {
            $data['issue_status'] = 1;
            $this->model->table('wechat_prize')->data($data)->where(array('id' => $id, 'wechat_id' => $this->wechat_id))->save();

            $this->message('发放成功', url('winner_list', array('ks' => $activity_type)));
        }

    }

    /**
     * 删除记录
     */
    public function actionWinnerDel()
    {
        $id = I('get.id', 0, 'intval');
        $activity_type = I('get.ks', '', 'trim');
        if (empty($id)) {
            $this->message('请选择中奖记录', NULL, 2);
        }
        $this->model->table('wechat_prize')->where(array('id' => $id, 'wechat_id' => $this->wechat_id))->delete();

        $this->message('删除成功', url('winner_list', array('ks' => $activity_type)));
    }

    /**
     * 获取插件配置
     *
     * @return multitype:
     */
    private function read_wechat()
    {
        $modules = glob(ADDONS_PATH . $this->plugin_type . '/*/config.php');
        foreach ($modules as $file) {
            $config[] = require_once($file);
        }
        return $config;
    }
}
