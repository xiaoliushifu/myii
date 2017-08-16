<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\base;

use Yii;
use ArrayAccess;
use ArrayObject;
use ArrayIterator;
use ReflectionClass;
use IteratorAggregate;
use yii\helpers\Inflector;
use yii\validators\RequiredValidator;
use yii\validators\Validator;

/**
 * Model是数据模型的基类
 * Model is the base class for data models.
 *Model实现了经常使用的特性：
 * Model implements the following commonly used features:
 *属性声明，默认情况下每个public类成员被认为是模型的属性
 * - attribute declaration: by default, every public class member is considered as
 *   a model attribute
 *   属性标签，每个属性都关联了一个给终端用户展示的标签
 * - attribute labels: each attribute may be associated with a label for display purpose
 * 批量赋值
 * - massive attribute assignment
 * 基于场景的验证
 * - scenario-based validation
 *Model在验证过程中，也会触发下述的事件
 * Model also raises the following events when performing data validation:
 *
 * - [[EVENT_BEFORE_VALIDATE]]: an event raised at the beginning of [[validate()]]
 * - [[EVENT_AFTER_VALIDATE]]: an event raised at the end of [[validate()]]
 *可以直接使用Model存储数据，也可以自定义扩展它
 * You may directly use Model to store model data, or extend it with customization.
 *
 * For more details and usage information on Model, see the [guide article on models](guide:structure-models).
 *
 * @property \yii\validators\Validator[] $activeValidators The validators applicable to the current
 * [[scenario]]. This property is read-only.
 * @property array $attributes Attribute values (name => value).
 * @property array $errors An array of errors for all attributes. Empty array is returned if no error. The
 * result is a two-dimensional array. See [[getErrors()]] for detailed description. This property is read-only.
 * @property array $firstErrors The first errors. The array keys are the attribute names, and the array values
 * are the corresponding error messages. An empty array will be returned if there is no error. This property is
 * read-only.
 * @property ArrayIterator $iterator An iterator for traversing the items in the list. This property is
 * read-only.
 * @property string $scenario The scenario that this model is in. Defaults to [[SCENARIO_DEFAULT]].
 * @property ArrayObject|\yii\validators\Validator[] $validators All the validators declared in the model.
 * This property is read-only.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class Model extends Component implements IteratorAggregate, ArrayAccess, Arrayable
{
    use ArrayableTrait;

    /**默认场景的名字
     * The name of the default scenario.
     */
    const SCENARIO_DEFAULT = 'default';
    /**事件，ModelEvent事件触发在validate()之前，可以设置ModelEvent的valid属性为false来停止验证流程
     * @event ModelEvent an event raised at the beginning of [[validate()]]. You may set
     * [[ModelEvent::isValid]] to be false to stop the validation.
     */
    const EVENT_BEFORE_VALIDATE = 'beforeValidate';
    /**
     * @event Event an event raised at the end of [[validate()]]
     */
    const EVENT_AFTER_VALIDATE = 'afterValidate';

    /**数组，只读，存储属性的错误信息
     * @var array validation errors (attribute name => array of errors)
     */
    private $_errors;
    /**数组对象ArrayObject，含有一系列的验证对象
     * @var ArrayObject list of validators
     */
    private $_validators;
    /**当前场景
     * @var string current scenario
     */
    private $_scenario = self::SCENARIO_DEFAULT;


    /**
     * 返回属性的验证规则
     * Returns the validation rules for attributes.
     *验证规则在validate()中使用，
     * Validation rules are used by [[validate()]] to check if attribute values are valid.
     * 子类应该覆盖这个方法来声明自己的验证规则
     * Child classes may override this method to declare different validation rules.
     *每个规则的格式如下：
     * Each rule is an array with the following structure:
     *
     * ```php
     * [
     *     ['attribute1', 'attribute2'],
     *     'validator type',
     *     'on' => ['scenario1', 'scenario2'],
     *     //...other parameters...
     * ]
     * ```
     *
     * where
     *  属性列表
     *  - attribute list: required, specifies the attributes array to be validated, for single attribute you can pass a string;
     *  验证类型
     *  - validator type: required, specifies the validator to be used. It can be a built-in validator name,
     *    a method name of the model class, an anonymous function, or a validator class name.
     *    on字段
     *  - on: optional, specifies the [[scenario|scenarios]] array in which the validation
     *    rule can be applied. If this option is not set, the rule will apply to all scenarios.
     *    附加键值对，用于初始化验证器对象的属性
     *  - additional name-value pairs can be specified to initialize the corresponding validator properties.
     *    Please refer to individual validator class API for possible properties.
     *验证器是一个继承自Validator类的对象，或者是行内验证器（写在model里第一个方法）
     * A validator can be either an object of a class extending [[Validator]], or a model class method
     * 方法的签名如下
     * (called *inline validator*) that has the following signature:
     *
     * ```php
     * // $params refers to validation parameters given in the rule
     * function validatorName($attribute, $params)
     * ```
     *
     * In the above `$attribute` refers to the attribute currently being validated while `$params` contains an array of
     * validator configuration options such as `max` in case of `string` validator. The value of the attribute currently being validated
     * can be accessed as `$this->$attribute`. Note the `$` before `attribute`; this is taking the value of the variable
     * `$attribute` and using it as the name of the property to access.
     *Yii提供了一系列的内置验证器
     * Yii also provides a set of [[Validator::builtInValidators|built-in validators]].
     * 每个都有别名，可以在rules中使用。
     * Each one has an alias name which can be used when specifying a validation rule.
     *
     * Below are some examples:
     *
     * ```php
     * [
     *     // built-in "required" validator
     *     [['username', 'password'], 'required'],
     *     // built-in "string" validator customized with "min" and "max" properties
     *     ['username', 'string', 'min' => 3, 'max' => 12],
     *     // built-in "compare" validator that is used in "register" scenario only
     *     ['password', 'compare', 'compareAttribute' => 'password2', 'on' => 'register'],
     *     // an inline validator defined via the "authenticate()" method in the model class
     *     ['password', 'authenticate', 'on' => 'login'],
     *     // a validator of class "DateRangeValidator"
     *     ['dateRange', 'DateRangeValidator'],
     * ];
     * ```
     *
     * Note, in order to inherit rules defined in the parent class, a child class needs to
     * merge the parent rules with child rules using functions such as `array_merge()`.
     *
     * @return array validation rules
     * @see scenarios()
     */
    public function rules()
    {
        return [];
    }

    /**返回所有的场景及每个场景关联的激活属性
     * Returns a list of scenarios and the corresponding active attributes.
     * 什么是激活属性？在当前场景下验证的属性都是激活属性
     * An active attribute is one that is subject to validation in the current scenario.
     * 看看返回的数组格式，比看十几行代码更容易快速了解一个方法
     * The returned array should be in the following format:
     *
     * ```php
     * [
     *     'scenario1' => ['attribute11', 'attribute12', ...],
     *     'scenario2' => ['attribute21', 'attribute22', ...],
     *     ...
     * ]
     * ```
     *默认情况下，激活属性被认为是安全的，可以批量赋值
     * By default, an active attribute is considered safe and can be massively assigned.
     * 如果某（些）个属性不允许被批量赋值，（也就是认为不安全的）
     * If an attribute should NOT be massively assigned (thus considered unsafe),
     * 可以前缀这个属性一个“！”字符，比如!rank
     * please prefix the attribute with an exclamation character (e.g. `'!rank'`).
     *默认返回rules里发现的所有场景
     * The default implementation of this method will return all scenarios found in the [[rules()]]
     * 一个特殊的场景SCENARIO_DEFAULT将包含在rules里发现的所有属性
     * declaration. A special scenario named [[SCENARIO_DEFAULT]] will contain all attributes
     * 每个场景都会关联一些属性，这些属性会应用验证规则，适用于当前场景的验证规则
     * found in the [[rules()]]. Each scenario will be associated with the attributes that
     * are being validated by the validation rules that apply to the scenario.
     *
     * @return array a list of scenarios and the corresponding active attributes.
     */
    public function scenarios()
    {
        $scenarios = [self::SCENARIO_DEFAULT => []];
        foreach ($this->getValidators() as $validator) {
            //二层循环说明，一条rule的on字段可以指定多个场景
            foreach ($validator->on as $scenario) {
                $scenarios[$scenario] = [];
            }
            foreach ($validator->except as $scenario) {
                $scenarios[$scenario] = [];
            }
        }
        $names = array_keys($scenarios);

        foreach ($this->getValidators() as $validator) {
            if (empty($validator->on) && empty($validator->except)) {
                foreach ($names as $name) {
                    foreach ($validator->attributes as $attribute) {
                        $scenarios[$name][$attribute] = true;
                    }
                }
            } elseif (empty($validator->on)) {
                foreach ($names as $name) {
                    if (!in_array($name, $validator->except, true)) {
                        foreach ($validator->attributes as $attribute) {
                            $scenarios[$name][$attribute] = true;
                        }
                    }
                }
            } else {
                foreach ($validator->on as $name) {
                    foreach ($validator->attributes as $attribute) {
                        $scenarios[$name][$attribute] = true;
                    }
                }
            }
        }

        foreach ($scenarios as $scenario => $attributes) {
            if (!empty($attributes)) {
                $scenarios[$scenario] = array_keys($attributes);
            }
        }

        return $scenarios;
    }

    /***返回表单名
     * Returns the form name that this model class should use.
     * 表单名主要用于在\yii\widgets\ActiveForm中命名表单项
     * The form name is mainly used by [[\yii\widgets\ActiveForm]] to determine how to name
     * 如果表单名是A,一个属性名是b,那么表单项input的name将是A[b]。
     * the input fields for the attributes in a model. If the form name is "A" and an attribute
     * 如果表单名是空字符串，input的name就仅仅是b了
     * name is "b", then the corresponding input name would be "A[b]". If the form name is
     * an empty string, then the input name would be "b".
     *使用上述命名规则的目的是，有的表单含有多个模型，以每个模型名为下标分组来存储$_POST过来的数据，方便区别它们
     * The purpose of the above naming schema is that for forms which contain multiple different models,
     * 方便不同模型的表单项的区别
     * the attributes of each model are grouped in sub-arrays of the POST-data and it is easier to
     * differentiate between them.
     *默认返回不带命名空间的类名
     * By default, this method returns the model class name (without the namespace part)
     * as the form name. You may override it when the model is used in different forms.
     *
     * @return string the form name of this model class.
     * @see load()
     */
    public function formName()
    {
        $reflector = new ReflectionClass($this);
        return $reflector->getShortName();
    }

    /**返回一系列的属性名
     * Returns the list of attribute names.
	 *默认返回模型类的公共非静态属性，可以覆盖
     * By default, this method returns all public non-static properties of the class.
     * You may override this method to change the default behavior.
     * @return array list of attribute names.
     */
    public function attributes()
    {
        $class = new ReflectionClass($this);
        $names = [];
		//应用PHP反射类方法，获得反射类的所有public属性
        foreach ($class->getProperties(\ReflectionProperty::IS_PUBLIC) as $property) {
			//public属性里再排除静态属性
            if (!$property->isStatic()) {
                $names[] = $property->getName();
            }
        }

        return $names;
    }

    /**
     * Returns the attribute labels.
     *这个方法，一般是具体的model类实现
     *属性的label主要用于友好地展示给终端用户
     * Attribute labels are mainly used for display purpose. For example, given an attribute
     * `firstName`, we can declare a label `First Name` which is more user-friendly and can
     * be displayed to end users.
     *默认情况下，label由generateAttributeLabel()方法生成
     * By default an attribute label is generated using [[generateAttributeLabel()]].
     * This method allows you to explicitly specify attribute labels.
     *
     * Note, in order to inherit labels defined in the parent class, a child class needs to
     * merge the parent labels with child labels using functions such as `array_merge()`.
     *
     * @return array attribute labels (name => label)
     * @see generateAttributeLabel()
     */
    public function attributeLabels()
    {
        return [];
    }

    /**
     * Returns the attribute hints.
     *
     * Attribute hints are mainly used for display purpose. For example, given an attribute
     * `isPublic`, we can declare a hint `Whether the post should be visible for not logged in users`,
     * which provides user-friendly description of the attribute meaning and can be displayed to end users.
     *
     * Unlike label hint will not be generated, if its explicit declaration is omitted.
     *
     * Note, in order to inherit hints defined in the parent class, a child class needs to
     * merge the parent hints with child hints using functions such as `array_merge()`.
     *
     * @return array attribute hints (name => hint)
     * @since 2.0.4
     */
    public function attributeHints()
    {
        return [];
    }

    /**
     * Performs the data validation.
     *
     * This method executes the validation rules applicable to the current [[scenario]].
     * The following criteria are used to determine whether a rule is currently applicable:
     *
     * - the rule must be associated with the attributes relevant to the current scenario;
     * - the rules must be effective for the current scenario.
     *
     * This method will call [[beforeValidate()]] and [[afterValidate()]] before and
     * after the actual validation, respectively. If [[beforeValidate()]] returns false,
     * the validation will be cancelled and [[afterValidate()]] will not be called.
     *
     * Errors found during the validation can be retrieved via [[getErrors()]],
     * [[getFirstErrors()]] and [[getFirstError()]].
     *
     * @param array $attributeNames list of attribute names that should be validated.
     * If this parameter is empty, it means any attribute listed in the applicable
     * validation rules should be validated.
     * @param bool $clearErrors whether to call [[clearErrors()]] before performing validation
     * @return bool whether the validation is successful without any error.
     * @throws InvalidParamException if the current scenario is unknown.
     */
    public function validate($attributeNames = null, $clearErrors = true)
    {
		//先清除曾经的错误信息
        if ($clearErrors) {
            $this->clearErrors();
        }

		//注意，这里有个before处理，它如果返回false，无论如何都认为验证失败了。return false嘛
        if (!$this->beforeValidate()) {
            return false;
        }

		//再次获取一下所有场景
        $scenarios = $this->scenarios();
		//当前场景
        $scenario = $this->getScenario();
		//场景不匹配就抛异常
        if (!isset($scenarios[$scenario])) {
            throw new InvalidParamException("Unknown scenario: $scenario");
        }

        if ($attributeNames === null) {
            $attributeNames = $this->activeAttributes();
        }

		//遍历激活的验证对象（rules里的一行行），进行验证。这是validate()方法的核心
		//其实是经过了两层过滤：
		//1 首先获得所有验证器
		//2 再获得激活的验证器(正如函数名所写：get——Active--Validators
		//我们看到验证的核心，交给了验证器的validateAttributes方法。
		//通过查看源码知道，每个验证器都有自己的一套规范，每个验证器内部
		//都要实现验证逻辑，且在验证出错误时，把其内部把错误信息填充到model的_errors里
		//且验证器执行完内部的验证后不应有返回值
		//所以要想了解具体的20多个验证器都是如何千姿百态地实现个性验证，花点时间看看它们吧。
        foreach ($this->getActiveValidators() as $validator) {
			//看来，validateAttributes
            $validator->validateAttributes($this, $attributeNames);
        }
		//验证过还有动作，留给开发人员发挥自己特长的地方
		//只是不像beforeValidate,会影响后续代码的执行，after没有那么大的权力而已。
		//可以做一点收尾工作
        $this->afterValidate();

        return !$this->hasErrors();
    }

    /**逻辑同afterValidate()，但是这里使用自己的事件对象ModelEvent
     * This method is invoked before validation starts.
     * 默认就是触发beforeValidate事件
     * The default implementation raises a `beforeValidate` event.
     * 可以覆盖这个方法，也一定要触发事件，然后在添加自己的逻辑
     * You may override this method to do preliminary checks before validation.
     * Make sure the parent implementation is invoked so that the event can be raised.
     * @return bool whether the validation should be executed. Defaults to true.
     * 注意，这个before类的事件，在事件执行中可以修改事件对象ModelEvent，$event的isValid属性值影响程序后续的执行
     * 默认这个isValid是true
     * If false is returned, the validation will stop and the model is considered invalid.
     */
    public function beforeValidate()
    {
        $event = new ModelEvent;
        $this->trigger(self::EVENT_BEFORE_VALIDATE, $event);

        return $event->isValid;
    }

    /**触发一个事件，不是醒目的trigger，而是写在一个方法里，
     * This method is invoked after validation ends.
     * 方法的名字一般就是事件的名字
     * The default implementation raises an `afterValidate` event.
     * 我们可以覆盖这个方法，用于验证后处理
     * 但一定确保parent::afterValidate,以保证事件触发
     * You may override this method to do postprocessing after validation.
     * Make sure the parent implementation is invoked so that the event can be raised.
     */
    public function afterValidate()
    {
        $this->trigger(self::EVENT_AFTER_VALIDATE);
    }

    /**
     * 返回rules()里涉及的验证规则
     * Returns all the validators declared in [[rules()]].
     * 这个方法与getActiveValidators()方法的区别是，后者返回应用于当前场景的验证规则
     * This method differs from [[getActiveValidators()]] in that the latter
     * only returns the validators applicable to the current [[scenario]].
     *这个方法返回的是数组对象ArrayObject，所以可以通过inserting或者removing操作
     * Because this method returns an ArrayObject object, you may
     * manipulate it by inserting or removing validators (useful in model behaviors).
     * For example,
     *
     * ```php
     * $model->validators[] = $newValidator;
     * ```
     *
     * @return ArrayObject|\yii\validators\Validator[] all the validators declared in the model.
     */
    public function getValidators()
    {
        if ($this->_validators === null) {
            $this->_validators = $this->createValidators();
        }
        return $this->_validators;
    }

    /**返回当前场景下激活的验证器
     * Returns the validators applicable to the current [[scenario]].
     * 属性名参数有的话，就获得与这个属性相关的激活验证器
     * @param string $attribute the name of the attribute whose applicable validators should be returned.
     * 如果没有参数的话，所有属性的激活验证器都会返回
     * If this is null, the validators for ALL attributes in the model will be returned.
     * @return \yii\validators\Validator[] the validators applicable to the current [[scenario]].
     */
    public function getActiveValidators($attribute = null)
    {
        $validators = [];
        $scenario = $this->getScenario();
        foreach ($this->getValidators() as $validator) {
            //首先验证器是激活的，然后要验证的属性也在当前验证器将要验证的属性列表里。
			//每个验证器要验证哪些属性，在其实例化时就已经确定了
            if ($validator->isActive($scenario) && ($attribute === null || in_array($attribute, $validator->attributes, true))) {
                $validators[] = $validator;
            }
        }
        return $validators;
    }

    /**根据rules创建涉及的验证对象，返回一个集合对象（集合类？这个概念好像java中的集合类）
     * Creates validator objects based on the validation rules specified in [[rules()]].
     * 不像getValidators(),每次调用这个方法都会生成一个大对象，这个对象以数组那样包含各个验证对象
     * 因为看过append就知道了
     * Unlike [[getValidators()]], each time this method is called, a new list of validators will be returned.
     * @return ArrayObject validators
     * @throws InvalidConfigException if any validation rule configuration is invalid
     * 注意验证器是如何实例化的，实例化时传入了哪些参数。有空看看validator类吧
     */
    public function createValidators()
    {
        //ArrayObject数组对象，来自SPL,可以把这个对象当做数组来用
        $validators = new ArrayObject;
        foreach ($this->rules() as $rule) {
            if ($rule instanceof Validator) {
                $validators->append($rule);
                //正如注释所说，rule[0]是当前rule涉及的属性，rule[1]是当前rule的验证类型
            } elseif (is_array($rule) && isset($rule[0], $rule[1])) { // attributes, validator type
                //创建验证对象时传递4个参数(通过查看createValidator方法可知：
				//其实是根据传入的这四个参数，组装好定义验证器的数组，进而使用Yii::createObject()方法创建验证器
                $validator = Validator::createValidator($rule[1], $this, (array) $rule[0], array_slice($rule, 2));
                $validators->append($validator);
            } else {
                throw new InvalidConfigException('Invalid validation rule: a rule must specify both attribute names and validator type.');
            }
        }
        return $validators;
    }

    /**
     * 属性可以有自己的验证规则rule,该方法检测指定属性的rules里，是否有
     * required这个rule存在。
     * Returns a value indicating whether the attribute is required.
     * This is determined by checking if the attribute is associated with a
     * [[\yii\validators\RequiredValidator|required]] validation rule in the
     * current [[scenario]].
     *注意，当属性的rules里有required这个rule,但是还有附加的条件验证时（when），
     *也不算有required，也就是说，必须有纯洁的required，而不是带有其他条件的required
     * Note that when the validator has a conditional validation applied using
     * [[\yii\validators\RequiredValidator::$when|$when]] this method will return
     * `false` regardless of the `when` condition because it may be called be
     * before the model is loaded with data.
     *
     * @param string $attribute attribute name
     * @return bool whether the attribute is required
     */
    public function isAttributeRequired($attribute)
    {
        foreach ($this->getActiveValidators($attribute) as $validator) {
            if ($validator instanceof RequiredValidator && $validator->when === null) {
                return true;
            }
        }
        return false;
    }

    /**检测某个属性是否在安全属性中
     * Returns a value indicating whether the attribute is safe for massive assignments.
     * @param string $attribute attribute name
     * @return bool whether the attribute is safe for massive assignments
     * @see safeAttributes()
     * 激活属性和安全属性都是属性集合，两种不同的属性集合
     */
    public function isAttributeSafe($attribute)
    {
        return in_array($attribute, $this->safeAttributes(), true);
    }

    /**检测某个属性是否在激活属性中，注意in_array第三个参数是true，严格模式
     * Returns a value indicating whether the attribute is active in the current scenario.
     * @param string $attribute attribute name
     * @return bool whether the attribute is active in the current scenario
     * @see activeAttributes()
     */
    public function isAttributeActive($attribute)
    {
        return in_array($attribute, $this->activeAttributes(), true);
    }

    /**返回指定属性的label值
     * label是前端HTML里的概念（一个规范的表单项应该含有label和input）
     * label一般在model的attributeLabels()方法里定义，如果没有的话，就临时动态生成属性的label
     * Returns the text label for the specified attribute.
     * @param string $attribute the attribute name
     * @return string the attribute label
     * @see generateAttributeLabel()
     * @see attributeLabels()
     */
    public function getAttributeLabel($attribute)
    {
        $labels = $this->attributeLabels();
        return isset($labels[$attribute]) ? $labels[$attribute] : $this->generateAttributeLabel($attribute);
    }

    /**Hint提示，是html里所谓的title属性吗？暂不理会
     * Returns the text hint for the specified attribute.
     * @param string $attribute the attribute name
     * @return string the attribute hint
     * @see attributeHints()
     * @since 2.0.4
     */
    public function getAttributeHint($attribute)
    {
        $hints = $this->attributeHints();
        return isset($hints[$attribute]) ? $hints[$attribute] : '';
    }

    /**检测是否有错误信息
     * Returns a value indicating whether there is any validation error.
     * 不传参数表示检测是否有任何属性的错误信息，传参数表示检测指定属性是否有错误信息
     * @param string|null $attribute attribute name. Use null to check all attributes.
     * @return bool whether there is any error.
     */
    public function hasErrors($attribute = null)
    {
        return $attribute === null ? !empty($this->_errors) : isset($this->_errors[$attribute]);
    }

    /**返回所有属性的所有错误信息或者单独一个属性的所有错误信息
     * Returns the errors for all attributes or a single attribute.
     * 参数是属性名时获得该属性的所有错误信息，为null时获得所有属性的所有错误信息
     * @param string $attribute attribute name. Use null to retrieve errors for all attributes.
     * 没有错误就返回空数组
     * @property array An array of errors for all attributes. Empty array is returned if no error.
     * 返回结果是二维数组，就像下面的格式那样
     * The result is a two-dimensional array. See [[getErrors()]] for detailed description.
     * @return array errors for all attributes or the specified attribute. Empty array is returned if no error.
     * Note that when returning errors for all attributes, the result is a two-dimensional array, like the following:
     *
     * ```php
     * [
     *     'username' => [
     *         'Username is required.',
     *         'Username must contain only word characters.',
     *     ],
     *     'email' => [
     *         'Email address is invalid.',
     *     ]
     * ]
     * ```
     *
     * @see getFirstErrors()
     * @see getFirstError()
     */
    public function getErrors($attribute = null)
    {
        if ($attribute === null) {
            return $this->_errors === null ? [] : $this->_errors;
        }
        return isset($this->_errors[$attribute]) ? $this->_errors[$attribute] : [];
    }

    /**返回每个属性错误信息的第一条
     * Returns the first error of every attribute in the model.
     * 返回的数组key是属性名，value是对应这个属性的第一条错误信息
     * 没有错误时就返回空数组
     * @return array the first errors. The array keys are the attribute names, and the array
     * values are the corresponding error messages. An empty array will be returned if there is no error.
     * @see getErrors()
     * @see getFirstError()
     */
    public function getFirstErrors()
    {
        if (empty($this->_errors)) {
            return [];
        }

        $errors = [];
        foreach ($this->_errors as $name => $es) {
            if (!empty($es)) {
                $errors[$name] = reset($es);
            }
        }
        return $errors;
    }

    /**获取指定属性的所有错误信息中的第一条
     * Returns the first error of the specified attribute.
     * @param string $attribute attribute name.
     * @return string the error message. Null is returned if no error.
     * @see getErrors()
     * @see getFirstErrors()
     * php还有reset函数可以用
     */
    public function getFirstError($attribute)
    {
        return isset($this->_errors[$attribute]) ? reset($this->_errors[$attribute]) : null;
    }

    /**为指定的属性添加一条新的错误信息
     * Adds a new error to the specified attribute.
     * @param string $attribute attribute name
     * @param string $error new error message
     * 目前在行内验证器中使用过该方法
     */
    public function addError($attribute, $error = '')
    {
        $this->_errors[$attribute][] = $error;
    }

    /**
     * 添加一系列的错误信息
     * Adds a list of errors.
     * 数组，数组key必须是属性名，数组的value是错误信息。如果一个属性有多个错误信息，
     * @param array $items a list of errors. The array keys must be attribute names.
     * The array values should be error messages. If an attribute has multiple errors,
     * 这些错误信息必须按照数组格式存储
     * these errors must be given in terms of an array.
     * 在设置了错误信息后，可以通过getErros()方法获得错误信息
     * You may use the result of [[getErrors()]] as the value for this parameter.
     * @since 2.0.2
     */
    public function addErrors(array $items)
    {
        foreach ($items as $attribute => $errors) {
            if (is_array($errors)) {
                foreach ($errors as $error) {
                    $this->addError($attribute, $error);
                }
            } else {
                $this->addError($attribute, $errors);
            }
        }
    }

    /**清除所有属性的错误信息或者单个属性的错误信息
     * Removes errors for all attributes or a single attribute.
     * 不传参数时，就是清除所有错误信息；
     * @param string $attribute attribute name. Use null to remove errors for all attributes.
     */
    public function clearErrors($attribute = null)
    {
        if ($attribute === null) {
            $this->_errors = [];
        } else {
            unset($this->_errors[$attribute]);
        }
    }

    /**
     * Generates a user friendly attribute label based on the give attribute name.
     * This is done by replacing underscores, dashes and dots with blanks and
     * changing the first letter of each word to upper case.
     * For example, 'department_name' or 'DepartmentName' will generate 'Department Name'.
     * @param string $name the column name
     * @return string the attribute label
     */
    public function generateAttributeLabel($name)
    {
        return Inflector::camel2words($name, true);
    }

    /**
     * 返回属性值
     * Returns attribute values.
     * @param array $names list of attributes whose value needs to be returned.
     * 参数为null时，获取所有public 非static的属性
     * Defaults to null, meaning all attributes listed in [[attributes()]] will be returned.
     * If it is an array, only the attributes in the array will be returned.
     * 还可以传递第二个参数，用来排除一部分属性
     * @param array $except list of attributes whose value should NOT be returned.
     * @return array attribute values (name => value).
     */
    public function getAttributes($names = null, $except = [])
    {
        $values = [];
        if ($names === null) {
            $names = $this->attributes();
        }
        foreach ($names as $name) {
            $values[$name] = $this->$name;
        }
        foreach ($except as $name) {
            unset($values[$name]);
        }

        return $values;
    }

    /**批量设置model类的属性值
     * Sets the attribute values in a massive way.
	 * 数组，键值对形式，要设置的属性及属性值对，来源于客户端
     * @param array $values attribute values (name => value) to be assigned to the model.
	 * 布尔值，是否仅仅设置安全属性，安全属性？什么意思？什么是安全属性？默认仅仅设置安全属性。
     * @param bool $safeOnly whether the assignments should only be done to the safe attributes.
     * A safe attribute is one that is associated with a validation rule in the current [[scenario]].
     * @see safeAttributes()
     * @see attributes()
     * 注意，按照Yii官方的推荐，默认该方法在load()内部调用，所以第二个参数永远默认是true
     * 可是我们也看到setAttributes是public,故其实我们可以绕过load方法直接使用。
     */
    public function setAttributes($values, $safeOnly = true)
    {
        if (is_array($values)) {
			//获得当前场景下所有的属性,即所谓的激活属性
			//model有很多属性，但不是每次都会设置所有属性，而是会针对每个场景设置跟场景相关的属性们
			//对应当前场景下的属性，即为激活属性，不在当前场景下的属性就是非激活属性。
			//如果safeOnly不为true,则调用attribute方法，获取model的public非static属性
			//使用php内置函数array_flip是键值对互换的数组函数，因为safeAttributes，attributes方法返回的数组，元素值是属性名
            $attributes = array_flip($safeOnly ? $this->safeAttributes() : $this->attributes());
			//遍历来自客户端的属性=》属性值对儿 只设置$attributes中存在的属性
            foreach ($values as $name => $value) {
                if (isset($attributes[$name])) {
                    $this->$name = $value;
				//如果外来键值对中出现了非安全属性，就去打个日志而已。
                } elseif ($safeOnly) {
                    $this->onUnsafeAttribute($name, $value);
                }
            }
        }
    }

    /**当在安全属性机制中发现非安全属性时，调用这个方法
     * This method is invoked when an unsafe attribute is being massively assigned.
	 * 默认的实现是打个warning级别的日志而已。其他没啥。
     * The default implementation will log a warning message if YII_DEBUG is on.
     * It does nothing otherwise.
     * @param string $name the unsafe attribute name
     * @param mixed $value the attribute value
     * 这个方法也是public，故我们也可以覆盖之
     */
    public function onUnsafeAttribute($name, $value)
    {
        if (YII_DEBUG) {
            Yii::trace("Failed to set unsafe attribute '$name' in '" . get_class($this) . "'.", __METHOD__);
        }
    }

    /**返回Model当前使用的场景
     * Returns the scenario that this model is used in.
     *场景影响了验证的执行以及哪些属性能被批量赋值
     * Scenario affects how validation is performed and which attributes can
     * be massively assigned.
     *如果前期没有设置场景，则是默认场景
     * @return string the scenario that this model is in. Defaults to [[SCENARIO_DEFAULT]].
     */
	 //一般在实例化Model对象的时候，传入初始化参数，通过yii2共有对象的_set()魔术方法来设置当前场景的，
	 //默认就是default
    public function getScenario()
    {
        return $this->_scenario;
    }

    /**为model设置当前场景。注意，可以胡乱设置，不检查场景是否已经定义（存在）与否
     * Sets the scenario for the model.
     * Note that this method does not check if the scenario exists or not.
     * validate()方法里会检测
     * The method [[validate()]] will perform this check.
     * @param string $value the scenario that this model is in.
     */
    public function setScenario($value)
    {
        $this->_scenario = $value;
    }

    /**返回安全属性集合(应用到load方法时，load方法就是所谓的批量赋值），massively assigned
	* 所谓的安全属性集合，安全属性指的是属于当前场景的，但是又排除那些有"!"前缀的属性们
	* 注意与激活属性概念的区分
     * Returns the attribute names that are safe to be massively assigned in the current scenario.
     * @return string[] safe attribute names
     */
    public function safeAttributes()
    {
		//获取当前的场景（一般在实例化model时传入参数指定本次验证是哪个场景）
        $scenario = $this->getScenario();
		//整理不同场景下要处理的属性  $scenarios是二维数组
        $scenarios = $this->scenarios();

		//当前场景应该属于总场景中的一员，不是的话就直接退出
        if (!isset($scenarios[$scenario])) {
            return [];
        }
		//场景合格的话，那就把这个场景下的属性都返回去吧。但是还有一个小微调
		//以进一步缩小范围
        $attributes = [];
        foreach ($scenarios[$scenario] as $attribute) {
			//如果属性名是类似'!storage_name'这样有’!'前缀的排除;
            if ($attribute[0] !== '!' && !in_array('!' . $attribute, $scenarios[$scenario])) {
                $attributes[] = $attribute;
            }
        }

        return $attributes;
    }

    /**返回当前场景下的激活属性（注意与safeAttributes()方法的区别）
     * 对比上述safeAttributes()方法的异同，激活属性是当前场景下的全部属性，带有'!'前缀的也返回
     * 但是safeAttributes()就会排除带有'!'前缀的
     * Returns the attribute names that are subject to validation in the current scenario.
     * @return string[] safe attribute names
     */
    public function activeAttributes()
    {
        $scenario = $this->getScenario();
        $scenarios = $this->scenarios();
        if (!isset($scenarios[$scenario])) {
            return [];
        }
        $attributes = $scenarios[$scenario];
		//为什么要把属性前面的!号去掉呢？？？
        foreach ($attributes as $i => $attribute) {
            if ($attribute[0] === '!') {
                $attributes[$i] = substr($attribute, 1);
            }
        }

        return $attributes;
    }

    /**
	 * 根据客户端传入的数据，填充model对象
     * Populates the model with input data.
     *这个方法其实是$model->attributes=$_POST['FormName']的快捷方式
     * This method provides a convenient shortcut for:
     *
     * ```php
     * if (isset($_POST['FormName'])) {
     *     $model->attributes = $_POST['FormName'];
     *     if ($model->save()) {
     *         // handle success
     *     }
     * }
     * ```
     *
     * which, with `load()` can be written as:
     *
     * ```php
     * if ($model->load($_POST) && $model->save()) {
     *     // handle success
     * }
     * ```
     *因为Yii框架所有含有表单的视图页，所有表单项默认都是以模型名为下标的二维数组，如果load时没有给出第二个参数，Yii自动通过模型的formName()获取。如果第二个参数是空字符串，则从$_POST一维数组填充model对象。
	 *
     * `load()` gets the `'FormName'` from the model's [[formName()]] method (which you may override), unless the
     * `$formName` parameter is given. If the form name is empty, `load()` populates the model with the whole of `$data`,
     * instead of `$data['FormName']`.
     *注意，load虽说是填充，但不是单纯地$this->attributes=$_POST。而是内部调用setAttributes()方法完成填充功能。
     * Note, that the data being populated is subject to the safety check by [[setAttributes()]].
     * 数组参数，典型的是$_POST,$_GET
     * @param array $data the data array to load, typically `$_POST` or `$_GET`.
	 * 字符串，表单名，为null时内部调用$this->formName()获取
     * @param string $formName the form name to use to load the data into the model.
     * If not set, [[formName()]] is used.
	 * 能填充就返回true,不能填充就返回false。并不验证，仅仅判断能否填充
	 * 能填充和填充了哪些属性是两个概念，填充一个可以说能填充，填充10个也可以说能填充。
	 * 只要最初的数据来源格式合适就认为可以填充（格式以form名为下标的二维数组，一维数组也可），除这两种
	 * 情况外都返回false
     * @return bool whether `load()` found the expected form in `$data`.
	 * setAttribute有可能是当前场景的激活属性们（默认），也可能是当前模型的public非static属性
	 * 总结一句话：Yii框架通过load方法，默认是为当前场景的激活属性们填充属性值
     */
    public function load($data, $formName = null)
    {
        $scope = $formName === null ? $this->formName() : $formName;
        if ($scope === '' && !empty($data)) {
            $this->setAttributes($data);

            return true;
        } elseif (isset($data[$scope])) {
            $this->setAttributes($data[$scope]);

            return true;
        }
        return false;
    }

    /**
     * Populates a set of models with the data from end user.
     * This method is mainly used to collect tabular data input.
     * The data to be loaded for each model is `$data[formName][index]`, where `formName`
     * refers to the value of [[formName()]], and `index` the index of the model in the `$models` array.
     * If [[formName()]] is empty, `$data[index]` will be used to populate each model.
     * The data being populated to each model is subject to the safety check by [[setAttributes()]].
     * @param array $models the models to be populated. Note that all models should have the same class.
     * @param array $data the data array. This is usually `$_POST` or `$_GET`, but can also be any valid array
     * supplied by end user.
     * @param string $formName the form name to be used for loading the data into the models.
     * If not set, it will use the [[formName()]] value of the first model in `$models`.
     * This parameter is available since version 2.0.1.
     * @return bool whether at least one of the models is successfully populated.
     */
    public static function loadMultiple($models, $data, $formName = null)
    {
        if ($formName === null) {
            /* @var $first Model */
            $first = reset($models);
            if ($first === false) {
                return false;
            }
            $formName = $first->formName();
        }

        $success = false;
        foreach ($models as $i => $model) {
            /* @var $model Model */
            if ($formName == '') {
                if (!empty($data[$i])) {
                    $model->load($data[$i], '');
                    $success = true;
                }
            } elseif (!empty($data[$formName][$i])) {
                $model->load($data[$formName][$i], '');
                $success = true;
            }
        }

        return $success;
    }

    /**
     * Validates multiple models.
     * This method will validate every model. The models being validated may
     * be of the same or different types.
     * @param array $models the models to be validated
     * @param array $attributeNames list of attribute names that should be validated.
     * If this parameter is empty, it means any attribute listed in the applicable
     * validation rules should be validated.
     * @return bool whether all models are valid. False will be returned if one
     * or multiple models have validation error.
     */
    public static function validateMultiple($models, $attributeNames = null)
    {
        $valid = true;
        /* @var $model Model */
        foreach ($models as $model) {
            $valid = $model->validate($attributeNames) && $valid;
        }

        return $valid;
    }

    /**返回字段列表,不太懂
     * Returns the list of fields that should be returned by default by [[toArray()]] when no specific fields are specified.
     *
     * A field is a named element in the returned array by [[toArray()]].
     *
     * This method should return an array of field names or field definitions.
     * If the former, the field name will be treated as an object property name whose value will be used
     * as the field value. If the latter, the array key should be the field name while the array value should be
     * the corresponding field definition which can be either an object property name or a PHP callable
     * returning the corresponding field value. The signature of the callable should be:
     *
     * ```php
     * function ($model, $field) {
     *     // return field value
     * }
     * ```
     *
     * For example, the following code declares four fields:
     *
     * - `email`: the field name is the same as the property name `email`;
     * - `firstName` and `lastName`: the field names are `firstName` and `lastName`, and their
     *   values are obtained from the `first_name` and `last_name` properties;
     * - `fullName`: the field name is `fullName`. Its value is obtained by concatenating `first_name`
     *   and `last_name`.
     *
     * ```php
     * return [
     *     'email',
     *     'firstName' => 'first_name',
     *     'lastName' => 'last_name',
     *     'fullName' => function ($model) {
     *         return $model->first_name . ' ' . $model->last_name;
     *     },
     * ];
     * ```
     *
     * In this method, you may also want to return different lists of fields based on some context
     * information. For example, depending on [[scenario]] or the privilege of the current application user,
     * you may return different sets of visible fields or filter out some fields.
     *
     * The default implementation of this method returns [[attributes()]] indexed by the same attribute names.
     *
     * @return array the list of field names or field definitions.
     * @see toArray()
     */
    public function fields()
    {
        $fields = $this->attributes();
        //第一个数组的值作为key，第二个数组的值作为value
        return array_combine($fields, $fields);
    }

    /**返回一个迭代器
     * Returns an iterator for traversing the attributes in the model.
     * This method is required by the interface [[\IteratorAggregate]].
     * @return ArrayIterator an iterator for traversing the items in the list.
     */
    public function getIterator()
    {
        $attributes = $this->getAttributes();
        return new ArrayIterator($attributes);
    }

    /**以下的四个方法，是一个模型类实现ArrayAccess接口必须要实现的方法吧
     * Returns whether there is an element at the specified offset.
     * This method is required by the SPL interface [[\ArrayAccess]].
     * It is implicitly called when you use something like `isset($model[$offset])`.
     * @param mixed $offset the offset to check on.
     * @return bool whether or not an offset exists.
     */
    public function offsetExists($offset)
    {
        return isset($this->$offset);
    }

    /**
     * Returns the element at the specified offset.
     * This method is required by the SPL interface [[\ArrayAccess]].
     * It is implicitly called when you use something like `$value = $model[$offset];`.
     * @param mixed $offset the offset to retrieve element.
     * @return mixed the element at the offset, null if no element is found at the offset
     */
    public function offsetGet($offset)
    {
        return $this->$offset;
    }

    /**
     * Sets the element at the specified offset.
     * This method is required by the SPL interface [[\ArrayAccess]].
     * It is implicitly called when you use something like `$model[$offset] = $item;`.
     * @param int $offset the offset to set element
     * @param mixed $item the element value
     */
    public function offsetSet($offset, $item)
    {
        $this->$offset = $item;
    }

    /**
     * Sets the element value at the specified offset to null.
     * This method is required by the SPL interface [[\ArrayAccess]].
     * It is implicitly called when you use something like `unset($model[$offset])`.
     * @param mixed $offset the offset to unset element
     */
    public function offsetUnset($offset)
    {
        $this->$offset = null;
    }
}
