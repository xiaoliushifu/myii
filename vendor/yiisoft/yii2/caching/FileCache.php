<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\caching;

use Yii;
use yii\helpers\FileHelper;

/**
* FileCache是使用文件系统驱动实现的缓存组件
既然是Cache的子类，所以那五个方法比不可少的要一一实现才行。另外，由于文件缓存的特殊性，也重写了exists方法。
 * FileCache implements a cache component using files.
 *每个缓存项都将使用一个单独地文件存储（会不会觉得浪费？）
 * For each data value being cached, FileCache will store it in a separate file.
 而每个文件都放在CachePath路径下，FileCache会自动进行垃圾回收来删除过期的缓存文件
 * The cache files are placed under [[cachePath]]. FileCache will perform garbage collection
 * automatically to remove expired cache files.
 *请参考[[Cache]]来看FileCache支持的常用操作
 * Please refer to [[Cache]] for common cache operations that are supported by FileCache.
 *
 * For more details and usage information on Cache, see the [guide article on caching](guide:caching-overview).
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class FileCache extends Cache
{
    /**缓存前缀
     * @var string a string prefixed to every cache key. This is needed when you store
	 当你为不同的应用都在[[cachePath]]相同目录下放置缓存文件时，可以避免缓存key的冲突
     * cache data under the same [[cachePath]] for different applications to avoid
     * conflict.
     *
     * To ensure interoperability, only alphanumeric characters should be used.
     */
    public $keyPrefix = '';
    /**
	* 这个成员，定义了cachePath，还使用了路径别名。
     * @var string the directory to store cache files. You may use path alias here.
     * If not set, it will use the "cache" subdirectory under the application runtime path.
     */
    public $cachePath = '@runtime/cache';
    /**缓存文件的后缀，默认是.bin
     * @var string cache file suffix. Defaults to '.bin'.
     */
    public $cacheFileSuffix = '.bin';
    /**
	* 整型，缓存文件所在子目录的层级，默认是1
     * @var int the level of sub-directories to store cache files. Defaults to 1.
	 * 如果系统有大量的缓存文件（比如1000000万），这时应该增加该值（通常不要超过3）。
     * If the system has huge number of cache files (e.g. one million), you may use a bigger value
	 用子目录层级机制主要是不想让太多文件都存在一个单独的目录里（涉及文件系统的IO操作性能）
     * (usually no bigger than 3). Using sub-directories is mainly to ensure the file system
     * is not over burdened with a single directory having too many files.
     */
    public $directoryLevel = 1;
    /**整型，可能性。百万分之一级别启动垃圾回收。何时触发？每次存入一个数据项时触发。
     * @var int the probability (parts per million) that garbage collection (GC) should be performed
	 默认是10，意味着是0.001%的概率
     * when storing a piece of data in the cache. Defaults to 10, meaning 0.001% chance.
	 这个数字的范围是0到1000000。0代表永不启动垃圾回收。
     * This number should be between 0 and 1000000. A value 0 means no GC will be performed at all.
     */
    public $gcProbability = 10;
    /**文件权限，当新创建一个缓存文件时使用。缓存文件也是文件，所以也得有文件系统那一套权限机制
     * @var int the permission to be set for newly created cache files.
	 *该值如何用？被php原生底层函数chmod作为参数使用。umask（文件的默认权限）不应用。
     * This value will be used by PHP chmod() function. No umask will be applied.
	 如果该值没有设置，也就是null的话，当将交给所处环境（操作系统）来决定
     * If not set, the permission will be determined by the current environment.
     */
    public $fileMode;
    /**
	* 刚才说到了有目录曾经机制，新创建目录时也有权限，也属于操作系统下文件系统里的概念。
     * @var int the permission to be set for newly created directories.
	 php原生函数chmod使用，umask（默认权限）是0。
     * This value will be used by PHP chmod() function. No umask will be applied.
	 默认是0775。啥意思呢？这是操作系统里文件系统的概念。拥有者7，所属组7，其他5。好像是linux操作系统
     * Defaults to 0775, meaning the directory is read-writable by owner and group,
     * but read-only for other users.
     */
    public $dirMode = 0775;


    /**
     * Initializes this component by ensuring the existence of the cache path.
     */
    public function init()
    {
        parent::init();
		//首先解析目录别名
        $this->cachePath = Yii::getAlias($this->cachePath);
		//没有该目录，则用FileHelper助手类来创建。
        if (!is_dir($this->cachePath)) {
            FileHelper::createDirectory($this->cachePath, $this->dirMode, true);
        }
    }

    /**
	* 这是FileCache继承自父类Cache必须实现的方法之一。
     * Checks whether a specified key exists in the cache.
     * This can be faster than getting the value from the cache if the data is big.
     * Note that this method does not check whether the dependency associated
     * with the cached data, if there is any, has changed. So a call to [[get]]
     * may return false while exists returns true.
     * @param mixed $key a key identifying the cached value. This can be a simple string or
     * a complex data structure consisting of factors representing the key.
     * @return bool true if a value exists in cache, false if the value is not in the cache or expired.
     */
    public function exists($key)
    {
		//正规化给定的key,就是缓存文件名了，然后根据这个文件名尝试去读取该文件
		//getCacheFile会根据key按照一定规则生成一个相对路径（可能真是存在文件系统里，也可能不存在）
        $cacheFile = $this->getCacheFile($this->buildKey($key));
		//filemtime函数返回文件上次被写入的时间戳（即文件内容修改的时间，不是读取文件内容的时间）
		//正常情况下，当代码执行到这里时，filemtime获取的文件上次修改时间肯定不会比当前时间time()大。
		//所以，该方法岂不是永远返回false？
		//看了setValue方法才知道，缓存文件在创建之初，用php原生函数touch生成了文件的修改时间为未来的某个时间，
		//这个未来的时间，就是数据项的过期时间。
		//这种用法比较新鲜。文件的修改时间，竟然可以随意的设置，你说有意思不？
        return @filemtime($cacheFile) > time();
    }

    /**继承自父类必须实现的方法之一
     * Retrieves a value from cache with a specified key.
     * This is the implementation of the method declared in the parent class.
     * @param string $key a unique key identifying the cached value
     * @return string|false the value stored in cache, false if the value is not in the cache or expired.
     */
    protected function getValue($key)
    {
        $cacheFile = $this->getCacheFile($key);

        if (@filemtime($cacheFile) > time()) {
            $fp = @fopen($cacheFile, 'r');
            if ($fp !== false) {
                @flock($fp, LOCK_SH);//取得共享锁
				//读取文件内容，为啥不用file_get_contents()?因为参数要求不同，一个需要文件路径，一个需要文件指针。
				//所以这里用stream_get_contents()。
                $cacheValue = @stream_get_contents($fp);
                @flock($fp, LOCK_UN);//释放共享锁
                @fclose($fp);//关闭文件资源
                return $cacheValue;
            }
        }

        return false;
    }

    /**
     * Stores a value identified by a key in cache.
     * This is the implementation of the method declared in the parent class.
     *
     * @param string $key the key identifying the value to be cached
     * @param string $value the value to be cached. Other types (If you have disabled [[serializer]]) unable to get is
     * correct in [[getValue()]].
     * @param int $duration the number of seconds in which the cached value will expire. 0 means never expire.
     * @return bool true if the value is successfully stored into cache, false otherwise
     */
    protected function setValue($key, $value, $duration)
    {
		//首先触发垃圾回收
        $this->gc();
		//首先根据文件名规则生成文件路径
        $cacheFile = $this->getCacheFile($key);
		//还得先创建层级目录
        if ($this->directoryLevel > 0) {
            @FileHelper::createDirectory(dirname($cacheFile), $this->dirMode, true);
        }
		//通过file_put_contents函数连贯地完成：1创建文件，2写入缓存内容，3关闭文件
		//参数LOCK_EX是独自锁
        if (@file_put_contents($cacheFile, $value, LOCK_EX) !== false) {
			//文件生成之后，再修改文件权限
            if ($this->fileMode !== null) {
                @chmod($cacheFile, $this->fileMode);
            }
			//注意，这里没有使用父类Cache的成员defaultDuration。
            if ($duration <= 0) {
                $duration = 31536000; // 1 year
            }
			//使用touch方法生成文件的访问时间和修改时间（好好看看php手册）
			//哥们你要知道，正常情况下，文件的访问时间，修改时间不应该是可以设置的，仔细想想。后话不提。
			//这里通过touch生成文件的修改时间，因为这是文件是否过期(也就是数据项是否过期）判断的根本依据。
            return @touch($cacheFile, $duration + time());
        } else {
            $error = error_get_last();
            Yii::warning("Unable to write cache file '{$cacheFile}': {$error['message']}", __METHOD__);
            return false;
        }
    }

    /**
     * Stores a value identified by a key into cache if the cache does not contain this key.
     * This is the implementation of the method declared in the parent class.
     *
     * @param string $key the key identifying the value to be cached
     * @param string $value the value to be cached. Other types (if you have disabled [[serializer]]) unable to get is
     * correct in [[getValue()]].
     * @param int $duration the number of seconds in which the cached value will expire. 0 means never expire.
     * @return bool true if the value is successfully stored into cache, false otherwise
     */
    protected function addValue($key, $value, $duration)
    {
		//首先计算出文件路径
        $cacheFile = $this->getCacheFile($key);
        if (@filemtime($cacheFile) > time()) {
            return false;
        }

        return $this->setValue($key, $value, $duration);
    }

    /**
     * Deletes a value with the specified key from cache
     * This is the implementation of the method declared in the parent class.
     * @param string $key the key of the value to be deleted
     * @return bool if no error happens during deletion
     */
    protected function deleteValue($key)
    {
		//首先计算出文件路径
        $cacheFile = $this->getCacheFile($key);
		//二话不说，直接删除之
        return @unlink($cacheFile);
    }

    /**根据给定的key，计算出文件的路径（该路径也许已经真实存在，或者不存在）
	 注意，Yii里所说的路径是包含最终的文件名的。可以是绝对路径，也可以是相对路径。
	 也许是开发人员对路径这个概念的理解不同吧，有的人总认为：路径是不包含文件名的目录名。
	 如果之前认为错的，这里纠正一下。
     * Returns the cache file path given the cache key.
     * @param string $key cache key
     * @return string the cache file path
     */
    protected function getCacheFile($key)
    {
        if ($this->directoryLevel > 0) {
			//缓存文件的基本文件目录
            $base = $this->cachePath;
            for ($i = 0; $i < $this->directoryLevel; ++$i) {
				//这行代码很关键，知道如何为层级子目录的命名吗？那就是根据key(已经正规化）的前2个字符命名。
				//因为默认的层级是1。即在runtime/cache下开始一级目录，比如runtime/cache/C1C2/C1C2xxxxx.bin
				//如果是二级目录的话，那就是runtime/cache/C1C2/C3C4/C1C2C3C4xxxxx.bin
				//如果是三级目录的话，那就是runtime/cache/C1C2/C3C4/C5C6/C1C2C3C4C5C6xxxxx.bin
				//依次类推，一般最多是三级目录。
				//另外，还有一种情况，有可能正规化的key的字符长度不够长，比如只有两个字符，这种情况下目录层级在多也
				//是只一级，您说是吧？
                if (($prefix = substr($key, $i + $i, 2)) !== false) {
                    $base .= DIRECTORY_SEPARATOR . $prefix;
                }
            }
			//最终返回的是：目录+正规化的key+后缀
            return $base . DIRECTORY_SEPARATOR . $key . $this->cacheFileSuffix;
        } else {
			//默认的目录层级数是1，所以大多数是走不到这个分支的
            return $this->cachePath . DIRECTORY_SEPARATOR . $key . $this->cacheFileSuffix;
        }
    }

    /**
	* 删除所有的数据项
     * Deletes all values from cache.
     * This is the implementation of the method declared in the parent class.
     * @return bool whether the flush operation was successful.
     */
    protected function flushValues()
    {
		//文件缓存FileCache是如何实现一次性删除缓存目录下的所有文件的呢？
        $this->gc(true, false);

        return true;
    }

    /**
	清除过期的缓存文件
     * Removes expired cache files.
	 参数$force,是否强制删除，不必看垃圾回收的概率
     * @param bool $force whether to enforce the garbage collection regardless of [[gcProbability]].
	 默认是false,也就是说默认得计算垃圾回收的概率值
     * Defaults to false, meaning the actual deletion happens with the probability as specified by [[gcProbability]].
     * @param bool $expiredOnly whether to removed expired cache files only.
     * If false, all cache files under [[cachePath]] will be removed.
     */
    public function gc($force = false, $expiredOnly = true)
    {
		//看到这个源码，就明白了垃圾回收的概率是怎么计算的了吧？概率的分母A就是mt_rand(0,A)。
		//想定的概率高点，就把A往小了写。简单不？明了不？
        if ($force || mt_rand(0, 1000000) < $this->gcProbability) {
            $this->gcRecursive($this->cachePath, $expiredOnly);
        }
    }

    /**递归地删除某个目录下的过期的缓存文件
	看过源码的话，就明白了，主要的逻辑就是遍历目录呗。代码比较固定
     * Recursively removing expired cache files under a directory.
     * This method is mainly used by [[gc()]].
     * @param string $path the directory under which expired cache files are removed.
     * @param bool $expiredOnly whether to only remove expired cache files. If false, all files
     * under `$path` will be removed.
     */
    protected function gcRecursive($path, $expiredOnly)
    {
		//首先打开目录，不返回false就是成功打开了
        if (($handle = opendir($path)) !== false) {
			//readdir原生函数，将一个个返回该目录下的文件（目录也是文件）
            while (($file = readdir($handle)) !== false) {
                if ($file[0] === '.') {
                    continue;
                }
                $fullPath = $path . DIRECTORY_SEPARATOR . $file;
				//如果还是目录，那就递归
                if (is_dir($fullPath)) {
                    $this->gcRecursive($fullPath, $expiredOnly);
					//如果全部删除目录，那么该目录下的文件删除后，还得把这个子目录干掉也。
                    if (!$expiredOnly) {
                        if (!@rmdir($fullPath)) {
                            $error = error_get_last();
                            Yii::warning("Unable to remove directory '{$fullPath}': {$error['message']}", __METHOD__);
                        }
                    }
				//否则就判断条件满足否，满足的删除（unlink)。什么条件呢？
				//$expiredOnly为真，则只删除过期的；$expireOnly为false,则全部删除（不管过不过期）
                } elseif (!$expiredOnly || $expiredOnly && @filemtime($fullPath) < time()) {
					//删除时也比较高级，使用抑制错误符@,且应用error_get_last()原生函数，你用过这个函数吗？
                    if (!@unlink($fullPath)) {
                        $error = error_get_last();
                        Yii::warning("Unable to remove file '{$fullPath}': {$error['message']}", __METHOD__);
                    }
                }
            }
            closedir($handle);
        }
    }
}
