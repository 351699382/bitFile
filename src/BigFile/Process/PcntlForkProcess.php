<?php
/**
 * SuJun (https://github.com/351699382)
 *
 * @link      https://github.com/351699382
 * @copyright Copyright (c) 2016 SuJun
 * @license   http://www.apache.org/licenses/LICENSE-2.0.txt (Apache License)
 */

namespace BigFile\Process;

use BigFile\Process\ProcessAbstract;
use BigFile\Process\ProcessInterface;

class PcntlForkProcess extends ProcessAbstract
{

    /**
     * 任务的实际处理者，对象, 必须有 runWorker 方法
     */
    private $worker;

    /**
     * 进程运行信息
     * @var [type]
     */
    private $info;

    /**
     * 保存进程号
     * @var [type]
     */
    private $process;

    public function __construct($worker)
    {
        if (!($worker instanceof ProcessInterface)) {
            throw new \Exception("处理类不支持多进程方式", 500);
        }
        
        $this->worker = $worker;

        //设置错误
        if (function_exists('pcntl_signal')) {
            declare (ticks = 1);
            pcntl_signal(SIGTERM, array(&$this, "sigHandler"));
            pcntl_signal(SIGHUP, array(&$this, "sigHandler"));
            pcntl_signal(SIGINT, array(&$this, "sigHandler"));
            pcntl_signal(SIGQUIT, array(&$this, "sigHandler"));
            pcntl_signal(SIGILL, array(&$this, "sigHandler"));
            pcntl_signal(SIGPIPE, array(&$this, "sigHandler"));
            pcntl_signal(SIGALRM, array(&$this, "sigHandler"));
        }

    }

    /**
     * 进程中止处理
     * @param  [type] $signal [description]
     * @return [type]         [description]
     */
    public function sigHandler($signal)
    {
        $time = date('Y-m-d H:i:s');
        if ($signal == 14) {
            //忽略alarm信号
            $this->info['exception'][] = $time . " ignore alarm signo[{$signal}]\r\n";
        } else {
            $this->info['exception'][] = $time . " exit signo[{$signal}]\r\n";
            exit("");
        }
    }

    /**
     * fork子进程处理数据
     * worker进程最大数量, 至少两个
     * @param Array $data 需要处理的数据，必须是数组
     */
    public function run($data, $processNum = 2)
    {
        $processNum = max(2, (int) $processNum);

        $childs = array();
        for ($i = 0; $i < $processNum; $i++) {
            $pid = pcntl_fork();
            if ($pid == -1) {
                $this->info['error'][] = "Fork worker failed!";
                return false;
            } elseif ($pid) {
                $this->info['fork'][] = '创建pid:' . $pid . ",时间：" . date('Y-m-d H:i:s') . "\n";
                $childs[] = $pid;
                $this->process[$i] = $pid;
            } else {
                $this->worker->worker($data[$i]);
                exit();
            }
        }
        $this->check($childs);
    }

    /**
     * 检测子进程状态，监控子进程是否退出，并防止僵尸进程
     */
    protected function check($childs)
    {
        while (count($childs) > 0) {
            foreach ($childs as $key => $pid) {
                $res = pcntl_waitpid($pid, $status, WNOHANG);
                if ($res == -1 || $res > 0) {
                    $this->info['fork'][] = '结束pid:' . $pid . ",时间：" . date('Y-m-d H:i:s') . "\n";
                    unset($childs[$key]);
                }
            }
            sleep(1);
        }
        $this->info['error'][] = pcntl_strerror(pcntl_get_last_error()) . "\n";
    }

    public function getInfo()
    {
        return $this->info;
    }

}
