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

    //单个进程一次可以处理的任务量
    //一般这个值要小于等于单个进程的任务量。

    //如果step=10，size=5,那么一个子进程每次处理5条的数据，总共需要处理2次。
    //在$size小于2000的时候，基本上每个子进程处理一次大概花费一秒，无论它的任务量多少
    //如果$size是2000，那么处理一次花费的时间将会大概是1.5,其它情况没有测试
    protected $size = 2000;
    //定义，每个进程分派的任务量,总任务量和单个进程的任务量，决定了子进程的数量
    protected $step = 10000;

    //合理设置每个子进程完成的总量和一次的处理量，如果总量多但是一次处理量少，那么每个子进程在获取下次处理量的开销上将增多。
    //需要多多测试才知道什么比例才是最好的合理值
    
    public function beforeAction($action)
    {
        parent::beforeAction($action);
//        $this->csvPath = dirname(Yii::$app->basePath) . '/csv';
        $this->csvPath = Yii::$app->basePath . '/csv';
        $this->user = 'user';
        return true;
    }

    //多进程的脚本，$count是所有子进程要处理的任务总量
    public function actionSend($count)
    {
        if (!$count) exit('count is zero');
        $taskStartTime = microtime(true);
        //创建目录
        if (!file_exists($this->csvPath)) {
            FileHelper::createDirectory($this->csvPath,0755,true);
//            CommonFun::recursionMkDir($this->csvPath);
        }
        //总共需要多少个进程，是通过任务量计算的
        $totalNum = ceil($count/$this->step);
        $childs = array();
        for($i=0; $i< $totalNum; $i++) {
            //从这一行pcntl_fork()开始，出现了一个新的进程，子进程。
            //主进程和子进程同时在这里执行。
            //如何区别当前是主进程还是子进程呢？根据进程号$pid
            //如果当前在子进程工作，那么$pid=0，
            //如果是一个大于0的数，那么就表示它是子进程的进程号，当前程序的执行进程是主进程
            //注意，一旦fork出子进程，其实就是对主进程资源的近乎拷贝，包括计数器，内存等，还有代码（其中有变量）
            //这里要特别说明一下的是：php对象的引用计数就会+1哦，所以好的优化方法就是如果不需要处理这个对象
            //就把它销毁吧（php就是unset，可以使引用计数-1）
            $pid = pcntl_fork();
            if ($pid == -1) {
                exit('Could not fork');
                // 大于0的，表示是子进程的进程号。表示程序运行在主进程中
            } elseif ($pid) {
                //这里是主进程才会进入到这段代码执行
                echo "I'm the Parent $i, pid={$pid}\n";
                $childs[] = $pid;
                //其它情况$pid=0,表示当前程序运行在子进程中
            } else {
                //从这里打印的数据可以看到，其实每次子进程的父进程是同一个
                //因为主进程不会走到这一块的代码，只有子进程才会走到这儿的，这就是为什么是同一个主进程产生了的多个子进程，而不可能是由子进程再产生孙进程的根本原因
                echo "子进程{$i}的父进程是 " . posix_getppid()."\n";
                //子进程处理业务，并最后通过exit函数主动销毁自己
                //所以子进程没有机会去再fork出它的子进程，也就是孙进程
                $this->handelChildProcess($i,$count);

                //其实这里还有优化的地方：比如子进程不需要的原来主进程里的资源，减少引用计数等
                //后面学习《网络编程实战》时看到的
            }
        }

        // 上面已经完成了进程的fork,产生了子进程。并且子进程也各自工作了。处理完后自动销毁
        //也就是handelChildProcess最后有exit()代码
        //因为子进程在handelChildProcess中自我销毁，所以理论上只有主进程才会执行下述代码
        //这里开始对各个子进程的执行状态，通过轮询进行等待监听！
        while (count($childs) > 0) {
            echo "childs count is " . count($childs) ."\n";
            foreach ($childs as $key => $pid) {
                //等待$pid表示的子进程的返回，返回状态由$status给出，WNOHANG表示子进程未退出时就直接返回
                //也就是非阻塞。pcntl_wait没有办法不阻塞，所以用waitpid。
                //非阻塞一般要配合轮询，也就是后续再来调用
                $res = pcntl_waitpid($pid, $status, WNOHANG);

                //进程的退出原因
                if ($res > 0) {
                    //判断是否正常退出（比如exit,或者代码执行完）
                    if (pcntl_wifexited($status)) {
                        //正常退出的返回码（整型）
                        $ret = pcntl_wexitstatus($status);
                        echo "process$key=> $pid 正常退出 返回码：$ret \n";
                    } else {
                        echo "process$key=> $pid 非正常退出\n";
                    }
                    //是否因为某个外部的信号退出（比如kill之类的）
                    if (pcntl_wifsignaled($status)) {
                        $ret = pcntl_wtermsig($status);
                        echo "process$key=> $pid 信号退出 信号码是： $ret\n";
                    } else {
                        echo "process$key=> $pid 非信号退出\n";
                    }
                }

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

        //根据当前子进程要处理的业务总量，计算出要处理的页数
        //每个子进程也要拆分分配给它的业务总量，而不是直接开始循环，那样虽然简单但是有时并不高效
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
            //子进程处理一次，歇息一段时间
            sleep(1);
        }
        //计算下当前子进程处理完分派给它的业务总量后总共耗费了多久
        $lastTime = $this->getElapsedTime($taskStartTime);
        Yii::info("lastTime|process" . $processKey . '|' . $lastTime, __METHOD__);
//        echo "process $processKey end\n";
        //结束php代码，也就是结束了当前的子进程，
        //如果父进程在pcntl_waitpid()函数处展开了监听，那么此时父进程应该可以收到返回值
        //注意，在这里就要结束进程（php就是exit），而不要再往下走了，
        //这是多进程代码的一个通用写法，减少多进程变为僵尸进程的危险
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
