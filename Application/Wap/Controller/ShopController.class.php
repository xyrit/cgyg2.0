<?php

/*
 *  购买流程类
 * @2016年4月14日 14:43:57
 */

namespace Wap\Controller;
use Think;
use Vendor\FetionApi\fetion;

class ShopController extends HomeController
{

    //查看购物车
    function shop_cart_list()
    {
        $this->meta_title='购物车';
        $user_id=is_login();
        if(!$user_id)
        {
          is_weixin();
        }

        $pay_type=I('type');

        if($pay_type != 1)
        {
            $pay_type=2;
        }

        $useraccount= M('member')->where('uid='.$user_id)->getField('account');

        $cart=$_COOKIE['cart'];

        $list=$this->getCookiescat($cart);


            $this->assign('list',$list);
             $addpay=time();
            $_SESSION['addpay']=$addpay;

            $goods_data = D('Goods')->getGoodsList();

            $this->assign('goods_data',$goods_data);

        $this->assign('addpay',$addpay?$addpay:0);
        $this->assign('useraccount',$useraccount?$useraccount:0);
        $this->display();
    }

    //结算  订单处理
    function orderInfo()
    {
        $user_id=is_login();   //测试
        if(!$user_id)
        {
            $this->redirect('User/login');
            exit;
        }

        $pay_type=I('type');

        if($pay_type != 1)
        {
            $pay_type=2;
        }

        $str_info= I('info');
        $info = json_decode($str_info,1);

        $user_id=is_login();
        if(!$user_id)
        {
            $this->redirect('User/login');
            exit;
        }
        $cart=$_COOKIE['cart'];

        $list=$this->getCookiesOder($cart);

        if(!$list || $_SESSION['addpay']!=I('addpay'))
        {
            $this->redirect('User/login');exit;

        }

        //过期页面处理 防止重复刷新   清空cookie
        $_SESSION['addpay']='';
        //


        $orders['pay_type']=$pay_type;
        $orders['orders']=$list;

        $this->pay_add($orders);

//        if($rs)
//        {
//            $this->redirect('Shop/pay_success/paycode/'.$rs);
//            exit;
//           // $this->pay_success();
//        }
//        else
//        {
//            $this->redirect('Shop/pay_error');
//            exit;
//          //  $this->pay_error();
//        }


    }

    //拿到cookie 取出期号数据
    private function getCookiesOder($cart)
    {
        // 获取提交订单cookie
        //将商品信息查询出来
        if($cart) {
            $goods = explode(',', $cart);
            $list = array();
            $i = 0;
            
            foreach ($goods as $key => $row) {
                $good = explode(':', $row);
                $arr=array(
                    'lottery_id'=>$good[0],
                    'status'=>0
                );
                $goodsInfo = M('lottery_product')->field('remain_count,purchase_frequency,need_count,pid,single_price,lottery_id,title,marketprice,thumb,(need_count-attend_count) as over_count')->where($arr)->find();
                if (!$goodsInfo) {
                    continue;
                }

                if($good[1]<1)
                {
                    continue;
                }
                //查询这个商品 已经购买成功的
                $arr2=array(
                  'lottery_id'=>$good[0],
                    'is_pay'=>1,
                    'uid'=>is_login()
                );
                $user_attend_count=M('lottery_attend')->where($arr2)->sum('attend_count');  //用户购买次数
                //如果已经买好的 +　现在购买的多余限制的  那么将数量改为可以购买的最大值

                if($goodsInfo['purchase_frequency'] >0){

                   $sygm= $user_attend_count +abs($good[1]) - $goodsInfo['purchase_frequency'];
                    if($sygm >0)
                    {
                        $good[1] =$sygm;

                    } else {

                       continue;
                    }

                }
                $list[$i] = $goodsInfo;
                $list[$i]['attend_count'] =  abs($good[1]);

                $list[$i]['money_count'] = $good[1] * $goodsInfo['single_price'];
                $list[$i]['key'] = $i;
                $list[$i]['thumb'] = getfullImg($list[$i]['thumb']);
                $i++;

            }

            //重新组装 cookie
            return $list;
        }
        return false;
    }


