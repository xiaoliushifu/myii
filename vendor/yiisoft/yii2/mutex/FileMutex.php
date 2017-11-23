<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\mutex;

use Yii;
use yii\base\InvalidConfigException;
use yii\helpers\FileHelper;

/**
 * FileMutex implements mutex "lock" mechanism via local file system files.
 依赖于php的flock函数，果然不出余之所料
 * This component relies on PHP `flock()` function.
 *
 * Application configuration example:
 *
 * ```
 * [
 *     'components' => [
 *         'mutex' => [
 *             'class' => 'yii\mutex\FileMutex'
 *         ],
 *     ],
 * ]
 * ```
 *
 * Note: this component can maintain the locks only for the single web server,
 * it probably will not suffice to your in case you are using cloud server solution.
 *
 * Warning: due to `flock()` function nature this component is unreliable when
 * using a multithreaded server API like ISAPI.
 *
 * @see Mutex
 *
 * @author resurtm <resurtm@gmail.com>
 * @since 2.0
 */
class FileMutex extends Mutex
{
    /**
     * @var string the directory to store mutex files. You may use path alias here.
     * Defaults to the "mutex" subdirectory under the application runtime path.
     */
    public $mutexPath = '@runtime/mutex';
    /**
     * @var int the permission to be set for newly created mutex files.
     * This value will be used by PHP chmod() function. No umask will be applied.
     * If not set, the permission will be determined by the current environment.
     */
    public $fileMode;
    /**
     * @var int the permission to be set for newly created directories.
     * This value will be used by PHP chmod() function. No umask will be applied.
     * Defaults to 0775, meaning the directory is read-writable by owner and group,
     * but read-only for other users.
     */
    public $dirMode = 0775;

    /**
     * @var resource[] stores all opened lock files. Keys are lock names and values are file handles.
     */
    private $_files = [];


    /**
     * Initializes mutex component implementation dedicated for UNIX, GNU/Linux, Mac OS X, and other UNIX-like
     * operating systems.
     * @throws InvalidConfigException
     */
    public function init()
    {
        $this->mutexPath = Yii::getAlias($this->mutexPath);
		//首先创建文件所在路径
        if (!is_dir($this->mutexPath)) {
            FileHelper::createDirectory($this->mutexPath, $this->dirMode, true);
        }
    }

    /**
     * Acquires lock by given name.
     * @param string $name of the lock to be acquired.
     * @param int $timeout to wait for lock to become released.
     * @return bool acquiring result.
     */
    protected function acquireLock($name, $timeout = 0)
    {
        $file = fopen($this->getLockFilePath($name), 'w+');
        if ($file === false) {
            return false;
        }
        if ($this->fileMode !== null) {
            @chmod($this->getLockFilePath($name), $this->fileMode);
        }
        $waitTime = 0;
		//LOCK_EX是进行排他锁，LOCK_NB暂不理解。是在进行锁操作时也不阻塞？（谁都可以抢锁吗？）直到谁最终抢到？
        while (!flock($file, LOCK_EX | LOCK_NB)) {
            $waitTime++;
			//尝试是有次数限制的，
            if ($waitTime > $timeout) {
                fclose($file);

                return false;
            }
			//而且休眠一秒
            sleep(1);
        }
		//锁定后的文件存到内存中
        $this->_files[$name] = $file;

        return true;
    }

    /**
     * Releases lock by given name.
     * @param string $name of the lock to be released.
     * @return bool release result.
     */
    protected function releaseLock($name)
    {
		//没有这个锁，或释放锁失败。否则就是释放锁成功。
		//接着再进行else。
        if (!isset($this->_files[$name]) || !flock($this->_files[$name], LOCK_UN)) {
            return false;
        } else {
			//关闭这个文件
            fclose($this->_files[$name]);
			//文件系统删除这个文件
            unlink($this->getLockFilePath($name));
			//内存中删除这个文件
            unset($this->_files[$name]);

            return true;
        }
    }

    /**
	* 文件路径+锁名+.lock。
	* 锁名需进行md5散列。
     * Generate path for lock file.
     * @param string $name
     * @return string
     * @since 2.0.10
     */
    protected function getLockFilePath($name)
    {
        return $this->mutexPath . DIRECTORY_SEPARATOR . md5($name) . '.lock';
    }
}
