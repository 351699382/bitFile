<?php
/**
 * SuJun (https://github.com/351699382)
 *
 * @link      https://github.com/351699382
 * @copyright Copyright (c) 2017 SuJun
 * @license   http://www.apache.org/licenses/LICENSE-2.0.txt (Apache License)
 */

namespace BigFile;

/**
 * 操作文件
 */
class BigFile
{
    //配置
    private $config;

    public function __construct(array $config)
    {
        /**
         *   基本设置
        filePath 原文件路径
        savePath 保存文件路径
        saveFileName 保存文件名
         */
        if (empty($config['filePath'])) {
            throw new \Exception("分割原文件不存在", 500);
        }

        if (empty($config['savePath'])) {
            throw new \Exception("请设置savePath保存路径", 500);
        }
        $this->config['filePath'] = $config['filePath'];
        $this->config['savePath'] = $config['savePath'];
    }

    //HASH-》排序、去重、分割法

    //只分割、分割并去重、排序去重分割;
    //去重、去重分割、去重分割排序
    //排序、排序去重、排序去重分割
    public function execute()
    {

        //获得分割文件，根据是否去重设置HASH函数

        //

    }

    //设置分割
    /**
     *  分隔每个文件最大行数
     */
    public function Bsp($param)
    {
        //分隔每个文件最大行数
        if (empty($param)) {
            if (is_array($param)) {
                $this->config['maxLine'] = array_shift($param);
            } else {
                $this->config['maxLine'] = $param;
            }
        } else {
            $this->config['maxLine'] = 100000;
        }

        return $this;
    }

    //设置去重
    public function Bcr(callable $func)
    {
        $this->config['isClearRepeat'] = true;
        $this->config['isHash'] = true;
        $this->config['hashFunc'] = $func;
        return $this;
    }

    //设置排序
    public function Bs(callable $func)
    {

        $this->config['isSort'] = true;
        $this->config['sortFunc'] = $func;

        //此时需要根据字段进行排序
        $this->config['isHash'] = true;
        $this->config['hashFunc'] = function ($param, $mod) {return fmod($param, $mod);};

        return $this;
    }

    //设置多进程进行
    public function Bp($param = 2)
    {
        $this->config['isProcess'] = true;
        $this->config['processNum'] = $param;
    }

    //设置去掉首行
    public function Bf($param = '')
    {
        $this->config['isRemoveFirst'] = true;
    }

    //设置分割法
    public function Bsf()
    {

    }

}
