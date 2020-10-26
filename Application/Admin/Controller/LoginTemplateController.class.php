<?php
/**
 * 登录页面模板
 */

namespace Admin\Controller;

use Admin\Model\LogintemplateModel;

class LoginTemplateController extends CommonController
{
    public function templateList()
    {
        $this->display();
    }

    public function loadTemplateList()
    {
        $where = [];
        $i = 0;
        $temp_name = I("post.temp_name", "", 'trim');
        if ($temp_name <> "") {
            $where[$i] = "(temp_name like '%" . $temp_name . "%')";
            $i++;
        }
        $type = I("post.type", "");
        if ($type <> "") {
            $where[$i] = "type = " . $type;
            $i++;
        }
        $count = LogintemplateModel::getCount($where);
        $datalist = LogintemplateModel::getPageData($where, I('post.page', 0), I('post.limit', 10));
        foreach ($datalist as $key => $val) {
            if ($val['type'] == 1) {
                $datalist[$key]['type_name'] = '管理后台';
            } else {
                $datalist[$key]['type_name'] = '用户后台';
            }
        }
        $ReturnArr = [
            'code' => 0,
            'msg' => '数据加载成功',
            'count' => $count,
            'data' => $datalist
        ];
        $this->ajaxReturn($ReturnArr, 'JSON');
    }

    public function templateAdd()
    {
        $this->display();
    }

    /**
     * 添加登录页面模板
     */
    //2019-3-11 任梦龙：添加操作记录
    public function addTemplate()
    {
        //一进来首先判断是否登录，上传文件时，需要判断是否在线，还得考虑怎么写
//        if (!$this->admin_id) {
//            $this->ajaxReturn(['status' => 'no_login', 'msg' => '请重新登录']);
//        }
        $post = I('post.');
        if ($post['type'] == 1) {
            $sub_path = 'admin';
            $msg = '添加管理后台登录页面模板[' . $post['temp_name'] . ']:';
        } else {
            $sub_path = 'user';
            $msg = '添加用户后台登录页面模板[' . $post['temp_name'] . ']:';
        }
        $tablename = D('logintemplate');
        if (!$tablename->create()) {
            $this->addAdminOperate($msg . $tablename->getError());
            $this->ajaxReturn(['status' => 'no', 'msg' => $tablename->getError()]);
        }

        //检测文件夹是否存在，否则创建
        $path = C('LOGIN_TEMPLATE_PATH');
        $total_path = $path . '/' . $sub_path;
        if (!file_exists($total_path)) {
            mkdir($total_path, 0777, true);  //true表示创建多级目录
        }
        //上传文件
        $upload = new \Think\Upload(); // 实例化上传类
        $upload->maxSize = 2 * 1024 * 1024; // 设置附件上传大小 2M
        $upload->exts = array('jpg', 'png', 'bmp', 'jpeg'); // 设置附件上传允许类型jpg|png|bmp|jpeg
        $upload->rootPath = $path; // 设置附件上传根目录
        $upload->subName = $sub_path;   //子目录创建方式
        $upload->saveName = date('YmdHis') . rand(1000, 9999);    //设置模板文件名称
        $upload->replace = true;   //存在同名文件覆盖
        $info = $upload->uploadOne($_FILES['file']);  // 上传单个文件方法
        if (!$info) {
            $this->addAdminOperate($msg . '上传文件时:' . $upload->getError());
            $this->ajaxReturn(['status' => 'no', 'msg' => $upload->getError()]);
        } else {
            $img_path = $path . $info['savepath'] . $info['savename'];  //拼接文件完整路径
            $tablename->img_path = $img_path;
            $res = $tablename->add();
            if ($res) {
                $this->addAdminOperate($msg . '模板添加成功');
                $this->ajaxReturn(['status' => 'ok', 'msg' => "登录模板添加成功"]);
            } else {
                $this->addAdminOperate($msg . '模板添加失败');
                unlink($img_path);  //如果数据库添加失败，则将这次上传的文件删除
                $this->ajaxReturn(['status' => 'no', 'msg' => '登录模板添加失败']);
            }
        }
    }

