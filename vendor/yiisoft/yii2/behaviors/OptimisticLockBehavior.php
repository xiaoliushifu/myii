<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\behaviors;

use Yii;
use yii\db\BaseActiveRecord;
use yii\base\InvalidCallException;
use yii\validators\NumberValidator;
use yii\helpers\ArrayHelper;

/**
 * 1何时开始发挥作用？ 三个事件：beforeinsert,beforeupdate,beforedelete
 * 2版本值如何确定？从bodyParam里获取，并不是读取AR对象自身的version属性，还有一点，只支持web请求的应用场景
 * 3目前来看，就beforeupdate和beforedelete有用到乐观锁
 * OptimisticLockBehavior automatically upgrades a model's lock version using the column name 
 * returned by [[\yii\db\BaseActiveRecord::optimisticLock()|optimisticLock()]].
 *
 * Optimistic locking allows multiple users to access the same record for edits and avoids
 * potential conflicts. In case when a user attempts to save the record upon some staled data
 * (because another user has modified the data), a [[StaleObjectException]] exception will be thrown,
 * and the update or deletion is skipped.
 * 
 * To use this behavior, first enable optimistic lock by following the steps listed in 
 * [[\yii\db\BaseActiveRecord::optimisticLock()|optimisticLock()]], remove the column name 
 * holding the lock version from the [[\yii\base\Model::rules()|rules()]] method of your 
 * ActiveRecord class, then add the following code to it:
 *
 * ```php
 * use yii\behaviors\OptimisticLockBehavior;
 *
 * public function behaviors()
 * {
 *     return [
 *         OptimisticLockBehavior::className(),
 *     ];
 * }
 * ```
 *
 * By default, OptimisticLockBehavior will use [[\yii\web\Request::getBodyParam()|getBodyParam()]] to parse
 * the submitted value or set it to 0 on any fail. That means a request not holding the version attribute
 * may achieve a first successful update to entity, but starting from there any further try should fail
 * unless the request is holding the expected version number. 

 * Once attached, internal use of the model class should also fail to save the record if the version number 
 * isn't held by [[\yii\web\Request::getBodyParam()|getBodyParam()]]. It may be useful to extend your model class, 
 * enable optimistic lock in parent class by overriding [[\yii\db\BaseActiveRecord::optimisticLock()|optimisticLock()]], 
 * then attach the behavior to the child class so you can tie the parent model to internal use while linking the child model 
 * holding this behavior to the controllers responsible of receiving end user inputs.
 * Alternatively, you can also configure the [[value]] property with a PHP callable to implement a different logic.
 * 
 * OptimisticLockBehavior also provides a method named [[upgrade()]] that increases a model's 
 * version by one, that may be useful when you need to mark an entity as stale among connected clients
 * and avoid any change to it until they load it again:
 *
 * ```php
 * $model->upgrade();
 * ```
 *
 * @author Salem Ouerdani <tunecino@gmail.com>
 * @since 2.0.16
 * @see \yii\db\BaseActiveRecord::optimisticLock() for details on how to enable optimistic lock.
 */
class OptimisticLockBehavior extends AttributeBehavior
{
    /**
     * {@inheritdoc}
     *
     * In case of `null` value it will be directly parsed from [[\yii\web\Request::getBodyParam()|getBodyParam()]] or set to 0.
     */
    public $value;
    /**
     * {@inheritdoc}
     */
    public $skipUpdateOnClean = false;
    /**
     * @var string the attribute name holding the version value.
     */
    private $_lockAttribute;


    /**
     * {@inheritdoc}
     */
    public function attach($owner)
    {
        parent::attach($owner);

        if (empty($this->attributes)) {
            $lock = $this->getLockAttribute();
            //初始化要在哪些事件下开始业务逻辑，并且是哪些属性。
            //这里就是那个乐观锁的版本属性$lock
            $this->attributes = array_fill_keys(array_keys($this->events()), $lock);
        }
    }

