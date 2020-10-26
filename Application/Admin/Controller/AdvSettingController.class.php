<?php
/**
 * 广告模板
 */
namespace Admin\Controller;

use Admin\Model\AdvtemplateModel;
use Think\Controller;

class AdvSettingController extends CommonController
{
    //广告模板页面
    public function advTemplateList()
    {
        $this->display();
    }

    //加载广告模板记录
    public function loadAdvTemplateList()
    {
        $i=1;
        $title = I("post.title", "",'trim');
        if ($title <> "") {
            $where[$i] = "(title like '%" . $title . "%')";
            $i++;
        }
        $this->ajaxReturn(PageDataLoad('advtemplate', $where), 'JSON');
    }

    //模板添加
    public function advTemplateAdd()
    {
        $this->display();
    }

    //模板添加处理程序
    //2019-4-18 rml:添加模型自动验证
    public function addAdvTemplate()
    {
        $post = I('post.');
        $msg = "添加广告模板[".$post['title']."]:";
        $data = [
            'title'=>$post['title'],
            'pc_template_name'=>$post['pc_template_name'],
            'wap_template_name'=>$post['wap_template_name'],
            'pc_img_name'=>$post['pc_img_name'],
            'wap_img_name'=>$post['wap_img_name'],
        ];
        $tablename = D('advtemplate');
        if(!$tablename->create($data)){
            $this->addAdminOperate($msg.$tablename->getError());
            $this->ajaxReturn(['status' => 'no', 'msg' => $tablename->getError()]);
        }
        $res = AdvtemplateModel::addTemplate($data);
        if($res){
            $this->addAdminOperate($msg.'广告模板添加成功');
            $this->ajaxReturn(['status' => 'ok', 'msg' => "广告模板添加成功"]);
        }else{
            unlink($post['pc_img_name']);
            unlink($post['wap_img_name']);
            $this->addAdminOperate($msg.'广告模板添加失败');
            $this->ajaxReturn(['status' => 'no', 'msg' => '广告模板添加失败']);
        }
    }

    //模板编辑页面
    public function advTemplateEdit()
    {
        $id = I('get.id');
        $info = AdvtemplateModel::getOneTemplate($id);
        $this->assign('info',$info);
        $this->display();
    }

    //模板编辑处理程序
    public function editAdvTemplate()
    {
        $post = I('post.');
        $template_name = AdvtemplateModel::getTemplateName($post['id']);
        //查询原始数据
        $old = AdvtemplateModel::getOneTemplate($post['id']);
        $msg = "修改广告模板[".$template_name."]:";
        $data = [
            'id' => $post['id'],
            'title'=>$post['title'],
            'pc_template_name'=>$post['pc_template_name'],
            'wap_template_name'=>$post['wap_template_name'],
            'pc_img_name'=>$post['pc_img_name'],
            'wap_img_name'=>$post['wap_img_name'],
        ];
        $tablename = D('advtemplate');
        if(!$tablename->create($data)){
            $this->addAdminOperate($msg.$tablename->getError());
            $this->ajaxReturn(['status' => 'no', 'msg' => $tablename->getError()]);
        }
        $res = AdvtemplateModel::saveTemplate($post['id'],$data);
        if($res){
            if($old['pc_img_name']!=$post['pc_img_name']){
                unlink($old['pc_img_name']);
            }
            if($old['wap_img_name']!=$post['wap_img_name']){
                unlink($old['wap_img_name']);
            }
            $this->addAdminOperate($msg.'广告模板修改成功');
            $this->ajaxReturn(['status' => 'ok', 'msg' => "广告模板修改成功"]);
        }else{
            $this->addAdminOperate($msg.'广告模板修改失败');
            $this->ajaxReturn(['status' => 'no', 'msg' => '广告模板修改失败']);
        }
    }

