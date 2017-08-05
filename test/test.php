<?php
require './base.php';

use BigFile\ClearRepeating;
use BigFile\Sorting;
use BigFile\Spliting;

try {
    $param=[
        'filePath'=>'./123456.csv',             //处理文件
        'savePath'=>'./data/',                  //保存路径
        'saveFileName'=>'test.csv',             //保存文件名,可不写
        'isProcess'=>true,                      //设置是否采用多进程方式处理
        'processNum'=>4,                        //开启进程数（需要isProcess设置为true时有效）
        'maxLine'=>100000,                      //分割后每个文件保存行数
        'isRemoveFirst'=>true,                  //剔除首行，有些文件第一行是说明
        'column'=>0,                            //需要把指定字段分到同一文件时指定这个参数并设置hashFunc。
        'isHash'=>false,
        'hashFunc'=>function ($param, $mod) {   //分割hash函数，param变量表示文件的每一行，(根据你业务来定义)
            return fmod($param[0], $mod);//$param[0]来写，一般对应column参数使用
        },
        'sortFunc'=>function ($a, $b) {
            if($a[1] == $b[1]) {
              return 0;
            }
            return ($a[1] < $b[1]) ? -1 : 1;
        }
    ];
    $obj = new Sorting($param);
    $obj->execute();//执行任务

} catch (\Exception $e) {
    echo $e->getMessage();
    exit;
}


/*try {
    $param = [
        'filePath' => './123456.csv', //处理文件
        'savePath' => './data/', //保存路径
        'saveFileName' => 'test.csv', //保存文件名,可不写
        'isProcess' => false, //设置是否采用多进程方式处理
        'processNum' => 4, //开启进程数（需要isProcess设置为true时有效）
        'maxLine' => 100000, //分割后每个文件保存行数
        'isRemoveFirst' => true, //剔除首行，有些文件第一行是说明
        'column' => 0, //需要把指定字段分到同一文件时指定这个参数并设置hashFunc。
        'isHash' => false,
        'hashFunc' => function ($param, $mod) { //分割hash函数，param变量表示文件的每一行，(根据你业务来定义)
            return fmod($param[0], $mod); //$param[0]来写，一般对应column参数使用
        },
    ];
    $obj = new Spliting($param);
    $obj->execute(); //执行任务
} catch (\Exception $e) {
    echo $e->getMessage();
    exit;
}
*/

/*try {
    $param = [
        'filePath' => './123456.csv', //处理文件
        'savePath' => './data/', //保存路径
        'saveFileName' => 'test.csv', //保存文件名,可不写
        'isProcess' => false, //设置是否采用多进程方式处理
        'processNum' => 4, //开启进程数（需要isProcess设置为true时有效）
        'maxLine' => 100000, //分割后每个文件保存行数
        'isRemoveFirst' => true, //剔除首行，有些文件第一行是说明
        'column' => 0, //需要把指定字段分到同一文件时指定这个参数并设置hashFunc。
        'isHash' => false,
        'hashFunc' => function ($param, $mod) { //分割hash函数，param变量表示文件的每一行，(根据你业务来定义)
            return fmod($param[0], $mod); //$param[0]来写，一般对应column参数使用
        },
        'sortFunc' => function ($a, $b) {
            if ($a[1] == $b[1]) {
                return 0;
            }
            return ($a[1] < $b[1]) ? -1 : 1;
        },
    ];

    $obj = new Sorting($param);
    $obj->execute(); //执行任务

} catch (\Exception $e) {
    echo $e->getMessage();
    exit;
}
*/