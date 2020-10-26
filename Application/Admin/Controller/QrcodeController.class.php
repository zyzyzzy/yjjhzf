<?php
/**
 * 扫码模板
 */

namespace Admin\Controller;

use Admin\Model\QrcodetemplateModel;
use Think\Controller;

class QrcodeController extends CommonController
{
    //扫码模板页面
    public function qrcodeTemplateList()
    {
        //查询所有启用的通道分类
        $where = [
            'del' => 0,
            'status' => 1
        ];
        $payapiclass = M('payapiclass')->where($where)->field('id,classname')->select();
        $this->assign('payapiclass', $payapiclass);
        $this->display();
    }

    //加载扫码模板记录
    public function loadQrcodeTemplateList()
    {
        $i = 1;
        $title = I("post.title", "", 'trim');
        if ($title <> "") {
            $where[$i] = "(title like '%" . $title . "%')";
            $i++;
        }
        $payapiclass_id = I("post.payapiclass_id", "");
        if ($payapiclass_id <> "") {
            $where[$i] = "payapiclass_id = " . $payapiclass_id;
            $i++;
        }
        $this->ajaxReturn(PageDataLoad('qrcodetemplate', $where), 'JSON');
    }

    //模板添加
    public function qrcodeTemplateAdd()
    {
        //查询所有启用的通道分类
        $where = [
            'del' => 0,
            'status' => 1
        ];
        $payapiclass = M('payapiclass')->where($where)->field('id,classname')->select();
        $this->assign('payapiclass', $payapiclass);
        $this->display();
    }

    //模板添加处理程序
    public function addQrcodeTemplate()
    {
        $msg = "添加扫码模板:";
        $post = I('post.');
        //上传文件
        $date_time = date('YmdHis');
        $save_name = $date_time . rand(1000, 9999) . $post['action'];
        $upload = new \Think\Upload(); // 实例化上传类
        $upload->maxSize = 2097152; // 设置附件上传大小
//        $upload->exts = array('xls'); // 设置附件上传类型jpg|png|gif|bmp|jpeg
        $upload->rootPath = C('QRCODE_PATH'); // 设置附件上传目录
        $upload->saveName = $save_name;   //文件名
        $upload->subName = date('Y-m-d');   //子目录创建方式，以账号id命名
        // 上传文件
        $info = $upload->uploadOne($_FILES['file']);
        if (!$info) { // 上传错误提示错误信息
            $this->addAdminOperate($msg . '图片上传有误,' . $upload->getError());
            $this->ajaxReturn(['status' => 'no', 'msg' => $upload->getError()]);
        } else { // 上传成功
            $all_path = C('QRCODE_PATH') . $info['savepath'] . $info['savename'];
            $data = [
                'img_name' => $all_path,
                'title' => $post['title'],
                'template_name' => $post['template_name'],
                'payapiclass_id' => $post['payapiclass_id'],
            ];
            $tablename = D('qrcodetemplate');
            if (!$tablename->create($data)) {
                $this->addAdminOperate($msg . $tablename->getError());
                $this->ajaxReturn(['status' => 'no', 'msg' => $tablename->getError()]);
            }
            $res = QrcodetemplateModel::addTemplate($data);
            if ($res) {
                $this->addAdminOperate($msg . '扫码模板添加成功');
                $this->ajaxReturn(['status' => 'ok', 'msg' => "扫码模板添加成功"]);
            } else {
                unlink($all_path);
                $this->addAdminOperate($msg . '扫码模板添加失败');
                $this->ajaxReturn(['status' => 'no', 'msg' => '扫码模板添加失败']);
            }
        }
    }

    //模板编辑页面
    public function QrcodeTemplateEdit()
    {
        $id = I('get.id');
        $info = QrcodetemplateModel::getOneTemplate($id);
        $this->assign('info', $info);
        //查询所有启用的通道分类
        $where = [
            'del' => 0,
            'status' => 1
        ];
        $payapiclass = M('payapiclass')->where($where)->field('id,classname')->select();
        $this->assign('payapiclass', $payapiclass);
        $this->display();
    }

    //模板编辑处理程序(点击过上传图片,先传图片)
    public function editQrcodeTemplate()
    {
        $post = I('post.');
        $template_name = QrcodetemplateModel::getTemplateName($post['id']);
        $msg = "修改扫码模板[" . $template_name . "]:";
        //上传文件
        $date_time = date('YmdHis');
        $save_name = $date_time . rand(1000, 9999) . $post['action'];
        $upload = new \Think\Upload(); // 实例化上传类
        $upload->maxSize = 2097152; // 设置附件上传大小
//        $upload->exts = array('xls'); // 设置附件上传类型jpg|png|gif|bmp|jpeg
        $upload->rootPath = C('QRCODE_PATH'); // 设置附件上传目录
        $upload->saveName = $save_name;   //文件名
        $upload->subName = date('Y-m-d');   //子目录创建方式，以账号id命名
        // 上传文件
        $info = $upload->uploadOne($_FILES['file']);
        if (!$info) { // 上传错误提示错误信息
            $this->addAdminOperate($msg . '图片上传有误,' . $upload->getError());
            $this->ajaxReturn(['status' => 'no', 'msg' => $upload->getError()]);
        } else { // 上传成功
            $old = QrcodetemplateModel::getOneTemplate($post['id']);
            $all_path = C('QRCODE_PATH') . $info['savepath'] . $info['savename'];
            $data = [
                'id' => $post['id'],
                'img_name' => $all_path,
                'title' => $post['title'],
                'template_name' => $post['template_name'],
                'payapiclass_id' => $post['payapiclass_id'],
            ];
            //2019-4-18 rml：添加模型验证
            $tablename = D('qrcodetemplate');
            if (!$tablename->create($data)) {
                $this->addAdminOperate($msg . $tablename->getError());
                $this->ajaxReturn(['status' => 'no', 'msg' => $tablename->getError()]);
            }
            $res = QrcodetemplateModel::saveTemplate($post['id'], $data);
            if ($res) {
                unlink($old['img_name']);
                $this->addAdminOperate($msg . '扫码模板修改成功');
                $this->ajaxReturn(['status' => 'ok', 'msg' => "扫码模板修改成功"]);
            } else {
                unlink($all_path);
                $this->addAdminOperate($msg . '扫码模板修改失败');
                $this->ajaxReturn(['status' => 'no', 'msg' => '扫码模板修改失败']);
            }
        }
    }

