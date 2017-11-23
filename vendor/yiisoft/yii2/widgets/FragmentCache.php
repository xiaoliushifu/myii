<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\widgets;

use Yii;
use yii\base\Widget;
use yii\caching\Cache;
use yii\caching\Dependency;
use yii\di\Instance;

/**
 * 片段缓存用于在View视图中使用来缓存页面数据
 * FragmentCache is used by [[\yii\base\View]] to provide caching of page fragments.
 *
 * @property string|false $cachedContent The cached content. False is returned if valid content is not found
 * in the cache. This property is read-only.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class FragmentCache extends Widget
{
    /**
	 * 既然要缓存，肯定依赖于底层的缓存组件。由$cache成员给出组件ID或缓存对象
     * @var Cache|array|string the cache object or the application component ID of the cache object.
	 若是组件ID，则是在FragmentCache对象初始化init方法时，进行实例化为缓存对象；如果后期要替换，请注意直接替换对象，而
	 不是缓存驱动的配置信息。了解片段缓存的源代码，才会知道这个流程。
     * After the FragmentCache object is created, if you want to change this property,
     * you should only assign it with a cache object.
     * Starting from version 2.0.2, this can also be a configuration array for creating the object.
     */
    public $cache = 'cache';
    /**
	* 缓存有效期，看到没，还是那一套，接下来该缓存依赖，
     * @var int number of seconds that the data can remain valid in cache.
     * Use 0 to indicate that the cached data will never expire.
     */
    public $duration = 60;
    /**
     * @var array|Dependency the dependency that the cached content depends on.
     * This can be either a [[Dependency]] object or a configuration array for creating the dependency object.
     * For example,
     *
     * ```php
     * [
     *     'class' => 'yii\caching\DbDependency',
     *     'sql' => 'SELECT MAX(updated_at) FROM post',
     * ]
     * ```
     *
     * would make the output cache depends on the last modified time of all posts.
     * If any post has its modification time changed, the cached content would be invalidated.
     */
    public $dependency;
    /**
	 * 这个得说一说：一系列的元素，这些元素将会引起被缓存内容的变化。可以认为是key下的子key。
     * @var array list of factors that would cause the variation of the content being cached.
	 听不明白的话，举个例子就知道了：比如同样的页面，根据客户端语言的不同，缓存外语版和中文版。
	 这里，语言就是一个元素。还有，分页，第一页是这样，第二页是那样。所以分页也是一个元素。
     * Each factor is a string representing a variation (e.g. the language, a GET parameter).
     * The following variation setting will cause the content to be cached in different versions
     * according to the current application language:
     *
     * ```php
     * [
     *     Yii::$app->language,
     * ]
     * ```
     */
    public $variations;
    /**
	* 临时开启或者关闭片段缓存功能
     * @var bool whether to enable the fragment cache. You may use this property to turn on and off
     * the fragment cache according to specific setting (e.g. enable fragment cache only for GET requests).
     */
    public $enabled = true;
    /**该属性内部使用，用来嵌入动态内容的。片段缓存支持内部嵌套
     * @var array a list of placeholders for embedding dynamic contents. This property
     * is used internally to implement the content caching feature. Do not modify it.
     */
    public $dynamicPlaceholders;


    /**
     * Initializes the FragmentCache object.
     */
    public function init()
    {
        parent::init();
		//先实例化缓存组件，这是实现缓存功能的底层硬需求
        $this->cache = $this->enabled ? Instance::ensure($this->cache, Cache::className()) : null;

        if ($this->cache instanceof Cache && $this->getCachedContent() === false) {
			//把片段缓存对象，放到视图对象中，专门存放片段缓存对象的属性cacheStack中
            $this->getView()->cacheStack[] = $this;
			//新开启一段ob缓冲区（输出缓冲区），因为这是处理有关页面的缓存，故涉及echo print等语句的，开启ob后，
			//会临时存储于php内存中，而不是返回给web服务器。
            ob_start();
            ob_implicit_flush(false);//关闭隐式冲刷
        }
    }

    /**
	* 这是FramentCache类作为小部件的子类，必须实现的run方法，一般由end()方法内部调用
     * Marks the end of content to be cached.
     * Content displayed before this method call and after [[init()]]
     * will be captured and saved in cache.
     * This method does nothing if valid content is already found in cache.
     */
    public function run()
    {
        if (($content = $this->getCachedContent()) !== false) {
            echo $content;
		//缓存驱动，必须是实现Cache接口的类（缓存的key才符合规律，操作一致）
        } elseif ($this->cache instanceof Cache) {
			//这里把初始化片段缓存对象时，放到cacheStack里的家伙弹出来，这是为何？
			//不需要了吗？应该是的，因为运行到run方法
            array_pop($this->getView()->cacheStack);
            
            $content = ob_get_clean();
            if ($content === false || $content === '') {
                return;
            }
			//缓存依赖判断
            if (is_array($this->dependency)) {
                $this->dependency = Yii::createObject($this->dependency);
            }
			//注意看，片段的数据格式。是个数组:元素0是片段内容，元素1是继承自父类的动态小坑
            $data = [$content, $this->dynamicPlaceholders];
			//放到缓存中
            $this->cache->set($this->calculateKey(), $data, $this->duration, $this->dependency);

            if (empty($this->getView()->cacheStack) && !empty($this->dynamicPlaceholders)) {
                $content = $this->updateDynamicContent($content, $this->dynamicPlaceholders);
            }
            echo $content;
        }
    }

    /**
     * @var string|bool the cached content. False if the content is not cached.
     */
    private $_content;

    /**
     * Returns the cached content if available.
     * @return string|false the cached content. False is returned if valid content is not found in the cache.
     */
    public function getCachedContent()
    {
        if ($this->_content === null) {
            $this->_content = false;
            if ($this->cache instanceof Cache) {
				//计算片段缓存的key
                $key = $this->calculateKey();
				//这里还是利用缓存对象，获得曾经缓存的数据
                $data = $this->cache->get($key);
				//元素0是片段内容，元素1是小洞
                if (is_array($data) && count($data) === 2) {
                    list ($content, $placeholders) = $data;
                    if (is_array($placeholders) && count($placeholders) > 0) {
                        if (empty($this->getView()->cacheStack)) {
                            // outermost cache: replace placeholder with dynamic content
                            $content = $this->updateDynamicContent($content, $placeholders);
                        }
                        foreach ($placeholders as $name => $statements) {
                            $this->getView()->addDynamicPlaceholder($name, $statements);
                        }
                    }
                    $this->_content = $content;
                }
            }
        }

        return $this->_content;
    }

    /**
     * Replaces placeholders in content by results of evaluated dynamic statements.
     *
     * @param string $content
     * @param array $placeholders
     * @return string final content
     */
    protected function updateDynamicContent($content, $placeholders)
    {
        foreach ($placeholders as $name => $statements) {
            $placeholders[$name] = $this->getView()->evaluateDynamicContent($statements);
        }

        return strtr($content, $placeholders);
    }

    /**
	* 对于片段缓存，缓存的key要特殊处理，体现页面片段缓存的特点。
     * Generates a unique key used for storing the content in cache.
     * The key generated depends on both [[id]] and [[variations]].
     * @return mixed a valid cache key
     */
    protected function calculateKey()
    {
        $factors = [__CLASS__, $this->getId()];
        if (is_array($this->variations)) {
            foreach ($this->variations as $factor) {
                $factors[] = $factor;
            }
        }

        return $factors;
    }
}