    //拿到cookie 取出期号数据
    private function getCookiescat($cart)
    {
        // 获取提交订单cookie
        //将商品信息查询出来
        if($cart) {
            $goods = explode(',', $cart);
            $list = array();
            $i = 0;

            foreach ($goods as $key => $row) {
                $good = explode(':', $row);
                $arr=array(
                    'lottery_id'=>$good[0],
                    'status'=>0
                );
                $goodsInfo = M('lottery_product')->field('remain_count,purchase_frequency,need_count,pid,single_price,lottery_id,title,marketprice,thumb,(need_count-attend_count) as over_count')->where($arr)->find();
                if (!$goodsInfo) {
                    continue;
                }
                //查询这个商品 已经购买成功的
                $arr2=array(
                    'lottery_id'=>$good[0],
                    'is_pay'=>1,
                    'uid'=>is_login()
                );
                $user_attend_count=M('lottery_attend')->where($arr2)->sum('attend_count');  //用户购买次数
                //如果已经买好的 +　现在购买的多余限制的  那么将数量改为可以购买的最大值


                if($goodsInfo['purchase_frequency'] >0){


                    if($goodsInfo['attend_count'] < $goodsInfo['purchase_frequency']) {

                        $goodsInfo['max'] =$goodsInfo['purchase_frequency']-$user_attend_count;


                    } else {

                        $goodsInfo['max'] = 0;
                    }

                }else
                {

                    $goodsInfo['max']=$goodsInfo['remain_count'];
                }



                if($good[1]>$goodsInfo['max'])
                {
                    $good[1]=$goodsInfo['max'];
                }

                $list[$i] = $goodsInfo;
                $list[$i]['max']=$goodsInfo['max'];
                $list[$i]['attend_count'] =  abs($good[1]);

                $list[$i]['money_count'] = $good[1] * $goodsInfo['single_price'];
                $list[$i]['key'] = $i;
                $list[$i]['thumb'] = getfullImg($list[$i]['thumb']);
                $list[$i]['ygm']=$user_attend_count ?$user_attend_count :0 ;
                $i++;
                //修改限制数量
                $str=$good[0].':'.$good[1];
                $cookie[]=$str;

            }
            $str=implode(',',$cookie);

            //  dump($str);
            //setcookie('cart',null,time()+24*3600,'/');
            setcookie('cart',$str,time()+24*3600,'/');

            //重新组装 cookie
            return $list;
        }
        return false;
    }

