<?php

/*
 *  商品类
 * @2016年4月12日 15:30:01
 */

namespace V1\Controller;
use Common\Controller\ApiController;
use Think\Controller;

class GoodsController extends ApiController
{

    //商品详情
    public function detail()
    {

        $lottery_id=$_GET['lottery_id'];
        $this->meta_title='商品详情';

        $user_id=is_login();

        if (!$lottery_id) {
            $this->error('该商品不存在', U('Index/index'));
        }
        $data=M('lottery_product')->where('lottery_id='.$lottery_id)->find();
        $data['proportion'] = $data['attend_count'] /$data['need_count'] *100;
        //查询分类名称        $data['cat_name'] =M('category')->where('id='.$data['cat_id'])->getField('title as cat_name');
        
        //登录查询购买次数
        if ($user_id) {
            $arr=array(
                'uid'=>$user_id,
                'lottery_id'=>intval($lottery_id),
                'is_pay'=>1,
            );
            $user_count= M('lottery_attend')->where($arr)->sum('attend_count');  //参与总数
            if($user_count > 0){
                $user_in_list= M('lottery_attend')->Field('pay_time,lucky_code')->where($arr)->select();      // 参与记录
                foreach ($user_in_list as $key => $row) {
                   $lucky_code= explode(',',$row['lucky_code']);
    
                    foreach ($lucky_code as $row2) {
                        if ($row2 == '') {
                            break;
                        }
                        $user_in_list[$key]['codelist'][]=$row2;
                    }
                }
                $this->assign('user_id',$user_id);      //登录购买次数
                $this->assign('user_count',$user_count);      //登录购买次数
                $this->assign('user_in_list',$user_in_list);   //登录购买列表   
            }
        }
        
        $arr2=array(
            'lottery_id'=>$lottery_id,
            'is_pay'=>1,
        );
        $falst_pay_time=  M('lottery_attend')->where($arr2)->order('pay_time desc')->getField('pay_time');
        $this->assign('falst_pay_time',$falst_pay_time);
        
        $infos= M('shop_detail')->where('id='.$data['pid'])->find();

        $data['images'] = unserialize($infos['images']);
        foreach ($data['images'] as $key => $row) {

            $data['images'][$key]=getfullImg($row);
            
        }

        if($data['status']==0)            //未开奖
        {
           //查询图文详情
            $data['infos']= $infos['content'];
        }
            if($data['status']==1)            //等待开奖
            {
               $data['nowtime']=time();
                //$data['expecttime']=$data['expecttime'];
                $data['seconds']=($data['expecttime']-time())* 1000;

            }
            if($data['status']==2)               //已开奖
            {
                //查询中奖用户信息
            if ($data['uid']) {
                    $arr1=array(
                        'la.uid'=>$data['uid'],
                        'la.lottery_id'=>$data['lottery_id'],
                        'la.is_pay'=>1,
                    );
                    //查询本期参与总次数
                    $userInfo = M('lottery_attend la')->field("la.ip_address,m.uid,m.nickname,m.face,sum(la.attend_count) as ucount")->join('cg_member m on la.uid=m.uid')->where($arr1)->find();
                    $userInfo['lottery_time']=date('Y-m-d H:i:s',$data['lottery_time']);   //开奖时间
                    
                    //查询购买记录
                    $lottery_orders=M('lottery_attend la')->field('pay_time,lucky_code')->where($arr1)->select();
                foreach ($lottery_orders as $key => $row1) {
                        $lucky_code= explode(',',$row1['lucky_code']);


                    foreach ($lucky_code as $row2) {
                        if ($row2 == '') {
                                break;
                            }

                            $lottery_orders[$key]['codelist'][]=$row2;
                        }
                    }
                    $this->assign('lottery_orders',$lottery_orders);    //查询中奖者的购买记录

                    $this->assign('userInfo',$userInfo);

                }

            }

            //查询计算结果  最近50条购买记录
            // $purchase_list= M('lottery_attend la')->field('la.create_time,m.nickname,la.attend_count,la.lottery_id,doi.title')->join('cg_member m on la.uid=m.uid')->join('cg_shop doi on doi.id=la.pid')->limit(0,50)->order('la.create_time desc')->select();          
            // $purchase_time_sum =M('lottery_attend la')->limit(0,50)->sum('la.create_time');
            //  $this->assign('purchase_list',$purchase_list);    //所有商品50条购买记录
            //$this->assign('purchase_time_sum',$purchase_time_sum ? $purchase_time_sum:0 );
            //查询第一次购买时间
        $falst_time=  M('lottery_attend la')->where($arr1)->order('create_time asc')->getField('create_time');
        $falst_time= date('Y-m-d h:i:s',$falst_time);
        //查询总共购买次数
        $arr1=array(
            'la.lottery_id'=>$lottery_id,
            'la.is_pay'=>1,
        );
        $goumaicount=  M('lottery_attend la')->join('cg_member m on m.uid=la.uid')->where($arr1)->count();

        $goumaicount=ceil($goumaicount/20);

        //查询下一期的期号
        $new_lottery_id= M('lottery_product')->where('pid='.$data['pid'].' and status=0')->getField('lottery_id');
        $this->assign('new_lottery_id',$new_lottery_id?$new_lottery_id:$lottery_id);
        $this->assign('goumaicount',$goumaicount);
        $this->assign('falst_time',$falst_time);
            $this->assign('user_id',$user_id);
            $this->assign('user_count',$user_count);      //登录购买次数
            $this->assign('data',$data);
        $this->History($user_id,$data['pid']);
            $this->display();
        
    }


