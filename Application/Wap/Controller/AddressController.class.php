<?php

namespace Wap\Controller;

/**
 * 地址模型控制器 joan
 */
class AddressController extends HomeController {
    /* 地址列表 */

    public function index() {
        if (!is_login()) {
            $this->error("您还没有登陆", U("User/login"));
        }
        $uid = is_login();
        $this->meta_title = '地址列表';
        $addressid = I("uid", 0);
        $condition = ($addressid > 0) ? "id=" . $addressid : "uid='$uid' and status>0";
        $field = "id,realname,cellphone,province,city,area,address,status,take_address";
        $list = M("address")->field($field)->where($condition)->select();
        $total = 3;
        $address_num = count($list);
        $remain = $total - $address_num;
        $display = ($remain < 1) ? "none" : "";
        $this->assign("list", $list);
        $this->assign("address_num", $address_num);
        $this->assign("remain", $remain);
        $this->assign("display", $display);
        $this->display();
    }

    /*
     * 修改地址
     */

    public function save() {
        if (!is_login()) {
            $this->error("您还没有登陆", U("User/login"));
        }
        $address = D('address');
        $uid = is_login();
        $info = M("member")->field("mobile")->where(array("uid" => $uid))->find();
        $mobile = $info["mobile"];
        if (IS_POST) { //提交表单
            $data['realname'] = I('post.realname', '', 'strip_tags');
            $data['cellphone'] = I('post.cellphone', 0, 'floatval');
            $data['province'] = I('post.province', '', 'strip_tags');
            $data['address'] = I('post.address', '', 'strip_tags');
            $data['verify'] = I('post.verify', 0, '');
            $data['take_address'] = $data['province'] . $data['address'];
            $id = I('post.addressid', 0, 'intval');

            $data['uid'] = $uid;
            $data['status'] = 2; //默认地址状态
            $data['create_time'] = NOW_TIME;
            $verify_info = A("User")->checkRegisterVerify($mobile, $data['verify'], 5);
            if ($verify_info["code"] == 200) {
                $address_id = $address->where("uid='$uid'and status=2")->save(array("status" => 1)); //将旧的默认地址设为普通
                if ($address_id) {
                    $thisid = $address->where(array("id" => $id))->save($data);
                    if ($thisid) {
                        $this->ajaxReturn(array('code' => 200, 'message' => "修改成功"));
                    } else {
                        $this->ajaxReturn(array('code' => 500, 'message' => "修改失败"));
                    }
                } else {
                    $this->ajaxReturn(array('code' => 500, 'message' => "修改失败"));
                }
            } else {
                $this->ajaxReturn($verify_info);
            }
        } else {
            $id = I("id", "0");
            $info = M("member")->field("mobile")->where(array("uid" => $uid))->find();
            $mobile = $info["mobile"];
            $result = $address->field("realname,cellphone,address,province,city,area")->where(array("id" => $id))->find();
            $this->meta_title = '修改地址';
            $this->assign("user_mobile", $mobile);
            $this->assign("result", $result);
            $this->assign("addressid", $id);
            $this->display("Address/save");
        }
    }

    /*
     * 新增地址
     */

    public function add($id = null, $pid = 0) {
        if (!is_login()) {
            $this->error("您还没有登陆", U("User/login"));
        }

        $address = D('address');
        $uid = is_login();
        $info = M("member")->field("mobile")->where(array("uid" => $uid))->find();
        $mobile = $info["mobile"];
        if (IS_POST) { //提交表单
            $data['realname'] = I('post.realname', '', 'strip_tags');
            $data['cellphone'] = I('post.cellphone', 0, 'floatval');
            $data['province'] = I('post.province', '', 'strip_tags');
            $data['address'] = I('post.address', '', 'strip_tags');
            $data['verify'] = I('post.verify', 0, 'strip_tags');
            $data['take_address'] = $data['province'] . $data['address'];

            $data['uid'] = $uid;
            $data['status'] = 2;
            $data['create_time'] = NOW_TIME;
            $verify_info = A("User")->checkRegisterVerify($mobile, $data['verify'], 4);
            if ($verify_info["code"] == 200) {
                $address->where("uid='$uid'and status=2")->save(array("status" => 1)); //将旧的默认地址设为普通
                $thisid = $address->add($data);
                if ($thisid) {
                    $this->ajaxReturn(array('code' => 200, 'message' => "添加成功"));
                } else {
                    $this->ajaxReturn(array('code' => 500, 'message' => "添加失败"));
                }
            } else {
                $this->ajaxReturn($verify_info);
            }
        } else {
            $this->meta_title = '新增地址';
            $this->assign("user_mobile", $mobile);
            $this->display("Address/add");
        }
    }

