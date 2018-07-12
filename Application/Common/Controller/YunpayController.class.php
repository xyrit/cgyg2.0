<?php

namespace Wap\Controller;
use Vendor\Jubaopay;

/**
 *
 * jubaoyun
 * Author: joan
 */
class YunPayController extends HomeController {
    /* 云支付 */

        private $jubaoyun_config;

      public function __construct()
      {

        $this->jubaoyun_config=C('jubaoyun');

      }

    function https_post($url,$data = null){

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_exec($ch);

    }

    /*
        * 同步支付成功
        */
    public function result() {
        //  A("Home/Log")->setlog("聚宝云支付同步回调", __LINE__, __METHOD__);
        $config = VENDOR_PATH . 'Jubaopay/Config/jubaopay.ini';

        Vendor('Jubaopay.Jubaopay');

        $jubaopay = new Jubaopay\Jubaopay($config);

        $message = $_GET["message"];
        $signature = $_GET["signature"];

        $jubaopay->decrypt($message);
        // 校验签名，然后进行业务处理
        $result = $jubaopay->verify($signature);

        //

        if ($result == 1) {            // 校验签名
            $payid =  $jubaopay->getEncrypt("payid"); //订单号
            $orderNo = $jubaopay->getEncrypt("orderNo"); //支付流水号
            $partnerid =  $jubaopay->getEncrypt("partnerid"); //商户号
            $amount = $jubaopay->getEncrypt("amount"); //订单金额
            $realReceive = $jubaopay->getEncrypt("realReceive"); //实际到帐金额
            $state = $jubaopay->getEncrypt("state"); //支付状态
            $modifyTime = $jubaopay->getEncrypt("modifyTime"); //支付时间
            $payerName = $jubaopay->getEncrypt("payerName"); //用户id
            $remark = $jubaopay->getEncrypt("remark"); //商品备注
            $payMethodType = $jubaopay->getEncrypt("payMethodType"); //支付类型
            $payMethod = $jubaopay->getEncrypt("payMethod"); //支付方式

            $arr_pay = array(
                'payid'=>$payid,
                'orderNo'=>$orderNo,
                'partnerid'=>$partnerid,
                'amount'=>$amount,
                'realReceive'=>$realReceive,
                'state'=>$state,
                'modifyTime'=>$modifyTime,
                'payerName'=>$payerName,
                'remark'=>$remark,
                'payMethodType'=>$payMethodType,
                'payMethod'=>$payMethod

            );

            if ($state == '2'){                  //判断第三方回调是否正确
                //回调 验证

                //查询出订单号
                $payInfo=M('order_pay')->where("pay_code='$payid' and status=0")->find();
                if($payInfo)
                {

                    if($payInfo['status']==1)     //如果订单已经支付
                    {
                        echo 'success';
                        exit;
                    }


                    if($payInfo['count_money']==$amount)      //对比订单价格是否相同
                    {


                        //根据订单号查询出商品

                        $list= M('lottery_attend')->where('order_number='.$payInfo['order_code'])->select();

                        $orderids=array();
                        foreach($list as $row)
                        {
                            $orderids[]=$row['id'];
                            //统计订单总额

                        }

                        $rs= A('Home/Shop')->purchase_success(2,$orderids,$arr_pay);

                        if($rs >0)
                        {


                            //更新支付表
                            $update_pay = array(
                                'status'=>1,
                                'pay_time'=>time(),
                                'pay_name'=>$payMethod,
                                'total_amount'=>$payInfo['count_money']+$payInfo['cg_money']
                            );

                            $rs2= M('order_pay')->where(array('pay_code'=>$payInfo['pay_code']))->save($update_pay);      //更新购买记录

                            if($rs2)
                            {
                                echo 'success';
                                exit;
                            }

                        }

                    }


                }

            }

        }
        echo "verify failed";
    }


}
