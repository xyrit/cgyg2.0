<?php

namespace Wap\Model;

use Think\Model;

/**
 * 第三方用户
 *
 * @author joan
 */
class ThirdMemberModel extends Model {
    
    protected $_validate = array(     
        array('uid', 'require', '用户ID不能为空', self::MUST_VALIDATE, 'regex', self::MODEL_BOTH),
        array('open_id', 'require', 'open_id不能为空', self::MUST_VALIDATE, 'regex', self::MODEL_BOTH),
        array('type', 'require', '第三方类型不能为空', self::MUST_VALIDATE, 'regex', self::MODEL_BOTH),	
    );

    protected $_auto = array(
        array('add_time', NOW_TIME, self::MODEL_INSERT),    
    );
    

    
       
    
    
    
}
