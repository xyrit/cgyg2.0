<?php

namespace Wap\Model;

use Think\Model;

/**
 * 验证码
 *
 * @author joan
 */
class VerifyModel extends Model {
    /* 添加手机号验证码 */

    public function verifyAdd($data = '') {
        $verify = M("verify");
        $verify = $verify->data($data)->add();
        //echo M("picture")->getLastSql();
        return $verify;
    }

    /* 查询手机号验证码 */
    /*
      public function getVerify($cellphone = 0, $type = 0) {

      $prefix = C('DB_PREFIX');
      $sql = "SELECT max(id) as id,v.verify,v.cellphone,v.creat_time FROM " . $prefix . "verify as v  " .
      " where id=(select max(id) from os_verify where  (cellphone=" . $cellphone . " and type=" . $type . "))";
      //echo $sql;
      $info = $this->query($sql);
      return $info[0];
      }
     */

    public function getVerify($mobile, $verify, $type) {

        $condition = array();
        $condition['cellphone'] = $mobile;
        $condition['verify'] = $verify;
        $condition['type'] = $type;
        $condition['status'] = 0;
        $this->where($condition)->order('id desc')->find();
        //echo $this->getLastSql();exit;
        return $this->where($condition)->order('id desc')->find();
    }

}