    // 生成订单  支付单
    function pay_add($Info=null)
    {

        $user_id=is_login();
        if(!$user_id)
        {
            $this->redirect('User/login');
            exit;
        }
        $orders=$Info['orders'];   //获取订单信息
        if(!$orders)
        {
           $this->error('订单不存在','Index/index');
        }
        $lottery_attend_obj= M('lottery_attend');
        $lottery_attend_obj->startTrans();


        $total_amount=0;      //订单总金额
        $orderids =array();   //订单id
        $goods_status=array();   // 商品状态
        $order_number=build_order_no();

        foreach($orders as $row)
        {
            $lettery_info = M('lottery_product')->field('pid,purchase_frequency,need_count,attend_count,status,single_price')->where('lottery_id='.$row['lottery_id'])->find();

            if($lettery_info['status'] >0)
            {

                continue;
                //  echo '该商品已经开奖或在开奖中';return false;
            }

            //查询是否超过剩余次数
            if(($lettery_info['attend_count']+$row['attend_count']) >$lettery_info['need_count'])
            {
                continue;
                //  echo '购买超出剩余次数';return false;
            }

            //查询用户总共购买次数   购买次数限制
            $where1=array(
                'uid'=>$user_id,
                'is_pay'=>1,
                'lottery_id'=>$row['lottery_id']
            );

            $user_attend_count= $lottery_attend_obj->where($where1)->sum('attend_count');
        if($lettery_info['purchase_frequency'] >0)
        {
            if(($row['attend_count']+$user_attend_count) >$lettery_info['purchase_frequency'] )
            {

                continue;
                //  echo '购买超出购买次数';return false;
            }
        }
            //计算出购买所需金额
            $order_amount=$row['attend_count']*$lettery_info['single_price'];

            //获取用户地址
          //  $addr=get_addr();
            $ip = get_client_ip();
            //获取设备信息
            $get_device=get_device();

            //插入数据库
            $time=time();
            $ftime=substr(microtime(), 2, 3);
            $order_data['lottery_id']=$row['lottery_id'];
            $order_data['uid']=$user_id;
            $order_data['pid']=$lettery_info['pid'];
            $order_data['create_time']=$time;
            $order_data['attend_device']=$row['attend_device'];
            $order_data['create_date_time']=date('Y-m-d H:i:s',$time).'.'.$ftime;
            $order_data['order_amount']=$order_amount;
            $order_data['attend_count']=$row['attend_count'];
            $order_data['order_number']=$order_number;
            $order_data['attend_ip']=$ip;         //IP地址
            $order_data['attend_device']=$get_device;         //设备信息
            $order_data['sfm_time']=date('His',$time).$ftime;//时分秒格式，截取和替换字符串，显示为110722123     用来计算开奖结果

            $rs = $lottery_attend_obj->add($order_data);

            //插入订单表
            if(!$rs)
            {
                $lottery_attend_obj->rollback();
                $this->error('订单不存在','Index/index');
               // return false;
            }

            $total_amount+=$order_amount;   //总金额
            $orderids[]=$rs;
            $goods_status[$lettery_info['lottery_id']]=array(
                'surplus'=>$lettery_info['need_count']-$lettery_info['attend_count'],
                'univalent'=>$lettery_info['single_price'],
                'buynum'=>$user_attend_count
            );

        }

        $cookies['count_money']=$total_amount;
        $cookies['uid']=$user_id;
        $cookies['goods_status']=$goods_status;
        //插入 支付表
        $pay=array(
            'pay_code'  => build_pay_no(),
            'order_code'=>$order_number,
            'uid'=>$user_id,
            'status'=>0,
            'add_time'=>time(),
            'cg_money'=>0,               //需要退回的橙果币
            'count_money'=>$total_amount,
            'total_amount'=>$total_amount,
            'pay_type'=>$Info['pay_type'],
            'cookies'=>serialize($cookies)
        );

        $rs2= M('order_pay')->add($pay);

        if(!$rs2)
        {
            $lottery_attend_obj->rollback();
            $this->error('插入失败','Index/index');

        }
        $lottery_attend_obj->commit();

        //插入记录成功 执行支付方法

        $pay_stauts= $this->payment($pay['pay_code']);

        return $pay_stauts;

    }
    //支付
    public function payment($pay_code)
    {

        $payInfo= M('order_pay')->where("pay_code='$pay_code'")->find();

        if(!$payInfo)
        {
            return false;
        }
        if($payInfo['pay_type']==1)    //余额支付
        {
            //计算金额
            //获取用户余额
            $r_account =  M('member')->where('uid='.$payInfo['uid'])->getField('account');

            if($r_account < $payInfo['count_money'])        //余额不足
            {
                //   echo '余额不足';exit;

                $single_money= $payInfo['count_money']-$r_account;


                //保存余额支付  等支付完成后扣款
                $save=array(
                    'cg_money'=>$r_account,                  //橙果币
                    'count_money'=>$single_money,            //线上需支付金额
                    'total_amount'=>$payInfo['count_money']   //总金额
                );
                M('order_pay')->startTrans();
                $rs3= M('order_pay')->where("pay_code='$pay_code'")->save($save);
                //将余额改为0
                $membersave=array('account'=>0);
                $rs4=  M('member')->where('uid='.$payInfo['uid'])->save($membersave);

                if($rs3 && $rs4)
                {
                    M('order_pay')->commit();
                    //改变支付金额
                    $payInfo['count_money']=$single_money;
                }
                else
                {
                    M('order_pay')->rollback();
                    return false;
                }

            }
            else
            {
                $order_list = M('lottery_attend')->where('order_number='.$payInfo['order_code'].' and uid='.$payInfo['uid'])->select();

                foreach($order_list as $row)
                {
                    $orderids[]=$row['id'];
                }

                //执行购买操作

                $rs= $this->purchase_success(1,$orderids);
                if($rs)      //扣减用户余额
                {
                    $update_money= array(
                        'account'=>array('exp','account-'.$payInfo['count_money'])
                    );
                    $update_pay = array(
                        'status'=>1,
                        'pay_time'=>time(),
                        'pay_name'=>'余额支付',
                        'total_amount'=>$payInfo['count_money']

                    );

                    M('member')->startTrans();
                    $rs1 =  M('member')->where(array('uid'=>$payInfo['uid']))->save($update_money);  //用户金额

                    $rs2= M('order_pay')->where(array('pay_code'=>$payInfo['pay_code']))->save($update_pay); //更新购买记录

                    if($rs1 && $rs2)
                    {

                        M('member')->commit();
                        $this->redirect('Shop/pay_success/paycode/'.$payInfo['pay_code']);
                        return false;
                    }

                }
                M('member')->rollback();
                $this->redirect('Shop/pay_error');
                return false;
            }
            /************余额支付结束**************/
        }
        //线上支付
        $Jubaocon= C('jubaoyun');
        //跳转支付
        $pay_data=array
        (
            'payid'=>$pay_code,
            'partnerid'=>$Jubaocon['partnerid'],
            'amount'=> $payInfo['count_money'],
            'payerName'=> $payInfo['uid'],
            'goodsName'=> $payInfo['order_code'],
            'payMethod'=> 'ALL',
            'remark'=> '购买商品',

        );
        $callserverBackURL =  'http://'.$_SERVER['SERVER_NAME'].U('Yunpay/result');
        $callBackURL =  'http://'.$_SERVER['SERVER_NAME'].U('Shop/pay_success',array('paycode'=>$pay_code));
        //传递支付信息，回调地址
        A('Wap/Yunpay')->index($pay_data,$callserverBackURL,$callBackURL);

    }

