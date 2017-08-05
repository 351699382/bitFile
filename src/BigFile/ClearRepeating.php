<?php
/**
 * SuJun (https://github.com/351699382)
 *
 * @link      https://github.com/351699382
 * @copyright Copyright (c) 2016 SuJun
 * @license   http://www.apache.org/licenses/LICENSE-2.0.txt (Apache License)
 */

namespace BigFile;

use BigFile\BigFileAbstract;
use BigFile\Spliting;

class ClearRepeating extends BigFileAbstract implements BigFileInterface
{

    /**
     * [__construct description]
     * @param array $config [description]
     */
    public function __construct(array $config)
    {
        if (empty($config['filePath'])) {
            throw new \Exception("分割原文件不存在", 500);
        }

        if (empty($config['savePath'])) {
            throw new \Exception("请设置savePath保存路径", 500);
        }

        //判断是否hash及相关函数
        if (isset($config['hashFunc']) && gettype($config['hashFunc']) != 'object') {
            throw new \Exception("请设置hashFunc哈希函数", 500);
        }

        //设置默认值
        $config = array_merge([
            'maxLine' => 100000,
            'saveFileName' => empty($config['saveFileName']) ? '_' . basename($config['filePath']) : trim($config['saveFileName']),
            'isProcess' => false,
            'isHash' => false,
            'hashFunc' => function ($param, $mod) {return fmod($param[0], $mod);},
            'isRemoveFirst' => false,
            'processNum' => 2,
            'column' => 0,
            'isHash' => true,
        ], $config);

        $config['savePath'] = rtrim($config['savePath'], '/') . '/';
        $this->config = $config;

        //创建目录及文件名
        !is_dir($this->config['savePath']) && mkdir($this->config['savePath'], 0755, true);
    }

    /**
     * 执行
     * @return [type] [description]
     */
    public function execute()
    {
        $obj = $this->getFileObj($this->config['filePath']);
        $this->maxRow = $obj->getCount($this->config['filePath']);
        if ($this->maxRow <= 0) {
            return false;
        }
        $this->config['isProcess'] ? $this->multiProcess() : $this->singleProcess();
        return true;
    }

    /**
     * 单进程方式实现
     *
     * @param $api
     * @param string $param
     * @param string $method
     * @param bool $multi
     * @return mixed
     */
    protected function singleProcess()
    {
        $this->worker();
    }

    /**
     * 多进程方式实现
     *
     * @param $api
     * @param string $param
     * @param string $method
     * @param bool $multi
     * @return mixed
     */
    protected function multiProcess()
    {
        $this->worker();
    }

    /**
     * 任务
     * @return [type] [description]
     */
    private function worker()
    {
        $s = new Spliting($this->config);
        $files = $s->execute()->getSplitFileInfo();
        $setObj = false;
        $obj = null;
        foreach ($files as $k => $v) {
            if (!$setObj) {
                $obj = $this->getFileObj('');
                $setObj = true;
            }

            $clearData = $obj->getAllData($v);
            //空的话不去重
            if (empty($clearData)) {
                continue;
            }

            $clearData = array_column($clearData, null, $this->config['column']);
            $obj->batchWrite($this->config['savePath'] . $this->config['saveFileName'], $clearData);
            unset($clearData);
            @unlink($v);
        }
    }

}
