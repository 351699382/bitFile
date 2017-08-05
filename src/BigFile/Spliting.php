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
use BigFile\Process\PcntlForkProcess;
use BigFile\Process\ProcessInterface;

/**
 * 分割
 */
class Spliting extends BigFileAbstract implements BigFileInterface, ProcessInterface
{

    /**
     * 当前文件最大行数
     * @var [type]
     */
    private $maxRow;

    /**
     * 验证文件,多进程情况避免系统内存不足杀掉子进程(后期需要加多守护进程方式创建进程)
     * @var [type]
     */
    private $verifyFile;

    /**
     * 获取条数
     * @var [type]
     */
    private $processGetNum = 100000;

    /**
     * [$avg description]
     * @var [type]
     */
    private $avg;

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
            'saveFileName' => empty($config['saveFileName']) ? '_' . basename($config['filePath']) : '_' . trim($config['saveFileName']),
            'isProcess' => false, //默认关
            'isHash' => false,
            'hashFunc' => function ($param, $mod) {return fmod($param[0], $mod);},
            'isRemoveFirst' => false,
            'processNum' => 2,
        ], $config);
        $config['savePath'] = rtrim($config['savePath'], '/') . '/';
        $this->config = $config;

        //创建目录及文件名
        !is_dir($this->config['savePath']) && mkdir($this->config['savePath'], 0755, true);

    }

    /**
     * 执行
     * [execute description]
     * @return [type] [description]
     */
    public function execute()
    {
        $obj = $this->getFileObj($this->config['filePath']);
        $this->maxRow = $obj->getCount($this->config['filePath']);

        if ($this->maxRow <= 0) {
            return false;
        }

        //拆分多少个文件
        $this->avg = ceil($this->maxRow / $this->config['maxLine']);

        //$this->isRunState && $this->runStateObject->start();

        $this->config['isProcess'] ? $this->multiProcess() : $this->singleProcess();

        //记录日志
        //$this->isRunState && $this->writeLog();
        return $this;
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
        //收集运行亻息
        //$this->isRunState && $this->runStateObject->getSysTopCmd();
        $obj = $this->getFileObj($this->config['filePath']);

        for ($i = 0; $i < $this->avg; $i++) {
            //开始行
            $start = $i * $this->config['maxLine'];
            $data = $obj->getLimitData($this->config['filePath'], $start, $this->config['maxLine']);
            //去除首行
            if ($this->config['isRemoveFirst'] && $i == 0) {
                array_shift($data);
            }

            //是否需要其它处理
            if (isset($this->config['middleFunc']) && gettype($this->config['middleFunc']) == 'object') {
                $data = $this->config['middleFunc']($data);
            }

            if ($this->config['isHash']) {
                $data = $this->arrayGroup($data, $this->avg);
                foreach ($data as $key => &$value) {
                    $obj->batchWrite($this->config['savePath'] . $key . $this->config['saveFileName'], $value);
                    unset($value);
                }
                unset($data);
            } else {
                $obj->batchWrite($this->config['savePath'] . $i . $this->config['saveFileName'], $data);
            }
        }
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
        //收集运行亻息
        //$this->isRunState && $this->runStateObject->getSysTopCmd();
        $this->verifyFile = tempnam(sys_get_temp_dir(), 'dbg');
        
        $processObject = new PcntlForkProcess($this);

        //拆分好平均数据
        $data = $this->maxSumSplit($this->maxRow, $this->config['processNum']);
        if (!empty($data)) {
            $processObject->run($data, $this->config['processNum']);
        }

        $str = file_get_contents($this->verifyFile);
        if (strlen($str) != $this->config['processNum']) {
            throw new \Exception("子进程中途退出。", 500);
        }
        register_shutdown_function('unlink', $this->verifyFile);
    }

    /**
     * 分割最大数
     * @param  [type] $maxSum [description]
     * @param  [type] $split  [description]
     * @return [type]         [description]
     */
    private function maxSumSplit($maxSum, $split)
    {
        if ($maxSum <= 0) {
            return [];
        }
        if (!function_exists("pcntl_fork")) {
            throw new \Exception("多进程方式需要PHP开启pcntl扩展", 500);
        }

        $avg = floor(($maxSum / $split));
        $avg = $avg ? $avg : ceil(($maxSum / $split));
        $data = [];
        for ($i = 0; $i < $split; $i++) {
            $min = ($i * $avg);
            $max = ($avg * ($i + 1));
            if ($max > $maxSum) {
                if ($data[$i - 1][1] != $maxSum) {
                    $data[$i - 1][1] = $maxSum;
                }
                break;
            }
            $data[$i] = [$min, $max];
            if ($i == ($split - 1)) {
                $data[$i][1] = $maxSum;
            }
        }
        return $data;
    }

    /**
     * 子进程处理
     * @param  [type] $data [description]
     * @return [type]       [description]
     */
    public function worker(array $param)
    {
        $obj = $this->getFileObj($this->config['filePath']);
        //如果取出总数大于每次取出数则循环
        $total = $param[1] - $param[0];
        //求出需要循环的次数
        $num = ceil($total / $this->processGetNum);
        for ($i = 0; $i < $num; $i++) {
            $start = $param[0] + ($i * $this->processGetNum);
            $limit = ($param[1] - $start) > $this->processGetNum ? $this->processGetNum : ($param[1] - $start);
            //最后一次循环
            if ($i == ($num - 1)) {
                $limit = $param[1] - $start;
            }
            $data = $obj->getLimitData($this->config['filePath'], $start, $limit);
            //去除首行
            if ($this->config['isRemoveFirst'] && $start == 0) {
                array_shift($data);
            }
            //是否需要其它处理
            if (isset($config['middleFunc']) && gettype($config['middleFunc']) == 'object') {
                $data = $this->config['middleFunc']($data);
            }
            if ($this->config['isHash']) {
                $data = $this->arrayGroup($data, $this->avg);
                foreach ($data as $key => &$value) {
                    $obj->batchWrite($this->config['savePath'] . $key . $this->config['saveFileName'], $value);
                    unset($value);
                }
                unset($data);
            } else {
                $obj->batchWrite($this->config['savePath'] . $i . $this->config['saveFileName'], $data);
            }
        }
        file_put_contents($this->verifyFile, 1, FILE_APPEND | LOCK_EX);
    }

    /**
     * 收集分割文件后的信息
     * @return [type] [description]
     */
    public function getSplitFileInfo($isRow = false)
    {
        $files = [];
        $queue = array($this->config['savePath']);
        while ($data = each($queue)) {
            $path = $data['value'];
            if (is_dir($path) && $handle = opendir($path)) {
                while ($file = readdir($handle)) {
                    if ($file == '.' || $file == '..' || $file == 'info.txt') {
                        continue;
                    }
                    $realPath = $path . $file;
                    //取得最大行数
                    if ($isRow) {
                        $temp = new \SplFileObject($realPath, 'rb');
                        $temp->seek(filesize($realPath));
                        $num = $temp->key();
                    } else {
                        $num = 0;
                    }
                    $files[] = !$isRow ? $realPath : [
                        'filePath' => $realPath,
                        'sizeCount' => $num,
                    ];
                    if (is_dir($realPath)) {
                        $queue[] = $realPath;
                    }
                }
            }
            closedir($handle);
        }
        return $files;
    }

    /**
     * 数组分组
     * @param  [type] $data [description]
     * @return [type]       [description]
     */
    private function arrayGroup(&$data, $avg)
    {
        $temp = [];
        foreach ($data as &$v) {
            $fmod = $this->config['hashFunc']($v, $avg);
            $temp[$fmod][] = $v;
            unset($v);
        }
        unset($v);
        return $temp;
    }

}