    //商品详情页
    public function detailinfo()
    {
        $this->meta_title='图文详情';
        $gid=I('gid');
        $gid = $gid ? $gid :'1';
        $goods_data= M('shop_detail')->where('id='.$gid)->find();
        if($goods_data==null){
            $this->apiError(405,'返回失败');
            exit;
        }
        $goods_data['images'] = unserialize($goods_data['images']);//处理 图片 转成数组
        $goods_data=$goods_data?$goods_data:array();
        $this->apiSuccess('返回成功',array('items'=>($goods_data)));
    }


    //图片轮播
    public function carousel(){
        $slide_list = D('Wap/Slide')->get_slide();
        $slide=array();
       foreach($slide_list as $k=>$v){
            $slide[$k]['id'] = $v['id'];//轮播id
            $slide[$k]['url'] = $v['url'];//地址
            $slide[$k]['img'] = get_cover($v['icon'],'path');;//轮播图片地址

        }
        $this->apiSuccess('返回成功',array('items'=>$slide));
    }


    /*
    *  首页菜单列表
    */
    public function menu_list(){
        $goods_data=  array(
            array('name'=>'10元专区','url'=>'javascript:;','icon'=>'null','checked_icon'=>null),
            array('name'=>'限购专区','url'=>'javascript:;','icon'=>'null','checked_icon'=>null),
            array('name'=>'晒单分享','url'=>'javascript:;','icon'=>'null','checked_icon'=>null),
            array('name'=>'常见问题','url'=>'javascript:;','icon'=>'null','checked_icon'=>null)
        );
        $this->apiSuccess('返回成功',array('items'=>($goods_data)));
    }

    /*
    * 底部菜单
    */
    public function footer_nav(){
        $goods_data=  array(
            array('name'=>'首页','url'=>'javascript:;','icon'=>'null'),
            array('name'=>'所有商品','url'=>'javascript:;','icon'=>'null'),
            array('name'=>'发现','url'=>'javascript:;','icon'=>'null'),
            array('name'=>'购物车','url'=>'javascript:;','icon'=>'null'),
            array('name'=>'我的','url'=>'javascript:;','icon'=>'null')
        );
        $this->apiSuccess('返回成功',array('items'=>($goods_data)));
    }

    /*
    *   推荐商品列表
     *  page  页数
     *  number 数量
    */
    public function lists_recom(){
        $goods_data = D('Goods')->getGoodsList($this->page,$this->number);
        foreach($goods_data['data'] as &$v){
            $v['thumb']=getfullImg($v['thumb']);
        }
        $goods_data=$goods_data['data']?$goods_data['data']:array();
        $this->apiSuccess('返回成功',array('items'=>($goods_data)));
    }

  /*
   * 获取所有商品分类
   * */

