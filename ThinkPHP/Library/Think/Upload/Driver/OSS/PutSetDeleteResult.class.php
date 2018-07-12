<?php

namespace Think\Upload\Driver\OSS;


/**
 * Class PutSetDeleteResult
 * @package OSS\Result
 */
class PutSetDeleteResult extends Result
{
    /**
     * @return null
     */
    protected function parseDataFromResponse()
    {
        $info=$this->rawResponse->header['_info'];
        return  $info;
    }
}