    //客户端成功回调

    public function pay_success(){
        $this->meta_title='购买成功';
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
        $order_code=M('order_pay')->where($where)->getField('order_code');
        if(!$order_code)
        {
            $this->redirect('Index/index');
            exit;
        }
        //查询出这次支付的订单
        $order_list=M('lottery_attend')->where('order_number='.$order_code.' and more_money > 0')->select();
        $list_count=count($order_list);
        setcookie('cart',null,time()+24*3600,'/');      //清空购物车
        $this->assign('list_count',$list_count);
        $this->assign('order_list',$order_list);
        $this->display('pay_success');
    }

    //失败回调
    public function pay_error(){
        $this->meta_title='购买失败';
        $goods_data = D('Goods')->getGoodsList();
        $this->assign('goods_data',$goods_data);

        $this->display('pay_error');
    }



    /*
 * 购买成功   更新记录
 * $pay_type  支付方式  1 线下 线上
 * $orders   支付订单
 * $orders=array(0=>array('aid=1'))
 *
 */

    public function purchase_success($pay_type,$orders,$arr_pay=null)
    {

        //用户id
        $user_id=is_login();
        if(!$user_id)
        {
            $this->redirect('User/login');
            exit;
        }

        foreach($orders as $row)
        {
            $time=time();
            $lottery_attendObj = M('lottery_attend');
            $order_Info =$lottery_attendObj->where('id='.$row)->find();


            $lpInfo =  M('lottery_product')->where('lottery_id='.$order_Info['lottery_id'])->find();

            $key='code_'.$lpInfo['pid'].'_'.$lpInfo['lottery_id'];

            $lottery_attendObj->startTrans();

            //如果是线上支付
            if($arr_pay)
            {
                //插入线上记录支付表
                $more = $arr_pay['realReceive']/$lpInfo['single_price'] ;     //总的购买次数
                $extra=$lpInfo['need_count'] -$lpInfo['attend_count'] - $more ; //多出的购买次数
                $rs= M('jubao_pay')->add($arr_pay);
                $actual_payment =$arr_pay['realReceive'];    //实际支付金额

            }
            else
            {
                $more = $order_Info['attend_count'];
                $extra=$lpInfo['need_count'] -$lpInfo['attend_count'] - $order_Info['attend_count'] ;
                $rs=1;
                $actual_payment =$order_Info['order_amount'];  //实际支付金额
            }

            $scale = floor(($more+$lpInfo['attend_count'])/$lpInfo['need_count']*100000);   //百分比
            //剩余
            //判断开奖

            if($extra > 0)    //不需要开启下一期
            {
                $lucky_code=$this->rand_getcode($more,$key);

                 if($lucky_code['locky_code']=='')
                 {
                     return 0;
                 }
                $la_update=array
                (
                    'is_pay'=>1,             //支付成功
                    'actual_payment'=>$actual_payment,  //实际支付金额
                    'lucky_code'=>$lucky_code['locky_code'],             //购买的幸运码
                    'pay_type'=>$pay_type,                        //支付方式
                    'pay_time'=>$arr_pay['modifyTime']?$arr_pay['modifyTime']:$time,
                    'attend_count'=>$more,
                );

                $rs1= $lottery_attendObj->where('id='.$row)->save($la_update);

                //减少库存
                $lp_update=array(
                    'attend_count'=>array('exp','attend_count+'.$more),
                    'attend_ratio'=>$scale,
                    'remain_count'=>$lpInfo['remain_count']-$more,        //剩余人次
                );
                $rs2=  M('lottery_product')->where('lottery_id='.$order_Info['lottery_id'])->save($lp_update);

                if($rs && $rs1 && $rs2)
                {
                    $lottery_attendObj->commit();
                }
                else
                {
                    $lottery_attendObj->rollback();
                    return 0;

                }
            }
            else             //需要开启下一期
            {

                $ml_extra=$lpInfo['need_count'] -$lpInfo['attend_count'];    //剩余的个数

                $lucky_code=$this->rand_getcode($ml_extra,$key);  //获取开奖码
                if($lucky_code['locky_code']=='')
                {
                    return 0;
                }
                $realReceive =($ml_extra)*$lpInfo['single_price'];   //购买本次购买所有剩余开奖码价格
                $more_money=$actual_payment-$realReceive;     //超出的金额

                $la_update=array(
                    'is_pay'=>1,                        //支付成功
                    'actual_payment'=>$realReceive    ,  //实际支付金额
                    'lucky_code'=>$lucky_code['locky_code'],             //购买的幸运码
                    'pay_type'=>$pay_type,                        //支付方式
                    'pay_time'=>$arr_pay['modifyTime']?$arr_pay['modifyTime']:$time,
                    'attend_count'=>$ml_extra,             //实际购买的幸运码总数
                    'more_money'=>$more_money              //剩余金额
                );

                $rs1= $lottery_attendObj->where('id='.$row)->save($la_update);

                //减少库存
                $lp_update=array(
                    'attend_count'=>$order_Info['need_count'],
                    'attend_ratio'=>10000000,          //百分比
                    'remain_count'=>0         //剩余人次
                );
                $rs2=  M('lottery_product')->where('lottery_id='.$order_Info['lottery_id'])->save($lp_update);

                 if($more_money)
                 {
                 $uid=$arr_pay['payerName']?$arr_pay['payerName']:$user_id;
                 $rs3= M('member')->where('uid='.$uid)->setInc('account',$more_money);
                 }
                else
                {
                 $rs3=true;
                }


                if($rs1 && $rs2 && $rs3)
                {
                    $lottery_attendObj->commit();
                    //执行开奖
                    A('Goods')->lettery_way($order_Info['lottery_id']);
                }
                else
                {
                    $lottery_attendObj->rollback();
                    return 0;

                }
            }

        }
        return 1;


    }



      /*
       * 取出lucky_code 固定数量的开奖码
       */
    public function rand_getcode($num,$key)
    {
        $level=11;
        $redis = new Think\RedisContent($level);
        $data=array(
            'code'=>$key,
            'num'=>$num
        );
        $arr=$redis->easygetcode($data);   //取出code

        return $arr;
    }

public function cs(){
    $d=microtime();
   $c= substr(microtime(), 2, 3);
    echo $d;
    echo '<br>';
    echo $c;
}

}