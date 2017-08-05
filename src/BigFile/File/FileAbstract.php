<?php

/**
 *
 * @author SuJun <351699382@qq.com>
 * @link      https://github.com/351699382
 * @copyright Copyright (c) 2017 SuJun
 * @license   http://www.apache.org/licenses/LICENSE-2.0.txt (Apache License)
 */

namespace BigFile\File;

/**
 *
 *
 *
 */
abstract class FileAbstract
{

    /**
     * 批量写进
     * @param array  $param   [description]
     * @param string $csvFile [description]
     */
    abstract public function batchWrite($filePath, array $param, $mode = 'a+');

    /**
     * 获取数据
     * @param  integer $start  [description]
     * @param  integer $length [description]
     * @return [type]          [description]
     */
    abstract public function getLimitData($filePath, $start, $length);

    /**
     * 获取整个文件数据
     * @param  [type] $filePath [description]
     * @return [type]           [description]
     */
    abstract public function getAllData($filePath);

    /**
     * 获取总行数
     * @param  [type] $filePath [description]
     * @return [type]           [description]
     */
    abstract public function getCount($filePath);

    /**
     * 获取目录所有文件
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