    public  function category(){
        $category_tree = S('category_tree');//查询所有分类
        if(!$category_tree){
            $category_tree = D('category')->getCategory();
            S('category_tree',$category_tree);
        }
        $category = [];
        foreach($category_tree as $v){
            $category['id']    = $v['id'];//分类id
            $category['title'] = $v['title'];//分类名称
            $category['icon']  = $v['icon'];//分类图标
            $category['checked_icon'] = $v['checked_icon'];//选中分类图标
            $category['status'] = $v['status'];//状态 0 代表可用 1代表失效
        }
        $this->apiSuccess('返回成功',array('items'=>($category)));
    }


    /* 商品排序 筛选 列表
    * catid   商品分类 id
    *  最热   最新    剩余人次      总需人次
    *  hot   new    need_count
    */
    public function salelist(){
        $param['catid']=I('get.catid','0','intval');
        $param['order']=I('get.order','desc');//asc
        $param['field']=I('get.field','hot');
        $goods_data = D('Goods')->getGoodsList($this->page,$this->number,$param);
        foreach($goods_data['data'] as &$v){
            $v['thumb']=getfullImg($v['thumb']);
        }
        $goods_data=$goods_data['data']?$goods_data['data']:array();
        $this->apiSuccess('返回成功',array('items'=>($goods_data)));
    }
    
    public function lists()
    {
        $category_tree = S('category_tree');
        if(!$category_tree){
            $category_tree = D('category')->getCategory();
            S('category_tree',$category_tree);
        }
        $this->assign('category_tree',$category_tree);
        $this->meta_title='全部商品';
        $this->display();
        $this->apiSuccess('返回成功',$result);
    }

    public function list_data()
    {

        $page = intval(I('get.page')) > 0 ? intval(I('get.page')) : 1;
        $pagesize = intval(I('get.count','3'));
        $field=I('get.field','hot');
        $sort=I('get.order','desc');
        $params = array();
        $params['catid'] = intval(I('get.catid'));
        $params['order']=$sort;
        $params['field']=$field;
        $goods_data = D('Wap/goods')->getGoodsList($page,$pagesize,$params);
        if($goods_data['data']){
            foreach($goods_data['data'] as &$v){
                $v['isten']=intval($v['single_price'])==10?1:0;
                $v['thumb']=getfullImg($v['thumb']);
                unset($v['userinfo']);
            }
        }

        $result['total']=$goods_data['total'];
        $result['list']['ghs']=$goods_data['data'];
        $result['list']['data']=array('field'=>$field,'order'=>$sort);

        $this->apiSuccess('返回成功',$result);


    }


    //购买记录分页
    public function purchase_records()
    {
        $this->meta_title='购买记录';
        $pageIndex = (I('page',1)-1)*20;
        $count=I('count',10);
        $lottery_id = I('lottery_id');
        $type = I('type');
        $arr1=array(
            'la.lottery_id'=>$lottery_id,
            'la.is_pay'=>1,
        );
       $list=  M('lottery_attend la')->field('la.id,la.create_date_time,m.face,m.nickname,m.uid,la.attend_count,la.ip_address')->join('cg_member m on m.uid=la.uid')->where($arr1)->limit($pageIndex,$count)->select();
        $total=  M('lottery_attend la')->join('cg_member m on m.uid=la.uid')->where($arr1)->count();
        if ($type == 'view') {
            $this->assign('list',$list);
            $this->assign('lottery_id',$lottery_id);

            $this->display();
        } else {
            $data=array(
                'total'=>$total,
                'list'=>$list

            );
            $this->ajaxReturn($data);

        }
    }
    

    //晒单分页
    public function ShowSingleList()
    {

        $this->meta_title='晒单分享';
        $type = I('type');
        $pageIndex = (I('page',1)-1)*20;
        $count=I('count',10);
        $pid = I('pid');

        if (!$pid) {
            return false;
        }

       $display_product = M('display_product dp');
       $list = $display_product->field('m.face,m.nickname,m.uid,dp.title,dp.create_time,dp.pid,dp.lottery_id,dp.description,dp.pics')->join('left join cg_member m on m.uid=dp.uid')->limit($pageIndex,$count)->where('dp.pid='.$pid.' and dp.status=1')->select();
        $total=$display_product->join('left join cg_member m on m.uid=dp.uid')->where('dp.pid='.$pid.' and dp.status=1')->count();

        foreach ($list as &$row) {
            $arr_pic=explode(',',$row['pics']);
            $row['arr_pic']=$arr_pic;
        }
        if ($type == 'view') {
            $this->assign('list',$list);
            $this->assign('pid',$pid);
            $this->assign('count',$total);
            $this->display();
        } else {
            $data=array(
                'total'=>$total,
                'list'=>$list
            );
            $this->ajaxReturn($data);
        }

    }


