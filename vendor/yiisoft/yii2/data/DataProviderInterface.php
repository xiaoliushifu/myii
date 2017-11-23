<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\data;

/**DataProviderInterface定义了所有数据提供器必须实现的方法
 * DataProviderInterface is the interface that must be implemented by data provider classes.
 *数据提供器是组件（封装了排序和分页功能。分页，排序是非常常见的业务），可以在小部件（比如GridView,ListView)里使用。
 * Data providers are components that sort and paginate data, and provide them to widgets
 * such as [[\yii\grid\GridView]], [[\yii\widgets\ListView]].
 *
 * For more details and usage information on DataProviderInterface, see the [guide article on data providers](guide:output-data-providers).
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
interface DataProviderInterface
{
    /**
     * Prepares the data models and keys.
     *
     * This method will prepare the data models and keys that can be retrieved via
     * [[getModels()]] and [[getKeys()]].
     *
     * This method will be implicitly called by [[getModels()]] and [[getKeys()]] if it has not been called before.
     *
     * @param bool $forcePrepare whether to force data preparation even if it has been done before.
     */
    public function prepare($forcePrepare = false);

    /**
	 * 返回数据模型的数量
     * Returns the number of data models in the current page.
	 等效于count($provider->getModels())
     * This is equivalent to `count($provider->getModels())`.
     * When [[getPagination|pagination]] is false, this is the same as [[getTotalCount|totalCount]].
     * @return int the number of data models in the current page.
     */
    public function getCount();

    /**
     * Returns the total number of data models.
     * When [[getPagination|pagination]] is false, this is the same as [[getCount|count]].
     * @return int total number of possible data models.
     */
    public function getTotalCount();

    /**
	* 返回当前页的数据模型（多个）
     * Returns the data models in the current page.
     * @return array the list of data models in the current page.
     */
    public function getModels();

    /**
     * Returns the key values associated with the data models.
     * @return array the list of key values corresponding to [[getModels|models]]. Each data model in [[getModels|models]]
     * is uniquely identified by the corresponding key value in this array.
     */
    public function getKeys();

    /**
	* 排序
     * @return Sort the sorting object. If this is false, it means the sorting is disabled.
     */
    public function getSort();

    /**分页对象
     * @return Pagination the pagination object. If this is false, it means the pagination is disabled.
     */
    public function getPagination();
}