    /**
     * {@inheritdoc}
     * 这是行为基类提供的方法：任何行为子类都可以利用它
     * 该方法的目的是把行为子类里的事件回调附加到属主对象的事件里
     * 数组下标是事件名称，数组值就是回调函数名
     * 对于乐观锁来说，目前只关心下面三个事件
     */
    public function events()
    {
        return Yii::$app->request instanceof \yii\web\Request ? [
            //这里虽然写了beforeInsert事件，但是其实在insert过程里没有判断锁的版本
            //所以对于新增场景，乐观锁不发挥作用
            BaseActiveRecord::EVENT_BEFORE_INSERT => 'evaluateAttributes',
            BaseActiveRecord::EVENT_BEFORE_UPDATE => 'evaluateAttributes',
            BaseActiveRecord::EVENT_BEFORE_DELETE => 'evaluateAttributes',
        ] : [];
    }

    /**
     * Returns the column name to hold the version value as defined in [[\yii\db\BaseActiveRecord::optimisticLock()|optimisticLock()]].
     * @return string the property name.
     * @throws InvalidCallException if [[\yii\db\BaseActiveRecord::optimisticLock()|optimisticLock()]] is not properly configured.
     * @since 2.0.16
     */
    protected function getLockAttribute()
    {
        if ($this->_lockAttribute) {
            return $this->_lockAttribute;
        }

        /* @var $owner BaseActiveRecord */
        $owner = $this->owner;
        //通过调用AR类覆盖BaseAR的方法来告知属主AR类是哪个属性存储版本值
        $lock = $owner->optimisticLock();
        if ($lock === null || $owner->hasAttribute($lock) === false) {
            throw new InvalidCallException("Unable to get the optimistic lock attribute. Probably 'optimisticLock()' method is misconfigured.");
        }
        $this->_lockAttribute = $lock;
        return $lock;
    }

    /**
     * {@inheritdoc}
     * 从BodyParams里获取乐观锁属性的值
     * 这个属性的值可以是固定的value,也可以通过一段函数过程计算，它不是直接读取AR对象的属性，而是从请求体中获得
     * 其实他就是个属性的值，但是它的地位比较特殊，它并不是AR对象的业务字段，而是为乐观锁的版本管理准备的
     * In case of `null`, value will be parsed from [[\yii\web\Request::getBodyParam()|getBodyParam()]] or set to 0.
     */
    protected function getValue($event)
    {
        if ($this->value === null) {
            $request = Yii::$app->getRequest();
            $lock = $this->getLockAttribute();
            $formName = $this->owner->formName();
            //优先使用FormName的方式获取
            //借助助手函数
            $formValue = $formName ? ArrayHelper::getValue($request->getBodyParams(), $formName . '.' . $lock) : null;
            //如果上一种方式没有获取到，那么就用下面这一种直接获取
            $input = $formValue ?: $request->getBodyParam($lock);
            //从上面获取值 版本值的过程我们知道，它一定来源于BodyParam，也就是$_POST或者原生请求体的a=b这样的
            
            //如果有值，那么它是否是一个数字，不是就返回0
            //这里就确定了，实现乐观锁里版本的字段的数据类型只能是整型
            $isValid = $input && (new NumberValidator())->validate($input);
            return $isValid ? $input : 0;
        }

        return parent::getValue($event);
    }

    /**
     * Upgrades the version value by one and stores it to database.
     *
     * ```php
     * $model->upgrade();
     * ```
     * @throws InvalidCallException if owner is a new record.
     * @since 2.0.16
     */
    public function upgrade()
    {
        /* @var $owner BaseActiveRecord */
        $owner = $this->owner;
        if ($owner->getIsNewRecord()) {
            throw new InvalidCallException('Upgrading the model version is not possible on a new record.');
        }
        $lock = $this->getLockAttribute();
        $version = $owner->$lock ?: 0;
        //这个也是更新AR对象的快捷方式（不走验证过程），还会同步更新OldAttribute。以后可以试试
        $owner->updateAttributes([$lock => $version + 1]);
    }
}
