# bigFile,大文件处理
[![Software license][ico-license]](LICENSE)
[![Latest development][ico-version-dev]][link-packagist]


-----

先前公司有个需求就是对发送数据去重，因为每次发送过来的数据文件都比较大，加上公司权限太多，申请流程麻烦，因此用PHP来实现这个需求。给过来的数据文件都是几百到千W这样（1核512M内存处理1kW手机账号分割并去重大概5分钟），跑的没问题，其实私下测试亿级别也没问题,慢点而已~︶~。工具实现了csv文件的处理，如果你需要处理其它文件格式的话只要实现"File/FileAbstract"接口就可以了。

----


# bigFile是什么？

bigFile是一个PHP写的文件处理工具类。

## 安装

通过composer，这是推荐的方式，可以使用composer.json 声明依赖，或者直接运行下面的命令。

```php
    composer require "sujun/bigFile:v1.0.0"
```

放入composer.json文件中

```php
    "require": {
        "sujun/bigFile": "v1.0.0"
    }
```

然后运行

```
composer update
```



去重：
原理是根据需要去重的字段分割大文件。

```
use BigFile\ClearRepeating;

try {
    $param=[
        'filePath'=>'./123456.csv',             //处理文件
        'savePath'=>'./data/',                  //保存路径
        'saveFileName'=>'test.csv',             //保存文件名,可不写
        'isProcess'=>false,                      //设置是否采用多进程方式处理
        'processNum'=>4,                        //开启进程数（需要isProcess设置为true时有效）
        'isRemoveFirst'=>true,                  //剔除首行，有些文件第一行是说明
        'column'=>0,                            //需要去重的字段，数字。0表示第一列
        'isHash'=>true,
        'hashFunc'=>function ($param, $mod) {   //去重hash函数，param变量表示文件的每一行。(根据你业务来定义)
            return fmod(crc32($param[0]), $mod);//$param[0]来写，一般对应column参数使用
        }
    ];
    $obj = new ClearRepeating($param);
    $obj->execute();//执行任务
} catch (\Exception $e) {
    echo $e->getMessage();
    exit;
}

```

分割：

```
use BigFile\Spliting;

try {
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

```

排序：
目前使用归并算法,使用方式需要自定排序。跟PHP的自定义usort函数类似

```
use BigFile\Sorting;

try {
    $param=[
        'filePath'=>'./123456.csv',             //处理文件
        'savePath'=>'./data/',                  //保存路径
        'saveFileName'=>'test.csv',             //保存文件名,可不写
        'isProcess'=>false,                      //设置是否采用多进程方式处理
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

```



[ico-license]: https://img.shields.io/github/license/helei112g/payment.svg
[ico-version-dev]: https://img.shields.io/packagist/vpre/riverslei/payment.svg


[link-packagist]: https://packagist.org/packages/riverslei/payment
[link-downloads]: https://packagist.org/packages