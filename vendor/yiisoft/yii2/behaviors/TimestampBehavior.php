<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\behaviors;

use yii\base\InvalidCallException;
use yii\db\BaseActiveRecord;

/**
 * 时间戳行为类实现了，为指定的属性填充当前的时间戳值，时间戳行为类属于属性行为类的子类，更是行为类的子类。
 所以可先看看其直接父类AttributeBehavior
 * TimestampBehavior automatically fills the specified attributes with the current timestamp.
 * 如何使用？把如下代码写到你的AR类中即可
 * To use TimestampBehavior, insert the following code to your ActiveRecord class:
 *
 * ```php
 * use yii\behaviors\TimestampBehavior;
 *
 * public function behaviors()
 * {
 *     return [
 *         TimestampBehavior::className(),
 *     ];
 * }
 * ```
 *默认情况下，时间戳行为类填充created_at,updated_at两个属性的值为当前时间戳（insert),updated_at（update）
 * By default, TimestampBehavior will fill the `created_at` and `updated_at` attributes with the current timestamp

 * when the associated AR object is being inserted; it will fill the `updated_at` attribute
 * with the timestamp when the AR object is being updated. The timestamp value is obtained by `time()`.
 *
 * 因为这是用行为方式添加的，故此时已经走过了rules,validate过程。故不必再写到rules里了
 * Because attribute values will be set automatically by this behavior, they are usually not user input and should therefore
 * not be validated, i.e. `created_at` and `updated_at` should not appear in the [[\yii\base\Model::rules()|rules()]] method of the model.
 *
 * 如果您的数据库是mysql，那么开发人员只要确保这两个字段数据类型是int()就行了
 * For the above implementation to work with MySQL database, please declare the columns(`created_at`, `updated_at`) as int(11) for being UNIX timestamp.
 *
 * 如果不是默认的，比如字段名不对，或者值想自定义。那就配置createdAtAttribute和updatedAtAttribute两个成员即可。
 例子如下：
 * If your attribute names are different or you want to use a different way of calculating the timestamp,
 * you may configure the [[createdAtAttribute]], [[updatedAtAttribute]] and [[value]] properties like the following:
 *
 * ```php
 * use yii\db\Expression;
 *
 * public function behaviors()
 * {
 *     return [
 *         [
 *             'class' => TimestampBehavior::className(),
 *             'createdAtAttribute' => 'create_time',//给出新字段名
 *             'updatedAtAttribute' => 'update_time',
 *             'value' => new Expression('NOW()'),//给出字段值
 *         ],
 *     ];
 * }
 * ```
 *
 * In case you use an [[\yii\db\Expression]] object as in the example above, the attribute will not hold the timestamp value, but
 * the Expression object itself after the record has been saved. If you need the value from DB afterwards you should call
 * the [[\yii\db\ActiveRecord::refresh()|refresh()]] method of the record.
 *
TimestampBehavior也提供了一个touch方法来为指定的字段设置当前时间戳，并保存到数据库里
 * TimestampBehavior also provides a method named [[touch()]] that allows you to assign the current
 * timestamp to the specified attribute(s) and save them to the database. For example,
 *
 * ```php
 * $model->touch('creation_time');
 * ```
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @author Alexander Kochetov <creocoder@gmail.com>
 * @since 2.0
 */
class TimestampBehavior extends AttributeBehavior
{
    /**
     * @var string the attribute that will receive timestamp value
     * Set this property to false if you do not want to record the creation time.
     */
    public $createdAtAttribute = 'created_at';
    /**
     * @var string the attribute that will receive timestamp value.
     * Set this property to false if you do not want to record the update time.
     */
    public $updatedAtAttribute = 'updated_at';
    /**
     * @inheritdoc
     *
	 * 
     * In case, when the value is `null`, the result of the PHP function [time()](http://php.net/manual/en/function.time.php)
     * will be used as value.
     */
    public $value;


    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

		//默认怎么实现的？就是初始化时先判断有无设置字段，没有设置就是默认的created_at,updated_at
        if (empty($this->attributes)) {
            $this->attributes = [
                BaseActiveRecord::EVENT_BEFORE_INSERT => [$this->createdAtAttribute, $this->updatedAtAttribute],
                BaseActiveRecord::EVENT_BEFORE_UPDATE => $this->updatedAtAttribute,
            ];
        }
    }

    /**
	* 重写getValue方法，在父类AttributeBehavior的事件处理者evaluateAttributes方法里调用时，值将全部是
	时间戳。
     * @inheritdoc
     *
     * In case, when the [[value]] is `null`, the result of the PHP function [time()](http://php.net/manual/en/function.time.php)
     * will be used as value.
     */
    protected function getValue($event)
    {
        if ($this->value === null) {
            return time();
        }
        return parent::getValue($event);
    }

    /**
     * Updates a timestamp attribute to the current timestamp.
     *
     * ```php
     * $model->touch('lastVisit');
     * ```
     * @param string $attribute the name of the attribute to update.
     * @throws InvalidCallException if owner is a new record (since version 2.0.6).
     */
    public function touch($attribute)
    {
        /* @var $owner BaseActiveRecord */
		//这里体现了行为类，因为出现了owner成员属性，这正是对属主类的引用
        $owner = $this->owner;
		//新记录不更新
        if ($owner->getIsNewRecord()) {
            throw new InvalidCallException('Updating the timestamp is not possible on a new record.');
        }
		//更新语句时才能执行
        $owner->updateAttributes(array_fill_keys((array) $attribute, $this->getValue(null)));
    }
}