    //模板编辑处理程序(未点击上传图片,直接修改其他内容)
    public function editTemplate()
    {
        $post = I('post.');
        $template_name = QrcodetemplateModel::getTemplateName($post['id']);
        $msg = "修改扫码模板[" . $template_name . "]:";
        $data = [
            'id' => $post['id'],
            'title' => $post['title'],
            'template_name' => $post['template_name'],
            'payapiclass_id' => $post['payapiclass_id'],
        ];
        //2019-4-18 rml：添加模型验证
        $tablename = D('qrcodetemplate');
        if (!$tablename->create($data)) {
            $this->addAdminOperate($msg . $tablename->getError());
            $this->ajaxReturn(['status' => 'no', 'msg' => $tablename->getError()]);
        }
        $res = QrcodetemplateModel::saveTemplate($post['id'], $data);
        if ($res) {
            $this->addAdminOperate($msg . '扫码模板修改成功');
            $this->ajaxReturn(['status' => 'ok', 'msg' => "统计模板修改成功"]);
        } else {
            $this->addAdminOperate($msg . '扫码模板修改失败');
            $this->ajaxReturn(['status' => 'no', 'msg' => '统计模板修改失败']);
        }
    }

    //模板单条删除
    public function templateDel()
    {
        $id = I("id");
        $template_name = QrcodetemplateModel::getTemplateName($id);
        $msg = "删除扫码模板[" . $template_name . "]:";
        //查询是否有用户或通道在使用此模板
        $use = QrcodetemplateModel::checkUserPayapi($id);
        if ($use) {
            $res = QrcodetemplateModel::delTemplate($id);
            if ($res) {
                $this->addAdminOperate($msg . '删除成功');
                $this->ajaxReturn(['status' => 'ok', 'msg' => '删除成功']);
            } else {
                $this->addAdminOperate($msg . '删除失败');
                $this->ajaxReturn(['status' => 'no', 'msg' => '删除失败，请重试']);
            }
        } else {
            $this->addAdminOperate($msg . '扫码模板为默认模板或正在被通道或者用户使用中,不能删除');
            $this->ajaxReturn(['status' => 'no', 'msg' => '扫码模板为默认模板或正在被通道或者用户使用中,不能删除']);
        }
    }

    //模板批量删除
    public function delAll()
    {
        $idstr = I("post.idstr", "");
        $msg = "批量删除扫码模板:";
        if ($idstr == "") {
            $this->addAdminOperate($msg . '未选择扫码模板');
            $this->ajaxReturn(['status' => 'no', 'msg' => '请选择扫码模板']);
        }
        $new_idstr = '';
        $idstr_arr = explode(',', $idstr);
        foreach ($idstr_arr as $k => $v) {
            $use = QrcodetemplateModel::checkUserPayapi($v);
            if ($use) {
                $new_idstr .= $v . ',';
            }
        }
        if ($new_idstr) {
            $new_idstr = trim($new_idstr, ',');
            $r = QrcodetemplateModel::delAllTemplate($new_idstr);
            if ($r) {
                $this->addAdminOperate($msg . '删除了未被使用和非默认的扫码模板');
                $this->ajaxReturn(['status' => 'ok', 'msg' => '已删除未被使用和非默认的扫码模板']);
            } else {
                $this->addAdminOperate($msg . '删除失败');
                $this->ajaxReturn(['status' => 'no', 'msg' => '删除失败，请重试']);
            }
        } else {
            $this->addAdminOperate($msg . '删除失败,所选择的模板都在被使用中或为默认模板');
            $this->ajaxReturn(['status' => 'no', 'msg' => '删除失败,您所选择的模板都正在被使用中为默认模板,不能删除']);
        }
    }

    //改变通道的状态
    public function editDefault()
    {
        $id = I("post.id", "");
        $template_name = QrcodetemplateModel::getTemplateName($id);
        $msg = "修改扫码模板[" . $template_name . "]的默认状态:";
        $default = I("post.default", "");
        if ($default == 1) {
            $stat = "修改为默认模板";
        } else {
            $stat = "取消此默认模板";
        }
        $r = QrcodetemplateModel::editTemplateDefault($id, $default);
        if ($r) {
            $this->addAdminOperate($msg . $stat . ',修改成功');
            $this->ajaxReturn(['status' => 'ok', 'msg' => '修改成功']);
        } else {
            $this->addAdminOperate($msg . $stat . ',修改失败');
            $this->ajaxReturn(['status' => 'no', 'msg' => '修改失败']);
        }
    }

}