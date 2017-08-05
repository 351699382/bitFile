<?php
namespace BigFile\File;

use BigFile\File\FileAbstract;

/**
 *
 *
 */
class CsvSplFile extends FileAbstract
{

    /**
     * 获取总行数
     */
    public function getCount($filePath)
    {
        $this->fileExists($filePath);
        $splObject = new \SplFileObject($filePath, 'rb');
        //获取最大行数
        $splObject->seek(filesize($filePath));
        return $splObject->key();
    }

    /**
     * 批量写进
     * @param array  $param   [description]
     * @param string $csvFile [description]
     */
    public function batchWrite($filePath, array $param, $mode = 'a+')
    {
        $file = new \SplFileObject($filePath, $mode);
        if ($file->flock(LOCK_EX)) {
            foreach ($param as $v) {
                $file->fputcsv($v);
                $file->fflush();
            }
            $file->flock(LOCK_UN);
        } else {
            throw new \Exception("锁文件失败", 1);
        }
    }

    /**
     * 获取数据
     * @param  integer $start  [description]
     * @param  integer $length [description]
     * @return [type]          [description]
     */
    public function getLimitData($filePath, $start = 0, $length = 100000)
    {
        $this->fileExists($filePath);
        $start = ($start - 1) < 0 ? 0 : ($start - 1);
        $data = array();
        //多进程运行情况不能共用
        $splObject = new \SplFileObject($filePath, 'rb');
        $splObject->seek($start);
        while ($length-- && !$splObject->eof()) {
            $temp = $splObject->fgetcsv();
            (!empty($temp[0]) || !empty($temp[1])) && $data[] = $temp;
            $splObject->next();
        }
        return $data;
    }

    /**
     * 获取数据
     * @param  [type] $filePath [description]
     * @return [type]           [description]
     */
    public function getAllData($filePath)
    {
        $this->fileExists($filePath);
        $data = array();
        $splObject = new \SplFileObject($filePath, 'rb');
        while (!$splObject->eof()) {
            $temp = $splObject->fgetcsv();
            (!empty($temp[0])) && $data[] = $temp;
            $splObject->next();
        }
        return $data;
    }

    /**
     * 检测文件是否存在
     */
    private function fileExists($filePath)
    {
        if (!file_exists($filePath)) {
            throw new \Exception("分割文件不存在", 500);
        }

        if (!is_readable($filePath)) {
            throw new \Exception("分割文件不可读", 500);
        }

    }

}
