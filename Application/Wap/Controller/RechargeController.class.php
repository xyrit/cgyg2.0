<?php

namespace Wap\Controller;

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/4/22
 * Time: 11:01
 * 充值
 */
class RechargeController extends HomeController {
    /*
     * 充值页面
     */

    public function InfoRecharge() {
        $user_id = is_login();
        if (!$user_id) {
            $this->redirect('User/login');
            exit;
        }
            $balance = M('member')->where('uid=' . $user_id)->getField('account');
            $this->assign('balance', $balance);
            $this->meta_title = '充值橙果币';
            $this->display("Recharge/InfoRecharge");

    }

    /**
     *
     * 进行充值
     */
    public function ToRecharge() {
        $rmb = I('rmb');
        $user_id = is_login();
        if (!$user_id) {
            $this->redirect('User/login');
            exit;
        }
        if (!preg_match('/^\+?[1-9][0-9]*$/', $rmb)) {
            $this->error('输入的金额不正确','Index/index');

        }
        //开始执行充值操作
        $balance = M('member')->where('uid=' . $user_id)->getField('account');

        $Jubaocon = C('jubaoyun');
        $payid = build_pay_no();
        $cookies = array(
            'uid' => $user_id, //用户id
            'oldrmb' => $balance       //充值前
        );
        // 生成充值订单
        //插入 支付表
        $pay = array(
            'pay_code' => $payid,
            'order_code' => '0',
            'uid' => $user_id,
            'status' => 0,
            'add_time' => time(),
            'count_money' => $rmb,
            'total_amount' => $rmb,
            'pay_type' => 4, //线上充值
            'pay_name' => '线上充值',
            'cookies' => serialize($cookies)
        );

        M('order_pay')->add($pay);
        //跳转支付
        $pay_data = array
            (
            'payid' => $payid,
            'partnerid' => $Jubaocon['partnerid'],
            'amount' => $rmb,
            'payerName' => $user_id,
            'goodsName' => '橙果币充值',
            'payMethod' => 'ALL',
            'remark' => '橙果币充值',
        );
        $callBackURL = 'http://' . $_SERVER['SERVER_NAME'] . U('Yunpay/RollbackRecharge');
        //传递支付信息，回调地址

        A('Wap/Yunpay')->index($pay_data, $callBackURL);
    }

    /*
     *  支付成功回调
     */
    public function RechargeSuccess(){

        //判断登录
        $user_id=is_login();
        if(!$user_id)
        {
            $this->redirect('User/login');
            exit;
        }
        $pay_code=I('paycode');
        //根据支付code 查询出订单code
        $where= array(
            'pay_code'=>$pay_code,
            'uid'=>$user_id,
            'status'=>1,

        );
        $money=M('order_pay')->where($where)->getField('total_amount');
        if(!$money)
        {
            $this->redirect('Index/index');
            exit;
        }
        $this->assign('money',$money);


        //查询人气商品
        $goods_data = D('Goods')->getGoodsList();
        $this->assign('goods_data',$goods_data);
        $this->display();

    }

    public function RechargeError(){

        //查询人气商品
        $goods_data = D('Goods')->getGoodsList();
        $this->assign('goods_data',$goods_data);
        $this->display();

    }



}