    public function templateEdit()
    {
        $id = I('get.id');
        $info = LogintemplateModel::getTempInfo($id);
        $this->assign('info', $info);
        $this->display();
    }

    /**
     * 修改登录模板：未点击上传图片
     */
    //2019-4-23 rml：完善
    public function editTemplate()
    {
        $post = I('post.');
        if ($post['type'] == 1) {
            $msg = '修改管理后台登录页面模板[' . $post['temp_name'] . ']:未修改模板图片,';
        } else {
            $msg = '修改用户后台登录页面模板[' . $post['temp_name'] . ']:未修改模板图片,';
        }
        $find = LogintemplateModel::getTempInfo($post['id']);
        if ($find['type'] == $post['type'] && $find['temp_name'] == $post['temp_name'] && $find['msg'] == $post['msg']) {
            $this->ajaxReturn(['status' => 'no', 'msg' => '未作任何修改,请确认']);
        }
        $tablename = D('logintemplate');
        if (!$tablename->create()) {
            $this->addAdminOperate($msg . $tablename->getError());
            $this->ajaxReturn(['status' => 'no', 'msg' => $tablename->getError()]);
        }
        $res = $tablename->save();
        if ($res) {
            $this->addAdminOperate($msg . '修改成功');
            $this->ajaxReturn(['status' => 'ok', 'msg' => "登录模板修改成功"]);
        } else {
            $this->addAdminOperate($msg . '修改失败');
            $this->ajaxReturn(['status' => 'no', 'msg' => '登录模板修改失败']);
        }
    }

    /**
     * 修改登录模板：同时也修改登录模板图片
     */
    public function editTemplateUpload()
    {
        //一进来首先判断是否登录
//        if (!$this->admin_id) {
//            $this->ajaxReturn(['status' => 'no_login', 'msg' => '请重新登录']);
//        }
        $post = I('post.');
        if ($post['type'] == 1) {
            $sub_path = 'admin';
            $msg = '修改管理后台登录页面模板[' . $post['temp_name'] . ']:有上传图片,';
        } else {
            $sub_path = 'user';
            $msg = '修改用户后台登录页面模板[' . $post['temp_name'] . ']:有上传图片,';
        }

        $tablename = D('logintemplate');
        if (!$tablename->create()) {
            $this->addAdminOperate($msg . $tablename->getError());
            $this->ajaxReturn(['status' => 'no', 'msg' => $tablename->getError()]);
        }
        //检测文件夹是否存在，否则创建
        $path = C('LOGIN_TEMPLATE_PATH');
        $total_path = $path . '/' . $sub_path;
        if (!file_exists($total_path)) {
            mkdir($total_path, 0777, true);  //true表示创建多级目录
        }
        //上传文件
        $upload = new \Think\Upload(); // 实例化上传类
        $upload->maxSize = 2 * 1024 * 1024; // 设置附件上传大小 2M
        $upload->exts = array('jpg', 'png', 'bmp', 'jpeg'); // 设置附件上传类型jpg|png|bmp|jpeg
        $upload->rootPath = $path; // 设置附件上传根目录
        $upload->subName = $sub_path;   //子目录创建方式
        $upload->saveName = date('YmdHis') . rand(1000, 9999);;   //设置文件名
        $upload->replace = true;   //存在同名文件覆盖
        $info = $upload->uploadOne($_FILES['file']);  // 上传单个文件方法
        $find = LogintemplateModel::getTempInfo($post['id']);
        if (!$info) {
            $this->addAdminOperate($msg . '上传文件时:' . $upload->getError());
            $this->ajaxReturn(['status' => 'no', 'msg' => $upload->getError()]);
        } else {
            $img_path = $path . $info['savepath'] . $info['savename'];  //拼接文件真实路径
            $tablename->img_path = $img_path;
            $res = $tablename->save();
            if ($res) {
                unlink($find['img_path']);  //删除原有模板图片
                $this->addAdminOperate($msg . '修改成功');
                $this->ajaxReturn(['status' => 'ok', 'msg' => "登录模板修改成功"]);
            } else {
                $this->addAdminOperate($msg . '修改失败');
                unlink($img_path);  //如果数据库添加失败，则将这次上传的文件删除
                $this->ajaxReturn(['status' => 'no', 'msg' => '登录模板修改失败']);
            }
        }
    }

