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

class Sorting extends BigFileAbstract implements BigFileInterface
{
    /**
     * 分割数
     * @var integer
     */
    private $splitNum = 100000;

    /**
     * 缓存写入数
     * @var integer
     */
    private $writeNum = 100000;

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
        if (isset($config['sortFunc']) && gettype($config['sortFunc']) != 'object') {
            throw new \Exception("请设置hashFunc哈希函数", 500);
        }

        //设置默认值
        $config = array_merge([
            'maxLine' => 100000,
            'saveFileName' => empty($config['saveFileName']) ? '_' . basename($config['filePath']) : trim($config['saveFileName']),
            'isProcess' => false, //默认关
            'isRemoveFirst' => false,
            'processNum' => 2,
            'column' => 0,
            'isHash' => false,
        ], $config);

        $config['savePath'] = rtrim($config['savePath'], '/') . '/';
        $this->config = $config;

        //创建目录及文件名
        !is_dir($this->config['savePath']) && mkdir($this->config['savePath'], 0755, true);
    }

    /**
     * 执行
     */
    public function execute()
    {
        @ini_set('memory_limit', '-1');
        set_time_limit(0);
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
        //分割并排序
        $this->config['middleFunc'] = function ($data) {
            usort($data, $this->config['sortFunc']);
            return $data;
        };
        $s = new Spliting($this->config);
        $files = $s->execute()->getSplitFileInfo();
        $num = count($files);
        while ($num > 1) {
            $avg = floor($num / 2);
            for ($i = 0; $i < $avg; $i++) {
                $this->arrayMerge($files[$i], $files[($i + $avg)], $this->config['sortFunc']);
                //删除文件
                @unlink($files[$i]);
                @unlink($files[($i + $avg)]);
            }
            $files = $this->tree($this->config['savePath']);
            $num = count($files);
        }
    }
    
    /**
     * 合并数据
     * @param  [type] $arrPathA [description]
     * @param  [type] $arrPathB [description]
     * @param  [type] $callback [description]
     * @return [type]           [description]
     */
    protected function arrayMerge($arrPathA, $arrPathB, callable $callback)
    {
        //设置两个起始位置标记
        $aI = $bI = 0; 

        //文件长度
        $obj = $this->getFileObj();
        $aLen = $this->maxRow = $obj->getCount($arrPathA);
        $bLen = $this->maxRow = $obj->getCount($arrPathB);

        //数据
        $arrA = $arrB = [];
        //取出头
        $startA = $startB = 0;
        //合并生成的主文件
        $mainFile = $this->config['savePath'] . 'main' . (md5(time()) . mt_rand(0,9999)) . $this->config['saveFileName'];

        //数组A剩除数组
        $arrATempFile = $this->config['savePath'] . '_temp_a_' . (md5(time())) . $this->config['saveFileName'];
        $arrBTempFile = $this->config['savePath'] . '_temp_b_' . (md5(time())) . $this->config['saveFileName'];
        $arrAFlag = $arrBFlag = false;

        while ($aI < $aLen && $bI < $bLen) {

            if (!isset($arrA[$aI])) {
                //判断是否大文件、分批取出
                if ($aLen > $this->splitNum) {

                    /*                   //把旧数据出栈
                    if(count($arrA) > $this->writeNum) {
                    $tempArray = [];
                    foreach ($arrA as $key => $value) {
                    $tempArray[] = $value;
                    unset($arrA[$key]);
                    }
                    $obj->batchWrite($arrATempFile,$tempArray);
                    $arrAFlag = true;
                    unset($tempArray);
                    }
                     */

                    $start = $startA * $this->splitNum;
                    $data = $obj->getLimitData($arrPathA, $start, $this->splitNum);
                    array_map(function ($v) use (&$arrA) {
                        array_push($arrA, $v);
                    }, $data);

                    unset($data);
                } else {
                    $arrA = $obj->getAllData($arrPathA);
                }
                $startA++;
            }

            if (!isset($arrB[$bI])) {
                //判断是否大文件、分批取出
                if ($bLen > $this->splitNum) {

                    /*
                    //把旧数据出栈
                    if(count($arrB) > $this->writeNum) {
                    $tempArray = [];
                    foreach ($arrB as $key => $value) {
                    $tempArray[] = $value;
                    unset($arrB[$key]);
                    }
                    $obj->batchWrite($arrBTempFile,$tempArray);
                    $arrBFlag = true;
                    unset($tempArray);
                    }
                     */

                    $start = $startB * $this->splitNum;
                    $data = $obj->getLimitData($arrPathB, $start, $this->splitNum);
                    array_map(function ($v) use (&$arrB) {
                        array_push($arrB, $v);
                    }, $data);

                } else {
                    $arrB = $obj->getAllData($arrPathB);
                }

                $startB++;
            }

            //当数组A和数组B都没有越界时
            $flag = $this->config['sortFunc']($arrA[$aI], $arrB[$bI]);

            if ($flag < 0) {
                $arrC[] = $arrA[$aI++];
                unset($arrA[($aI - 1)]);
            } else {
                $arrC[] = $arrB[$bI++];
                unset($arrB[($bI - 1)]);
            }

            //判断数据是否过多，写入文件
            if (isset($arrC[$this->writeNum])) {
                $obj->batchWrite($mainFile, $arrC);
                $arrC = [];
            }
        }

        //把$arrC剩余部份写进去
        if (!empty($arrC)) {
            $obj->batchWrite($mainFile, $arrC);
            $arrC = [];
        }

        /*       //把存时临时数据的加回去,这里可以分批加回
        if($arrAFlag){
        $temp = $obj->getAllData($arrATempFile);
        $obj->batchWrite($mainFile,$temp);
        @unlink($arrATempFile);
        }

        if($arrBFlag){
        $temp = $obj->getAllData($arrBTempFile);
        $obj->batchWrite($mainFile,$temp);
        @unlink($arrBTempFile);
        }
         */

        //判断 数组A内的元素是否都用完了，没有的话将其全部插入到C数组内：
        while ($aI < $aLen) {
            $t = isset($arrA[$aI++]) ? $arrA[($aI - 1)] : '';
            if (!empty($t)) {
                $arrC[] = $t;
            }
        }
        !empty($arrC) && $obj->batchWrite($mainFile, $arrC);
        $arrC = [];

        //判断 数组B内的元素是否都用完了，没有的话将其全部插入到C数组内：
        while ($bI < $bLen) {
            $t1 = isset($arrB[$bI++]) ? $arrB[($bI - 1)] : '';
            if (!empty($t1)) {
                $arrC[] = $t1;
            }
        }
        !empty($arrC) && $obj->batchWrite($mainFile, $arrC);
        $arrC = [];

        //如果没取完则把剩余取完
        if (($startA * $this->splitNum) < $aLen) {
            while (($startA * $this->splitNum) < $aLen) {
                $start = $startA * $this->splitNum;
                $data = $obj->getLimitData($arrPathA, $start, $this->splitNum);
                $obj->batchWrite($mainFile, $data);
                $startA++;

                unset($data);
            }
        }
        if (($startB * $this->splitNum) < $bLen) {
            while (($startB * $this->splitNum) < $bLen) {
                $start = $startB * $this->splitNum;
                $data = $obj->getLimitData($arrPathB, $start, $this->splitNum);
                $obj->batchWrite($mainFile, $data);
                $startB++;
                unset($data);
            }
        }

    }

}
