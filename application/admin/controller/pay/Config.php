<?php

namespace app\admin\controller\pay;

use app\common\controller\Backend;
use app\common\library\Email;
use app\common\model\PayConfig as ConfigModel;
use app\common\model\PayConfig;
use think\Exception;
use app\admin\model\Admin;

/**
 * 系统配置
 *
 * @icon fa fa-cogs
 * @remark 可以在此增改系统的变量和分组,也可以自定义分组和变量,如果需要删除请从数据库中删除
 */
class Config extends Backend
{

    /**
     * @var \app\common\model\Config
     */
    protected $model = null;
    protected $noNeedRight = ['check'];

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new PayConfig();
    }

    /**
     * 查看
     */
    public function index()
    {
        $adminInfo = Admin::get($this->auth->id);
        $this->view->assign('adminInfo', $adminInfo);
        return $this->view->fetch();
    }

    /**
     * 添加
     */
    public function add()
    {
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            if ($params) {
                foreach ($params as $k => &$v) {
                    $v = is_array($v) ? implode(',', $v) : $v;
                }
                try {
                    if (in_array($params['type'], ['select', 'selects', 'checkbox', 'radio', 'array'])) {
                        $params['content'] = json_encode(ConfigModel::decode($params['content']), JSON_UNESCAPED_UNICODE);
                    } else {
                        $params['content'] = '';
                    }
                    $result = $this->model->create($params);
                    if ($result !== false) {
                        try {
                            $this->refreshFile();
                        } catch (Exception $e) {
                            $this->error($e->getMessage());
                        }
                        $this->success();
                    } else {
                        $this->error($this->model->getError());
                    }
                } catch (Exception $e) {
                    $this->error($e->getMessage());
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }
        return $this->view->fetch();
    }

    /**
     * 编辑
     * @param null $ids
     */
    public function edit($ids = NULL)
    {
        if ($this->request->isPost()) {
            $row = $this->request->post("row/a");
            if ($row) {
                $configList = [];
                foreach ($this->model->all() as $v) {
                    if (isset($row[$v['name']])) {
                        $value = $row[$v['name']];
                        if (is_array($value) && isset($value['field'])) {
                            $value = json_encode(ConfigModel::getArrayData($value), JSON_UNESCAPED_UNICODE);
                        } else {
                            $value = is_array($value) ? implode(',', $value) : $value;
                        }
                        $v['value'] = $value;
                        $configList[] = $v->toArray();
                    }
                }
                $this->model->allowField(true)->saveAll($configList);
                try {
                    $this->refreshFile();
                } catch (Exception $e) {
                    $this->error($e->getMessage());
                }
                $this->success();
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }
    }

    public function del($ids = "")
    {
        $name = $this->request->request('name');
        $config = ConfigModel::getByName($name);
        if ($config) {
            try {
                $config->delete();
                $this->refreshFile();
            } catch (Exception $e) {
                $this->error($e->getMessage());
            }
            $this->success();
        } else {
            $this->error(__('Invalid parameters'));
        }
    }

    /**
     * 刷新配置文件
     */
    protected function refreshFile()
    {
        $config = [];
        foreach ($this->model->all() as $k => $v) {

            $value = $v->toArray();
            if (in_array($value['type'], ['selects', 'checkbox', 'images', 'files'])) {
                $value['value'] = explode(',', $value['value']);
            }
            if ($value['type'] == 'array') {
                $value['value'] = (array)json_decode($value['value'], TRUE);
            }
            $config[$value['name']] = $value['value'];
        }
        file_put_contents(APP_PATH . 'extra' . DS . 'payment.php', '<?php' . "\n\nreturn " . var_export($config, true) . ";");
    }

    /**
     * 检测配置项是否存在
     * @internal
     */
    public function check()
    {
        $params = $this->request->post("row/a");
        if ($params) {

            $config = $this->model->get($params);
            if (!$config) {
                return $this->success();
            } else {
                return $this->error(__('Name already exist'));
            }
        } else {
            return $this->error(__('Invalid parameters'));
        }
    }

    /**
     * 发送测试邮件
     * @internal
     */
    public function emailtest()
    {
        $row = $this->request->post('row/a');
        \think\Config::set('site', array_merge(\think\Config::get('site'), $row));
        $receiver = $this->request->request("receiver");
        $email = new Email;
        $result = $email
            ->to($receiver)
            ->subject(__("This is a test mail"))
            ->message('<div style="min-height:550px; padding: 100px 55px 200px;">' . __('This is a test mail content') . '</div>')
            ->send();
        if ($result) {
            $this->success();
        } else {
            $this->error($email->getError());
        }
    }


    /*public function getConfig()
    {
        $configArr = $this->model->where('admin_id',$this->auth->id);
        if ($configArr)
        {
            return $configArr;
        }else{
            $data = array();
            $data['name'] = 'appid';
            $data['group'] = 'basic';
            $data['title'] = '商户ID';
            $data['tip'] = '商户ID';
            $data['type'] = 'string';
            $data['rule'] = '';
            $data['admin_id'] = $this->auth->id;
            $this->model->insert($data);

            $data = array();
            $data['name'] = 'appid';
            $data['group'] = 'basic';
            $data['title'] = '商户ID';
            $data['tip'] = '商户ID';
            $data['type'] = 'string';
            $data['rule'] = '';
            $data['admin_id'] = $this->auth->id;
            $this->model->insert($data);
        }
    }*/

}