    /**
     * 删除模板
     */
    //2019-4-23 rml：完善
    public function templateDel()
    {
        $id = I('post.id');
        $find = LogintemplateModel::getTempInfo($id);
        if ($find['type'] == 1) {
            $msg = '删除管理后台登录页面模板[' . $find['temp_name'] . ']:';
        } else {
            $msg = '删除用户后台登录页面模板[' . $find['temp_name'] . ']:';
        }
        $res = LogintemplateModel::delTemplate($id);
        //删除成功，需要将对于的模板文件删除
        if ($res) {
            if (file_exists($find['img_path'])) {
                unlink($find['img_path']);
            }
            $this->addAdminOperate($msg . '模板删除成功');
            $this->ajaxReturn(['status' => 'ok', 'msg' => '删除成功']);
        }
        $this->addAdminOperate($msg . '模板删除失败');
        $this->ajaxReturn(['status' => 'no', 'msg' => '删除失败，请重试']);
    }

    /**
     * 修改默认模板状态
     */
    //改变通道的状态
    public function defaultEdit()
    {
        $info = I('post.');
        $find = LogintemplateModel::getTempInfo($info['id']);
        if ($find['type'] == 1) {
            $type = '管理后台';
        } else {
            $type = '用户后台';
        }
        if ($info['default'] == 1) {
            $msg = '修改' . $type . '登录模板[' . $find['temp_name'] . ']的默认状态:从普通修改为默认,';
        } else {
            $msg = '修改' . $type . '登录模板[' . $find['temp_name'] . ']的默认状态:从默认修改为普通,';
        }
        $r = LogintemplateModel::editTemplateDefault($info['id'], $info['default'], $info['type']);
        if ($r) {
            //如果有一个是修改为默认的，则需要将先前的都修改为普通
            if ($info['default'] == 1) {
                M("logintemplate")->where(['type' => $info['type'], 'id' => ['NEQ', $info['id']]])->setField("default", 0);
            }
            $this->addAdminOperate($msg . '模板修改成功');
            $this->ajaxReturn(['status' => 'ok', 'msg' => '修改成功']);
        }
        $this->addAdminOperate($msg . '模板修改失败');
        $this->ajaxReturn(['status' => 'no', 'msg' => '修改失败']);

    }

    /**
     * 批量删除
     */
    public function delAll()
    {
        $msg = '批量删除登录模板:';
        $id_str = I("post.idstr", "");
        if ($id_str == "") {
            $this->ajaxReturn(['status' => 'no', 'msg' => '请选择登录模板']);
        }
        $path_arr = LogintemplateModel::getMuiltinfo($id_str);  //要删除的模板数组
        $res = LogintemplateModel::delAllTemplate($id_str);
        //删除成功后同时将模板图片也删除
        if ($res) {
            foreach ($path_arr as $v) {
                if (file_exists($v['img_path'])) {
                    unlink($v['img_path']);
                }
            }
            $this->addAdminOperate($msg . '删除成功');
            $this->ajaxReturn(['status' => 'ok', 'msg' => '删除成功']);
        }
        $this->addAdminOperate($msg . '删除失败');
        $this->ajaxReturn(['status' => 'no', 'msg' => '删除失败，请重试']);

    }
}