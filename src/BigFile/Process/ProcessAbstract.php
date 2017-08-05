<?php
/**
 * SuJun (https://github.com/351699382)
 *
 * @link      https://github.com/351699382
 * @copyright Copyright (c) 2016 SuJun
 * @license   http://www.apache.org/licenses/LICENSE-2.0.txt (Apache License)
 */

namespace BigFile\Process;

abstract class ProcessAbstract
{

    abstract public function __construct($worker);
    /**
     * 单进程方式实现
     *
     * @param $api
     * @param string $param
     * @param string $method
     * @param bool $multi
     * @return mixed
     */
    abstract protected function run($data, $processNum);

}
