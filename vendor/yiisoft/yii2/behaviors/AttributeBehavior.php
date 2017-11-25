<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\behaviors;

use Yii;
use Closure;
use yii\base\Behavior;
use yii\base\Event;
use yii\db\ActiveRecord;

/**
 * 当某个事件发生的时候，AttributeBehavior自动给AR对象的一个或多个属性设置指定的值
 * AttributeBehavior automatically assigns a specified value to one or multiple attributes of an ActiveRecord
 * object when certain events happen.
 * 如何使用AttributeBehavior呢？配置attributes属性和value属性就行了。
 * attributes是以AR事件名为下标，目标属性为值的数组，
 * value是一个回调函数，返回用于设置上述目标属性的值。
 * 看下面例子
 * To use AttributeBehavior, configure the [[attributes]] property which should specify the list of attributes
 * that need to be updated and the corresponding events that should trigger the update. Then configure the
 * [[value]] property with a PHP callable whose return value will be used to assign to the current attribute(s).
 * For example,
 *
 * ```php
 * use yii\behaviors\AttributeBehavior;
 *
 * public function behaviors()
 * {
 *     return [
 *         [
 *             'class' => AttributeBehavior::className(),
				//属性列表
 *             'attributes' => [
 *                 ActiveRecord::EVENT_BEFORE_INSERT => 'attribute1',
 *                 ActiveRecord::EVENT_BEFORE_UPDATE => 'attribute2',
 *             ],
				//值
 *             'value' => function ($event) {
 *                 return 'some value';
 *             },
 *         ],
 *     ];
 * }
 * ```
 *由于上述配置的属性们都是由行为类自动设置的，所以，无需验证这些属性了，也就不要出现在AR的rules()里了
 * Because attribute values will be set automatically by this behavior, they are usually not user input and should therefore
 * not be validated, i.e. they should not appear in the [[\yii\base\Model::rules()|rules()]] method of the model.
 *
 * @author Luciano Baraglia <luciano.baraglia@gmail.com>
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class AttributeBehavior extends Behavior
{
    /**数组，给出要操作的属性列表，这些属性的值将由value属性指定。
     * @var array list of attributes that are to be automatically filled with the value specified via [[value]].
	 * 数组的key是事件名，val是属性名，意味着当key指定的事件发生时，更新val指定的属性们为[[value]]
     * The array keys are the ActiveRecord events upon which the attributes are to be updated,
	 *val可以是一个字符串表示的属性名，也可以是多个属性组成的数组
     * and the array values are the corresponding attribute(s) to be updated. You can use a string to represent
     * a single attribute, or an array to represent a list of attributes. For example,
     *
     * ```php
     * [
     *     ActiveRecord::EVENT_BEFORE_INSERT => ['attribute1', 'attribute2'],//多个属性
     *     ActiveRecord::EVENT_BEFORE_UPDATE => 'attribute2',//一个属性
     * ]
     * ```
     */
    public $attributes = [];
    /**
	 * value可以是匿名函数，静态函数，对象方法，db表达式
     * @var mixed the value that will be assigned to the current attributes. This can be an anonymous function,
     * callable in array format (e.g. `[$this, 'methodName']`), an [[\yii\db\Expression|Expression]] object representing a DB expression
     * (e.g. `new Expression('NOW()')`), scalar, string or an arbitrary value. If the former, the return value of the
     * function will be assigned to the attributes.
	 * 回调函数的签名如下：
     * The signature of the function should be as follows,
     *
     * ```php
     * function ($event)
     * {
     *     // return value will be assigned to the attribute
     * }
     * ```
     */
    public $value;
    /**
     * @var bool whether to skip this behavior when the `$owner` has not been
     * modified
     * @since 2.0.8
     */
    public $skipUpdateOnClean = true;


    /**
	 * 注意下面的@inheritdoc,说明这个是继承而来的，AttributeBehavior作为行为类，当然要有行为类的接口events()
	 * 一个行为类中有关事件的操作，有哪些方法还记得吗？attach(),events(),dettach()等
     * @inheritdoc
     */
    public function events()
    {
		//新认识一个php函数，array_fill_keys($arr,$val);为某个数组$arr的val作为新数组的key,新数组的val都是$val。
		//也就是下标key不同，但是所有的下标值都一样是$val的php函数
		//返回新数组
        return array_fill_keys(
            array_keys($this->attributes),
            'evaluateAttributes'
        );
		//上述返回的结果是以AR的事件名为key,字符串'evaluateAttributes'为值的数组。这其实是把当前类的evaluateAttribute()
		//方法注册为事件处理者了。看behaviors的attach()方法就知道字符串'evaluateAttributes'其实是方法名。
    }

    /**
	 * 真正的事件处理者
     * Evaluates the attribute value and assigns it to the current attributes.
     * @param Event $event
     */
    public function evaluateAttributes($event)
    {
        if ($this->skipUpdateOnClean
            && $event->name == ActiveRecord::EVENT_BEFORE_UPDATE
            && empty($this->owner->dirtyAttributes)
        ) {
            return;
        }

        if (!empty($this->attributes[$event->name])) {
			//获得当前事件里要操作的属性们（字符串或者数组都行）
            $attributes = (array) $this->attributes[$event->name];
			//获得属性们要被设置的value
            $value = $this->getValue($event);
			//遍历这些属性们，依次设置为value
            foreach ($attributes as $attribute) {
                // ignore attribute names which are not string (e.g. when set by TimestampBehavior::updatedAtAttribute)
				//只设置那些属性名是字符串的属性
                if (is_string($attribute)) {
                    $this->owner->$attribute = $value;
                }
            }
        }
    }

    /**
     * Returns the value for the current attributes.
     * This method is called by [[evaluateAttributes()]]. Its return value will be assigned
     * to the attributes corresponding to the triggering event.
     * @param Event $event the event that triggers the current attribute updating.
     * @return mixed the attribute value
     */
    protected function getValue($event)
    {
		//是一个匿名函数						亦或是个数组，且可调用的
        if ($this->value instanceof Closure || is_array($this->value) && is_callable($this->value)) {
            return call_user_func($this->value, $event);
        }
		//否则直接返回其值
        return $this->value;
    }
}