    //购买完成,进入正在揭晓
    public function lettery_way($lottery_id)
    {

      $lpInfo=  M('lottery_product')->where('lottery_id='.$lottery_id)->find();

     //   if($lpInfo['need_count']==$lpInfo['attend_count'] && $lpInfo['status']==0 )
        if ($lpInfo['status'] == 0) {
            $time = time();
            $last_attend_time=date('ymdhis',$time);

            $total_time=M('lottery_attend')->where('is_pay=1 and pay_time>0')->limit(50)->order('pay_time desc')->sum('sfm_time');
            $updata=array(
                'last_attend_time'=>$time,
                'last_attend_date_time'=> $last_attend_time,
                'total_time'=>$total_time,
                'expecttime'=> \Think\Cqssc::getExpectTime(),                   //开奖倒计时
                'status'=>1
            );
            M('lottery_product')->startTrans();
            $rs1=M('lottery_product')->where('lottery_id='.$lottery_id)->save($updata);  //更新表

            $goodInfo= M('shop')->where('id='.$lpInfo['pid'])->find();

            if ($goodInfo['stocks'] > 0 && $goodInfo['max_period'] >= $lpInfo['lottery_pid']) {
                //开始新的一期
                $need_count= intval($goodInfo['marketprice']/$goodInfo['single_price']);   //计算总人次

                $new_lottery =array(
                   'pid'=>$goodInfo['id'],               //商品id
                    'lottery_pid'=>$lpInfo['lottery_pid']+1,         //单个商品的开奖期号
                    'need_count'=>$need_count,          //需要总人次(总价)
                    'remain_count'=>$need_count,          //剩余人次
                    'attend_count'=>0,      //参加人次，(该期商品已购买人次)
                    'attend_ratio'=>0,      //参加人次完成比例
                    'create_time'=>time(),   //创建时间
                    'cat_id'=>$goodInfo['cat_id'],
                    'title'=>$goodInfo['title'],
                    'description'=>$goodInfo['description'],
                    'f_title'=>$goodInfo['f_title'],
                    'position'=>$goodInfo['position'],
                    'thumb'=>$goodInfo['thumb'],
                    'marketprice'=>$goodInfo['marketprice'],
                    'single_price'=>$goodInfo['single_price'],
                    'max_period'=>$goodInfo['max_period'],
                    'purchase_frequency'=>$goodInfo['purchase_frequency'],
                    'status'=>0
                );
                $new_id= M('lottery_product')->add($new_lottery);
                $new_lettery_id=10000000+$new_id;
                $rs2= M('lottery_product')->where('id='.$new_id)->save(array('lottery_id'=>$new_lettery_id));
                //生成开奖码
                $key = 'code_' . $new_lottery['pid'] . '_' . $new_lettery_id;
                $this->set_code($key,$new_lottery['need_count']);

                if ($rs1 && $new_id && $rs2) {

                    M('lottery_product')->commit();
                } else {
                    M('lottery_product')->rollback();
                }

            }

        }
        return true;
    }


    //往期揭晓
    public function to_announce()
    {
        $this->meta_title='往期揭晓';
        $pid=I('pid');
        $type = I('type');
        $pageIndex = (I('page', 1) - 1) * 20;
        $count = I('count', 10);

        $list = M('lottery_product lp')->field('lottery_time,pid,status,lottery_id,lottery_code,uid,expecttime')->where('status >0 and pid=' . $pid)->limit($pageIndex, $count)->select();
        $total=M('lottery_product lp')->where('status >0 and pid=' . $pid)->count();
        foreach ($list as &$row) {
                if($row['status']==2)      //已开奖查出用户信息
                {
                    $arr1=array(
                        'uid'=>$row['uid'],
                        'lottery_id'=>$row['lottery_id'],
                        'is_pay'=>1,
                    );

                    $row['user'] = M('member')->field('nickname,face,uid')->where('uid='.$row['uid'])->find();
                    $row['user']['count']=M('lottery_attend')->where($arr1)->sum('attend_count');
                    $row['user']['ip_address']=M('lottery_attend')->where($arr1)->getField('ip_address');
                }
            }

        if ($type == 'view') {
            $this->assign('pid',$pid);
        $this->assign('list',$list);


        $this->display();

        }
        else{
            $data = array(
                'total' => $total,
                'list' => $list
            );
            $this->ajaxReturn($data);
        }

    }


