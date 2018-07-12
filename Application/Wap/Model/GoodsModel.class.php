<?php
// +----------------------------------------------------------------------
// | OneThink [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013 http://www.onethink.cn All rights reserved.
// +----------------------------------------------------------------------
// | Author: 麦当苗儿 <zuojiazi@vip.qq.com> <http://www.zjzit.cn>
// +----------------------------------------------------------------------

namespace Wap\Model;
use Think\Model;

/**
 * 产品模型
 */
class GoodsModel extends Model{
    
    protected $tableName = 'lottery_product';
    
    /**
     * 获取商品列表
     * @param  milit   $id 分类ID或标识
     * @param  boolean $field 查询字段
     * @return array     分类信息
     * @author 麦当苗儿 <zuojiazi@vip.qq.com>p
     */
    public function getGoodsListback($index = 1,$pagesize = 10,$params = array()){

        $condition = array();
        $condition[] = 'lottery_time = 0';
        $condition[] = 'is_delete = 0';
        
        $cat_id = intval($params['cat_id']);
        if($cat_id > 0){
            $condition[] = 'cat_id = '.$cat_id;        
        }

        if(strlen($params['keyword']) > 0){
            $condition[] = " title REGEXP '".$params['keyword']."'";    
        }
        $order_str = '';
        if(intval($params['is_hot']) == 1){
            $order_str = ' ORDER BY hot_sort desc';    
        }elseif(intval($params['is_new']) == 1){
            $order_str = ' ORDER BY new_sort desc';
        }elseif(intval($params['need_sort']) == 1){
            $order_str = ' ORDER BY need_count desc';       
        }elseif(intval($params['left_sort']) == 1){
            $order_str = ' ORDER BY left_count desc';       
        }
            
        /*
        $sort_config = array('DESC','ASC');
        $order_str = '';
        if(isset($params['sort_need'])){
            $order_str = 'need_count '.$sort_config[intval($params['sort_need'])];            
        }elseif(isset($params['sort_left'])){
            //$order_str = 'need_count '.$sort_config[intval($params['sort_left'])];    
        }elseif(isset($params['sort_open'])){
            //$order_str = 'need_count '.$sort_config[intval($params[''])];    
        }else{
            $order_str = 'id '.$sort_config[0];    
        }
        */
        $where = '';
        if(count($condition) > 0){
            $where = ' WHERE '.implode(' AND ',$condition);
        }
        $sql = 'SELECT *,need_count-attend_count AS left_count FROM '.$this->trueTableName.$where.$order_str.' LIMIT '.($index-1)*$pagesize.','.$pagesize;
        
        //$list = $this->where($condition)->order($order_str)->limit(($index-1)*$pagesize,$pagesize)->select();
        $list = $this->query($sql);
        $total_count = $this->where($condition)->count();
        return $data = array('current_page'=>$index,'page_count' => ceil($total_count / $pagesize),'total_count' => $total_count, 'list' => is_array($list) && count($list) > 0 ? $list : array());
        
    }

    /**
     * @param int $page
     * @param int $pagesize
     * @param array $params
     * @return array
     */
    public function getGoodsList($page = 1,$pagesize = 10,$params = array()){
        $field='lottery_id,title,lottery_pid,need_count,attend_count,single_price,thumb,f_title,userinfo';
        $condition = array();
        $condition['expecttime']=0;
        $condition['is_delete'] = 0;

        $catid = $params['catid'];
        if($catid > 0){
            $condition['cat_id'] =$catid;
        }
        switch($params['field']){
            case 'hot':
              //  $order='attend_ratio desc';
                $order='remain_count asc';
                break;
            case 'new':
                $order='id desc';
                break;
            case 'total':
                $order='need_count '.$params['order'];
                break;
            default:
                $order='attend_ratio desc';
                break;
        }
       $count=$this->where($condition)->count();
       $data= $this->where($condition)->page($page,$pagesize)->field($field)->order($order)->select();
        if(!$data){
            $data=array();
        }
        else
        {
            foreach($data as &$row)
            {
                $row['thumb']=getfullImg($row['thumb']);
            }
        }
        return array('total'=>$count,'data'=>$data);

    }

    /**
     * @param int $page
     * @param $pagesize
     * @param array $params
     */
    public function getNewest($page=1,$pagesize,$params=array()){
        $field='lottery_id,title,lottery_pid,thumb,f_title,uid,lottery_code,expecttime,status,userinfo';
        $condition = array();
        $condition['expecttime']=array('gt','0');
        $condition['is_delete'] = 0;
        $order="expecttime desc";

        $data= $this->where($condition)->page($page,$pagesize)->field($field)->order($order)->select();
        if(!$data){
            $data=array();
        }
        $count=$this->where($condition)->count();
        return array('total'=>$count,'data'=>$data);

    }




}
