<?php
/**
 * SuJun (https://github.com/351699382)
 *
 * @link      https://github.com/351699382
 * @copyright Copyright (c) 2016 SuJun
 * @license   http://www.apache.org/licenses/LICENSE-2.0.txt (Apache License)
 */

namespace BigFile\Process;

interface ProcessInterface
{

    /**
     * 任务处理
     * [worker description]
     * @param  array  $param [description]
     * @return [type]        [description]
     */
    public function worker(array $param);

}