    //计算规则

    public function CalculationRules()
    {

        $this->meta_title='计算规则';
        //查询计算结果  最近50条购买记录
        $lottery_id=I('lottery_id');

        if (!$lottery_id) {
            echo '期号不存在';
            exit;
        }

        $data=M('lottery_product')->where('lottery_id='.$lottery_id)->find();

        if (!$data) {
            echo '期号不存在';
            exit;
        }

        $purchase_list= M('lottery_attend la')->field('la.uid,la.create_date_time,la.sfm_time,m.nickname,la.attend_count,la.lottery_id,doi.title')->join('cg_member m on la.uid=m.uid')->join('cg_shop doi on doi.id=la.pid')->where('la.create_time <'.$data['lottery_time'])->limit(0,50)->order('la.create_time desc')->select();
         $purchase_time_sum =M('lottery_attend la')->where('la.create_time <'.$data['lottery_time'])->limit(0,50)->sum('la.sfm_time');
        $this->assign('data',$data);    //状态

        foreach($purchase_list as &$row)
        {
            if(strlen($row['sfm_time']) <9)
            {
                $row['sfm_time']='0'.$row['sfm_time'];
            }
        }
        $this->assign('purchase_list',$purchase_list);    //所有商品50条购买记录
        $this->assign('purchase_time_sum',$purchase_time_sum ? $purchase_time_sum:0 );
        //查询第一次购买时间

        $this->display();
    }

    public function newest_data_list(){

        $page = intval(I('get.page')) > 0 ? intval(I('get.page')) : 1;
        $pagesize = intval(I('get.count','3'));
        $type=I('get.type','');
        $params = array();
        $goods_data = D('Wap/goods')->getNewest($page,$pagesize,$params);

        foreach($goods_data['data'] as &$v){
            $v['thumb']=getfullImg($v['thumb']);
            $v['nickname']='';
            if($v['uid'] > 0){

                $v['nickname']=getUserInfo($v['uid'],'nickname');
            }
            $v['seconds']=$v['expecttime']-time();
            $userinfo=unserialize($v['userinfo']);
            $v['joinintimes']=$userinfo['attend_count']?$userinfo['attend_count']:0;
            unset($v['userinfo']);
        }
        if($type=='new'){
            $result['total']=$goods_data['total'];
        }
        $result['list']=$goods_data['data'];
        $this->apiSuccess('返回成功',$result);


    }

    public function newest()
    {
        $this->meta_title='最新揭晓';
        $this->display();
    }

    //加入历史记录
    public function History($user_id = 0, $pid)
    {

        $time = time();
        //查询历史记录

        if ($user_id) {
            //查询是否存在
            $data = M('history')->where('uid=' . $user_id . ' and gid=' . $pid)->find();
            if ($data) {
            } else {
                //加入数据库
                $arr = array(
                    'uid' => $user_id,
                    'gid' => $pid,
                    'time' => $time,
                );
                M('history')->where('uid=' . $user_id . ' and gid=' . $pid)->add($arr);
            }
        } else {
            //查询是否存在
            $data = $_COOKIE['gid_history'];
            $arr_data = json_decode($data);
            if ($arr_data) {
                if (array_key_exists($pid, $arr_data)) {
                    foreach ($data as $key => $row)  //key是商品id  $row是时间
        {
                        if ($key == $pid) {
                            $data[$key] = $time;   //更新时间
                        }
                    }
                } else {
                    $arr[$pid] = $time;
                }
            } else {
                $arr[$pid] = $time;
            }
            //加入cookie
            $arr_data[] = $arr;
            $coookie = json_encode($arr_data);
            setcookie('gid_history', $coookie, time() + 3600 * 7, '/');
        }


    }

