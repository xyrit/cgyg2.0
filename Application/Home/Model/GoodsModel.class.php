<?php
// +----------------------------------------------------------------------
// | OneThink [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013 http://www.onethink.cn All rights reserved.
// +----------------------------------------------------------------------
// | Author: 麦当苗儿 <zuojiazi@vip.qq.com> <http://www.zjzit.cn>
// +----------------------------------------------------------------------

namespace Home\Model;
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
     * @author 麦当苗儿 <zuojiazi@vip.qq.com>
     */
    public function getGoodsList($index,$pagesize,$params = array()){

        $condition = array();
        $condition['lottery_time'] = 0;
        $condition['is_delete'] = 0;
        
        $cat_id = intval($params['cat_id']);
        if($cat_id > 0){
            $condition['cat_id'] = $cat_id;        
        }

        if(strlen($params['keyword']) > 0){
            $condition['title'] = array('like','%'.$params['keyword'].'%');    
        }
        
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
        
        $list = $this->where($condition)->order($order_str)->limit(($index-1)*$pagesize,$pagesize)->select();
        $total_count = $this->where($condition)->count();
        return $data = array('page_count' => ceil($total_count / $pagesize),'total_count' => $total_count, 'list' => is_array($list) && count($list) > 0 ? $list : array());
        
    }
    
    
    

   
}