    /*
     * 删除地址
     */

    public function delete() {
        if (!is_login()) {
            $this->error("您还没有登陆", U("User/login"));
        }
        $address = M("address");
        $uid = is_login();
        $id = I('id', 0, 'intval'); // 用intval过滤$_POST['id']
        $count = $address->where("uid='$uid'")->count(); //查询总条数
        if ($count < 2) {
            $this->ajaxreturn(array("code" => 500, "msg" => "删除失败"));
            exit;
        }
        if ($address->where("uid='$uid' and id='$id'")->delete()) {
            $this->ajaxreturn(array("code" => 500, "msg" => "删除成功"));
        } else {
            $this->ajaxreturn(array("code" => 500, "msg" => "删除失败"));
        }
    }

    /*
     * 设置默认地址
     */

    public function shezhi() {
        if (!is_login()) {
            $this->error("您还没有登陆", U("User/login"));
        }
        if (IS_AJAX) {
            $uid = is_login();
            $address = M("address");
            $id = I('id', 0, 'intval');
            $address->where("uid='$uid'and status=2")->save(array("status" => 1)); //将旧的默认地址设为普通
            $result = $address->where("id='$id'")->save(array("status" => 2)); //将当前选中地址设为默认

            if ($result) {
                $data = array('code' => 200, 'message' => '设置成功');
                $this->ajaxreturn($data);
            } else {
                $data = array('code' => 500, 'message' => '设置失败');
                $this->ajaxreturn($data);
            }
        }
    }

    public function build() {

        if (!is_login()) {
            $this->error("您还没有登陆", U("User/login"));
        }
        $address = M("address"); // 实例化address对象
        $id = I('post.id'); // 用intval过滤$_POST['id']
        $id = safe_replace($id); //过滤
        $uid = D("member")->uid();
        $province = I('post.province');
        $province = safe_replace($province); //过滤
        $city = I('post.city');
        $city = safe_replace($city); //过滤
        $area = I('post.area');
        $area = safe_replace($area); //过滤
        $data['province'] = $province;
        if ($province == $city) {
            $data['city'] = '';
        } else {
            $data['city'] = $city;
        }
        if ($area == $city) {
            $data['area'] = '';
        } else {
            $data['area'] = $area;
        }
        $data['address'] = safe_replace(I('post.posi'));
        $data['cellphone'] = safe_replace(I('post.pho'));
        $data['realname'] = safe_replace(I('post.rel'));

        if ($_POST["msg"] == "yes") {
//地址库有默认地址，有则保存
            if ($address->where("uid='$uid' and status='1'")->getField("id")) {
                $address->where("uid='$uid' and status='1'")->save($data);
                $addressid = $address->where("uid='$uid' and status='1'")->getField("id");
                addUserLog('修改默认地址', $uid);
            }
            //地址库有默认地址，有则保存	
            else {
                $data['status'] = 1;
                $data['create_time'] = NOW_TIME;
                $data['orderid'] = $id;
                $data['uid'] = $uid;
                $addressid = $address->add($data);
                addUserLog('新增默认地址', $uid);
            }
            $data['addressid'] = $addressid;
            $data['value'] = "default";
            // 返回新增标识
            $data['msg'] = 'yes';
        } else {
            $data['status'] = 0;
            $data['time'] = NOW_TIME;
            $data['orderid'] = $id;
            $addressid = $address->add($data); // 根据条件保存修改的数据
            addUserLog('新增非默认地址', $uid);
            $data['addressid'] = $addressid;
            $data['msg'] = 'no';
        }
        $this->ajaxReturn($data);
    }

}
