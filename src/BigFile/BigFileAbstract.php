<?php
/**
 * SuJun (https://github.com/351699382)
 *
 * @link      https://github.com/351699382
 * @copyright Copyright (c) 2016 SuJun
 * @license   http://www.apache.org/licenses/LICENSE-2.0.txt (Apache License)
 */

namespace BigFile;

use BigFile\BigFileInterface;
use BigFile\File\CsvSplFile;

abstract class BigFileAbstract implements BigFileInterface
{

    /**
     *  配置
     *  maxLine 分隔每个文件最大行数
     *  filePath 原文件路径
     *  savePath 保存文件路径
     *  saveFileName 保存文件名
     *  isProcess 是否多进程方式，默认为true即开启
     *  processNum 开启进程数
     *  isHash 是否需要hash，用于将特殊数据分配到指定文件
     *  hashFunc hash函数,需要isHash为true时有效
     *  isRemoveFirst 是否去掉首行
     * @var array
     */
    protected $config = array();

    /**
     * oauth版本
     * @var string
     */
    protected $version = '1.0.0';

    abstract public function __construct(array $config);

    /**
     * 单进程方式实现
     * @param $api
     * @param string $param
     * @param string $method
     * @param bool $multi
     * @return mixed
     */
    abstract protected function singleProcess();

    /**
     * 多进程方式实现
     * @param $api
     * @param string $param
     * @param string $method
     * @param bool $multi
     * @return mixed
     */
    abstract protected function multiProcess();

    /**
     * 根据文件后缀取对象
     */
    protected function getFileObj($filePath = '')
    {
        $arr = explode('.', $filePath);
        switch (end($arr)) {
            case 'csv':
                $obj = new CsvSplFile;
                break;

            default:
                $obj = new CsvSplFile;
                break;
        }
        return $obj;
    }

    /**
     * 获取目录所有文件
     * @param  [type] $filePath [description]
     * @return [type]           [description]
     */
    public function tree($filePath)
    {
        $files = [];
        $queue = array($filePath);
        while ($data = each($queue)) {
            $path = $data['value'];
            if (is_dir($path) && $handle = opendir($path)) {
                while ($file = readdir($handle)) {
                    if ($file == '.' || $file == '..') {
                        continue;
                    }
                    $realPath = $path . $file;
                    $files[] = $realPath;
                    if (is_dir($realPath)) {
                        $queue[] = $realPath;
                    }
                }
            }
            closedir($handle);
        }
        return $files;
    }

}