    //发现  晒单
    public function Discover(){
        $type = I('type');
        $pageIndex = (I('page', 1) - 1) * 20;
        $count = I('count', 10);
        $display_product = M('display_product dp');
        $list = $display_product->field('dp.id,m.face,m.nickname,m.uid,dp.title,dp.create_time,dp.pid,dp.lottery_id,dp.description,dp.pics')->join('left join cg_member m on m.uid=dp.uid')->limit($pageIndex, $count)->where('dp.status=1')->select();
        $total = $display_product->join('left join cg_member m on m.uid=dp.uid')->where('dp.status=1')->count();
        foreach ($list as &$row) {
            $arr_pic = explode(',', $row['pics']);
            $row['arr_pic'] = $arr_pic;
            foreach($row['arr_pic'] as &$row)
            {
                $row=getfullImg($row);
            }
        }
        if ($type == 'view') {

            $this->display();
        } else {
            $data = array(
                'total' => $total,
                'list' => $list
            );
            $this->ajaxReturn($data);
        }

    }

    //生成code
    public function set_code($key, $count) {

        $level = 11;
        $redis = new Think\RedisContent($level);

        //生成开奖码
        for ($i = 1; $i <= $count; $i++) {
            $value = $i + 10000000;
            $redis->redis()->sAdd($key, $value);
        }
        return true;
    }


    public function shua()
    {
        $list = M('shop_detail')->select();
        foreach($list as &$row)
        {
            $img['images'] = serialize(explode(',',$row['images']));

         M('shop_detail')->where('id='.$row['id'])->save($img);
        }
    }
    
    /**
     * post 传输数据，返回多个
     */
    public function query(){
        $ids=I('post.data',array());
        if(!$ids){
            return ;
        }
        $map['lottery_id']=array('in',$ids);

        $data= M('LotteryProduct')->where($map)->field('status,userinfo,lottery_code,uid,expecttime,lottery_id')->select();
         $result=array();
        if(!$data){
            return ;
        }
        foreach($data as $v){

            if($v['status'] ==2){
                $result[$v['lottery_id']]['nickname']=getUserInfo($v['uid'],'nickname');
                $result[$v['lottery_id']]['lottery_code']=$v['lottery_code'];
                $userinfo=unserialize($v['userinfo']);
                $result[$v['lottery_id']]['joinintimes']=$userinfo['attend_count']?$userinfo['attend_count']:0;
                $result[$v['lottery_id']]['code']='1';
            }else{
                if(time()-$v['expecttime'] > 10){
                $result[$v['lottery_id']]['code']='-1';
                }
            }

        }
         $this->ajaxReturn($result);

    }

    //商品详情页开奖
    public function queryInfo()
    {
        $lettery_id=I('lettery_id',0);
        if(!$lettery_id)
        {
            dump('aaaaaaaaa');
            dump(I('lettery_id',0));exit;
        }
        $where['lottery_id']=$lettery_id;
        $data= M('LotteryProduct')->where($where)->field('status,userinfo,lottery_code,uid,expecttime,lottery_time,lottery_id')->find();
        if($data['status'] ==2) {
            $result['nickname']=getUserInfo($data['uid'],'nickname');
            $result['user_uid']=$data['uid'];
            $result['lottery_time']=date('Y-m-d H:i:s',$data['lottery_time']);
            $result['lottery_code']=$data['lottery_code'];
            $userinfo=unserialize($data['userinfo']);
            $result['joinintimes']=$userinfo['attend_count']?$userinfo['attend_count']:0;
            $result['code']='1';
            $arr=array(
                'uid'=>$data['uid'],
                'lottery_id'=>intval($data['lottery_id']),
                'is_pay'=>1,
            );
                $user_in_list= M('lottery_attend')->Field('pay_time,lucky_code')->where($arr)->select();      // 参与记录
                  $result['user_count']=  M('lottery_attend')->where($arr)->sum('attend_count');   //参与总数
                foreach ($user_in_list as $key => $row) {
                    $lucky_code= explode(',',$row['lucky_code']);

                    foreach ($lucky_code as $row2) {
                        if ($row2 == '') {
                            break;
                        }
                        $user_in_list[$key]['codelist'][]=$row2;
                    }
                }

            $result['userlist']=$user_in_list;
        }else {

            if (time() - $data['expecttime'] > 10) {
            $result['code'] = '-1';
            }

        }
        $this->ajaxReturn($result);
    }
}

