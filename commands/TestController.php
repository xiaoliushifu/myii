<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace app\commands;

use Yii;
use yii\console\Controller;
use yii\helpers\FileHelper;

class TestController extends Controller
{
    protected $csvPath;
    protected $user;
    //一页的大小，这是待处理的数据按照这个量分为多个页，
    //一般这个值要小于等于每个进程的任务量。即每个进程至少处理一页的数据

    //如果step=10，size=5,那么一个子进程每次处理5条的数据，处理2次。
    protected $size = 50;
    //定义，每个进程分派的任务量
    protected $step = 100;

    public function beforeAction($action)
    {
        parent::beforeAction($action);
//        $this->csvPath = dirname(Yii::$app->basePath) . '/csv';
        $this->csvPath = Yii::$app->basePath . '/csv';
        $this->user = 'user';
        return true;
    }

    //多进程的脚本，$count任务总量
    public function actionSend($count)
    {
        if (!$count) exit('count is zero');
        $taskStartTime = microtime(true);
        //创建目录
        if (!file_exists($this->csvPath)) {
            FileHelper::createDirectory($this->csvPath,0755,true);
//            CommonFun::recursionMkDir($this->csvPath);
        }
        //总共需要多少个进程
        $totalNum = ceil($count/$this->step);
        $childs = array();
        for($i=0; $i< $totalNum; $i++) {
            //从这一行开始，出现了一个新的进程，子进程。
            //主进程和子进程同时在这里执行。
            //如何区别当前是主进程还是子进程呢？根据进程号$pid
            //如果当前在子进程工作，那么$pid=0，
            //如果是一个大于0的数，那么就表示它是子进程的进程号，当前程序的执行进程是主进程
            $pid = pcntl_fork();
            if ($pid == -1) {
                exit('Could not fork');
                // 大于0的，表示是子进程的进程号。表示程序运行在主进程中
            } elseif ($pid) {
                echo "I'm the Parent $i\n";
                $childs[] = $pid;
                //其它情况$pid=0,表示当前程序运行在子进程中
            } else {
                //子进程处理业务
                $this->handelChildProcess($i,$count);
            }
        }

        // 上面已经完成了进程的fork,产生了子进程。并且子进程也各自工作了。
        //这里开始对各个子进程的执行状态，进行等待监听！
        while (count($childs) > 0) {
            foreach ($childs as $key => $pid) {
                //等待$pid表示的子进程的返回，返回状态由$status给出，WNOHANG表示子进程已经退出时就直接返回
                //$pid表示的子进程还没执行完这里有可能会阻塞，
                $res = pcntl_waitpid($pid, $status, WNOHANG);
                //-1代表error,
                // 大于0代表子进程已退出,返回的是子进程的pid,
                //非阻塞时0代表没取到退出子进程
                if ($res == -1 || $res > 0) {
                    echo "process$key=> $pid\n";
                    unset($childs[$key]);
                }
            }
            sleep(1);
        }
        //整个任务结束耗费时间
        $lastTime = $this->getElapsedTime($taskStartTime);
        Yii::info("toallastTime|"  . $lastTime, __METHOD__);
        exit("success|$lastTime");
    }

    //从指定位置开始，获取$size大小的数据
    /**
     * @param $start 数据的起始偏移量
     * @return \Generator
     *
     * 从数据库中，按照 limit size offset $start 取得数据
     * 数据封装成生成器里
     */
    public function getRows($start) {
        $size = $this->size;
        $end = $start + $size;

        $userList = Yii::$app->db->createCommand('SELECT  email,user_code,mobile  FROM `qy_user` LIMIT '. $size .  ' OFFSET '  . $start)->queryAll();
        //使用了生成器，就不必写return语句了
        foreach ($userList as  $value) {
            yield $value;
        }
    }

    /**
     * @param $processKey  自定义的子进程编号（从0开始）
     * @param $totalCount  任务总量
     */
    public function handelChildProcess($processKey, $totalCount)
    {
        echo "process $processKey start \n";
        //标记当前任务的时间开始
        $taskStartTime = microtime(true);

        //根据每个子进程要处理的业务量，计算出要处理的页数
        $pageTotal = ceil($this->step/$this->size);
        for ($i=1; $i <= $pageTotal; $i++) {
            //计算起始位置，每个进程的起始位置都不一样
            //每个进程都按照自己的编号位置和总处理量决定开始位置
            //比如，0号进程的任务量是10，每一页的数据大小是2，
            //那么，0号进程需要处理5页，
            //第一页的起始位置就是 0 * 10 + （1-1） * 2 = 0
//            第二页的起始位置就是 0 * 10 + （2-1） * 2 = 2
            $start = $processKey * $this->step + ($i-1) * $this->size;
            //这里是每次处理的起始位置和要处理的数据总量比较。（一个是位置，一个是数量。两个不完全一样）
            if ($start > $totalCount) {
                $lastTime = $this->getElapsedTime($taskStartTime);
                Yii::info("lastTime|process" . $processKey . $lastTime, __METHOD__);
//                echo "process $processKey end\n";
                exit("process $processKey end 超过总数了 \n");
            }
            //返回生成器对象$userList
            //这个对象实现了Iterator接口，所以可以被迭代。每迭代一次都生成一个元素，这个元素才开始占用内存，直到迭代完对象也就消失了
//这就是省内存的地方。
//你可以理解，和之前循环数组对比，它是一个产生数组元素的对象：它可以一个个的产生数组元素，而不是一下子产生全部出来的数组；
//之前的数组都是一下子就全部出现在内存中。循环数组只是在一个个的复制数组元素。
//所以从返回值来区别：一个是返回生成器对象，一个是纯数组。在数组量非常小的情况下，纯数组占用的内存应该会比生成器对象小。
//所以，生成器对象非常适合大型数组，100，1000以上这样的。
            $userList = $this->getRows($start);
            //生成器对象是一个实现了Iterator接口的对象，可以通过迭代的方式一个个产生数组元素，慢慢地占用内存而不是一下子占用大量内存
            foreach ($userList as $key => $value) {
                //把这一行数据写入到指定的文件中
                $this->writeRow($value, $this->csvPath . '/' . $this->user . $processKey . '.csv');
            }
            sleep(1);
        }
        //计算下当前子进程处理完分派给它的业务量总共耗费了多久
        $lastTime = $this->getElapsedTime($taskStartTime);
        Yii::info("lastTime|process" . $processKey . '|' . $lastTime, __METHOD__);
//        echo "process $processKey end\n";
        //结束php代码，也就是结束了当前的子进程，
        //如果父进程在pcntl_waitpid()函数处展开了监听，那么此时父进程应该可以收到返回值
        exit("process $processKey end " . $lastTime . "\n");
    }

    //根据传入的起始时间，计算花费时间
    public function getElapsedTime($startTime) {
        $endTime = microtime(true);
        $elapsedTime = number_format($endTime- $startTime, 4);
        return $elapsedTime;
    }


    /**
     *
     * @param $row 要写入文件的数据。一维关联数组
     * @param $file 要写入文件的绝对路径
     */
    public function writeRow($row, $file) {
        //转码下
        $row = array_map(function($v){
            return iconv('utf-8', 'gbk//IGNORE', $v);
        },$row);
        //追加模式打开文件（打开后文件指针在文件的末尾），没有则直接创建该文件
        $handle = fopen($file,'a');
        //csv格式直接写入该文件中
        fputcsv($handle, $row);

        //为啥没有文件的关闭处理？？
    }
}