    //模板单条删除
    public function templateDel()
    {
        $id = I("id");
        $template_name = AdvtemplateModel::getTemplateName($id);
        $msg = "删除广告模板[".$template_name."]:";
        //查询是否有用户或通道在使用此模板
        $use = AdvtemplateModel::checkUse($id);
        if($use){
            $res = AdvtemplateModel::delTemplate($id);
            if ($res) {
                $this->addAdminOperate($msg.'删除成功');
                $this->ajaxReturn(['status' => 'ok', 'msg' => '删除成功']);
            } else {
                $this->addAdminOperate($msg.'删除失败');
                $this->ajaxReturn(['status' => 'no', 'msg' => '删除失败，请重试']);
            }
        }else{
            $this->addAdminOperate($msg.'该模板为默认模板或正在被通道使用中,不能删除');
            $this->ajaxReturn(['status' => 'no', 'msg' => '该模板为默认模板或正在被通道使用中,不能删除']);
        }
    }

    //模板批量删除
    public function delAll()
    {
        $idstr = I("post.idstr", "");
        $msg = "批量删除广告模板:";
        if ($idstr == "") {
            $this->addAdminOperate($msg.'未选择广告模板');
            $this->ajaxReturn(['status' => 'no', 'msg' => '请选择广告模板']);
        }
        $new_idstr = '';
        $idstr_arr = explode(',',$idstr);
        foreach ($idstr_arr as $k=>$v){
            $use = AdvtemplateModel::checkUse($v);
            if($use){
                $new_idstr .= $v.',';
            }
        }
        if($new_idstr) {
            $new_idstr = trim($idstr, ',');
            $r = AdvtemplateModel::delAllTemplate($new_idstr);
            if ($r) {
                $this->addAdminOperate($msg . '删除成功,已删除未被使用或非默认的广告模板');
                $this->ajaxReturn(['status' => 'ok', 'msg' => '批量删除成功,已删除未被使用或非默认的广告模板']);
            } else {
                $this->addAdminOperate($msg . '删除失败');
                $this->ajaxReturn(['status' => 'no', 'msg' => '删除失败，请重试']);
            }
        }else{
            $this->addAdminOperate($msg.'删除失败,所选择的模板都在被使用中或为默认模板');
            $this->ajaxReturn(['status' => 'no', 'msg' => '删除失败,您所选择的模板都正在被使用中或为默认模板,不能删除']);
        }
    }

    //改变广告的默认状态
    public function editDefault()
    {
        $id = I("post.id", "");
        $template_name = AdvtemplateModel::getTemplateName($id);
        $msg = "修改广告模板[".$template_name."]的默认状态:";
        $default = I("post.default", "");
        if($default==1){
            $stat = "修改为默认模板";
        }else{
            $stat = "取消此默认模板";
        }
        $r = AdvtemplateModel::editTemplateDefault($id, $default);
        if ($r) {
            $this->addAdminOperate($msg.$stat.',修改成功');
            $this->ajaxReturn(['status' => 'ok', 'msg' => '修改成功']);
        } else {
            $this->addAdminOperate($msg.$stat.',修改失败');
            $this->ajaxReturn(['status' => 'no', 'msg' => '修改失败']);
        }
    }

    //图片上传
    public function upload()
    {
        $post = I('post.');
        //上传文件
        $save_name = $this->getImgName($post['img_type']);
        $upload = new \Think\Upload(); // 实例化上传类
        $upload->maxSize = 2097152; // 设置附件上传大小
//        $upload->exts = array('xls'); // 设置附件上传类型jpg|png|gif|bmp|jpeg
        $upload->rootPath = C('ADVTEMPLATE_PATH'); // 设置附件上传目录
        $upload->saveName = $save_name;   //文件名
        $upload->subName = date('Y-m-d');   //子目录创建方式，以账号id命名
        // 上传文件
        $info = $upload->uploadOne($_FILES['file']);
        if (! $info) { // 上传错误提示错误信息
            $this->ajaxReturn(['status' => 'no', 'msg' => $upload->getError()]);
        } else { // 上传成功
            $all_path = C('ADVTEMPLATE_PATH').$info['savepath'].$info['savename'];
            $this->ajaxReturn(['status' => 'ok', 'data' => $all_path]);
        }
    }

    //获取文件名称
    public function getImgName($img_type)
    {
        $date_time = date('YmdHis');
        $save_name = $date_time.rand(10000,99999) .$img_type;
        //判断此文件名数据库中是否存在
        $name = C('ADVTEMPLATE_PATH').date('Y-m-d').'/'.$save_name;
        $res = AdvtemplateModel::checkImgName($name);
        if($res){
            return $this->getImgName($img_type);
        }
        return $save_name;
    }
}