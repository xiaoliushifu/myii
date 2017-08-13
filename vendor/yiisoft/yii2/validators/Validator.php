<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\validators;

use Yii;
use yii\base\Component;
use yii\base\NotSupportedException;

/**Validator是所有验证器类的基类
 * Validator is the base class for all validators.
 *子类应该覆盖validateValue或validateAttribute方法以提供实际的验证逻辑。
 * Child classes should override the [[validateValue()]] and/or [[validateAttribute()]] methods to provide the actual
 * 子类也可以覆盖clientValidateAttribute()方法以支持客户端验证
 * logic of performing data validation. Child classes may also override [[clientValidateAttribute()]]
 * to provide client-side validation support.
 *Validator基类声明了一套内置的验证器，这些用别名列在下面：
 * Validator declares a set of [[builtInValidators|built-in validators]] which can
 * be referenced using short names. They are listed as follows:
 *

 
 
 
 
 
 * - `boolean`: [[BooleanValidator]]
 * - `captcha`: [[\yii\captcha\CaptchaValidator]]
 * - `compare`: [[CompareValidator]]
 * - `date`: [[DateValidator]]
 * - `datetime`: [[DateValidator]]
 * - `time`: [[DateValidator]]
 * - `default`: [[DefaultValueValidator]]
 * - `double`: [[NumberValidator]]
 * - `each`: [[EachValidator]]
 * - `email`: [[EmailValidator]]
 * - `exist`: [[ExistValidator]]
 * - `file`: [[FileValidator]]
 * - `filter`: [[FilterValidator]]
 * - `image`: [[ImageValidator]]
 * - `in`: [[RangeValidator]]
 * - `integer`: [[NumberValidator]]
 * - `match`: [[RegularExpressionValidator]]
 * - `required`: [[RequiredValidator]]
 * - `safe`: [[SafeValidator]]
 * - `string`: [[StringValidator]]
 * - `trim`: [[FilterValidator]]
 * - `unique`: [[UniqueValidator]]
 * - `url`: [[UrlValidator]]
 * - `ip`: [[IpValidator]]
 * 这些验证器的详细使用，看guide article
 * For more details and usage information on Validator, see the [guide article on validators](guide:input-validation).
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class Validator extends Component
{
    /**
     * @var array list of built-in validators (name => class or configuration)
     */
    public static $builtInValidators = [
        'boolean' => 'yii\validators\BooleanValidator',
        'captcha' => 'yii\captcha\CaptchaValidator',
        'compare' => 'yii\validators\CompareValidator',
        'date' => 'yii\validators\DateValidator',
        'datetime' => [
            'class' => 'yii\validators\DateValidator',
            'type' => DateValidator::TYPE_DATETIME,
        ],
        'time' => [
            'class' => 'yii\validators\DateValidator',
            'type' => DateValidator::TYPE_TIME,
        ],
        'default' => 'yii\validators\DefaultValueValidator',
        'double' => 'yii\validators\NumberValidator',
        'each' => 'yii\validators\EachValidator',
        'email' => 'yii\validators\EmailValidator',
        'exist' => 'yii\validators\ExistValidator',
        'file' => 'yii\validators\FileValidator',
        'filter' => 'yii\validators\FilterValidator',
        'image' => 'yii\validators\ImageValidator',
        'in' => 'yii\validators\RangeValidator',
        'integer' => [
            'class' => 'yii\validators\NumberValidator',
            'integerOnly' => true,
        ],
        'match' => 'yii\validators\RegularExpressionValidator',
        'number' => 'yii\validators\NumberValidator',
        'required' => 'yii\validators\RequiredValidator',
        'safe' => 'yii\validators\SafeValidator',
        'string' => 'yii\validators\StringValidator',
        'trim' => [
            'class' => 'yii\validators\FilterValidator',
            'filter' => 'trim',
            'skipOnArray' => true,
        ],
        'unique' => 'yii\validators\UniqueValidator',
        'url' => 'yii\validators\UrlValidator',
        'ip' => 'yii\validators\IpValidator',
    ];
    /**
	 * 数组或者字符串，存储当前验证器要验证的属性，在初始化验证器时赋值
     * @var array|string attributes to be validated by this validator. For multiple attributes,
	 * 多个属性时，请使用数组，单个属性时，使用字符串或者数组都行
     * please specify them as an array; for single attribute, you may use either a string or an array.
     */
    public $attributes = [];
    /**字符串类型，用户自定义的错误信息，格式如下，有有一些可以填充的placeholder
     * @var string the user-defined error message. It may contain the following placeholders which
     * will be replaced accordingly by the validator:
     *属性的标签
     * - `{attribute}`: the label of the attribute being validated
	 * 属性的值
     * - `{value}`: the value of the attribute being validated
     *注意，有些验证器可能会引入新的属性封装错误信息（具体看看每个子类验证器有关错误信息的处理）
     * Note that some validators may introduce other properties for error messages used when specific
	 * 请参考每个验证器的API详细了解这些特性
     * validation conditions are not met. Please refer to individual class API documentation for details
	 * 按照惯例，当最重要的验证条件验证了模型的某个属性时，message属性将会存储主要的错误信息
     * about these properties. By convention, this property represents the primary error message
     * used when the most important validation condition is not met.
     */
    public $message;
    /**数组或者字符串，当前验证器应用的场景。多场景请指定为数组，单个场景用字符串或数组都行
     * @var array|string scenarios that the validator can be applied to. For multiple scenarios,
     * please specify them as an array; for single scenario, you may use either a string or an array.
     */
    public $on = [];
    /**数组或字符串，当前验证器应该排除在这些场景之外。多场景请指定为数组，单个场景用字符串或数组都行
     * @var array|string scenarios that the validator should not be applied to. For multiple scenarios,
     * please specify them as an array; for single scenario, you may use either a string or an array.
     */
    public $except = [];
    /**boolean值，某个属性在被其他验证器已经验证为失败的情况下，是否还会被当前验证器验证，
     * @var bool whether this validation rule should be skipped if the attribute being validated
	 * 默认是true,意味着前面的验证器把某个属性验证失败的情况下，轮到当前验证器再验证这个属性时就跳过不再验证。
     * already has some validation error according to some previous rules. Defaults to true.
	 * 这里有一层含义是说，模型类的某些属性有可能会被多个验证器验证，那么这些验证器肯定有个验证的先后顺序
	 * 当靠前的验证器验证通过时，没啥说的；当验证不通过时，也就是说验证出错时，也不会立即退出整个验证流程，而是靠后的验证器会继续验证，那么skipOnErros就是控制靠后的验证器是否略过这些属性不再验证，应该继续验证流程去验证其他属性
	 *
     */
    public $skipOnError = true;
    /**boolean值，当属性值是null或者emtpy时，是否跳过它而不进行验证。
     * @var bool whether this validation rule should be skipped if the attribute value
     * is null or an empty string.
	 *默认也是跳过
     */
    public $skipOnEmpty = true;
    /**boolean,是否开启客户端验证
     * @var bool whether to enable client-side validation for this validator.
	 * 实际的客户端验证代码，是由clientValidateAttribute()方法返回的。
     * The actual client-side validation is done via the JavaScript code returned
	 * 如果方法里并没有写客户端代码，那么即使这个属性设置为true，客户端验证还是开展不下去。
     * by [[clientValidateAttribute()]]. If that method returns null, even if this property
     * is true, no client-side validation will be done by this validator.
	 * 也就是说，$enableClientValidation这仅仅是个开关而已，真正的验证逻辑JS代码还得有才行
     */
    public $enableClientValidation = true;
    /**回调函数，用来替换默认的isEmpty()方法。
     * @var callable a PHP callable that replaces the default implementation of [[isEmpty()]].
	 * 如果该属性为空，那就用默认的isEmpty()方法判空。
     * If not set, [[isEmpty()]] will be used to check if a value is empty. The signature
	 * 函数只包含一个参数，函数的返回值是boolean,表名当前的属性是否是empty
     * of the callable should be `function ($value)` which returns a boolean indicating
     * whether the value is empty.
	 * 这个也很好，把判空的逻辑留给开发人员来写，使得程序设计多了些灵活性
     */
    public $isEmpty;
    /**这个属性的值是个回调函数，返回boolean,来决定当前的验证器是否应用于关联的属性（这就是所谓的“条件验证”）
     * @var callable a PHP callable whose return value determines whether this validator should be applied.
	 * 函数的签名是function($model,$attribute),其中$attribute主要是当前正在验证的属性，函数返回值应该是boolean.
     * The signature of the callable should be `function ($model, $attribute)`, where `$model` and `$attribute`
     * refer to the model and the attribute currently being validated. The callable should return a boolean value.
     * 这个属性的回调函数，主要为了支持服务端【条件验证】
     * This property is mainly provided to support conditional validation on the server-side.
	 * 如果这个属性为空（也就是说无需条件验证），则当前验证器总是验证关联的属性
     * If this property is not set, this validator will be always applied on the server-side.
     * 回调函数的例子看下面，当country选择了USA的时候，验证开启！
     * The following example will enable the validator only when the country currently selected is USA:
     *
     * ```php
     * function ($model) {
     *     return $model->country == Country::USA;
     * }
     * ```
     *
     * @see whenClient
     */
    public $when;
    /**字符串，一个JS函数，返回值决定了是否进行客户端验证（所谓的客户端【条件验证】)
     * @var string a JavaScript function name whose return value determines whether this validator should be applied
	 * 函数的签名是function(attribute,value)。attribute就是当前被验证的属性，value就是当前属性的值
     * on the client-side. The signature of the function should be `function (attribute, value)`, where
	 * 最好去看网站
     * `attribute` is an object describing the attribute being validated (see [[clientValidateAttribute()]])
     * and `value` the current value of the attribute.
     *这个属性其实提供了实现客户端【条件验证】的机会
     * This property is mainly provided to support conditional validation on the client-side.
	 *如果属性为空，则总是在客户端验证
     * If this property is not set, this validator will be always applied on the client-side.
     *下面的例子当country选择了USA的时候，就会动态开启客户端验证。
     * The following example will enable the validator only when the country currently selected is USA:
     *
     * ```javascript
     * function (attribute, value) {
     *     return $('#country').val() === 'USA';
     * }
     * ```
     *
     * @see when
     */
    public $whenClient;


    /**
     * 创建一个验证器对象
     * Creates a validator object.
     * 参数1指出验证类型，可以有四种情况：
     * @param string|\Closure $type the validator type. This can be either:
	 *  * 内置验证器类型（required,string等在builtinValidators列表列出来的那些）
     *  * a built-in validator name listed in [[builtInValidators]];
	 *  * 一个模型类的方法名
     *  * a method name of the model class;
	 *  * 一个匿名函数
     *  * an anonymous function;
	 *  * 一个验证类的类名
     *  * a validator class name.
     *  参数2，数据模型对象（仅仅在$model->hasMethod()这用了一次）
     * @param \yii\base\Model $model the data model to be validated.
     * 参数3，数组或字符串，给出当前要验证的属性，字符串时存储逗号分隔的属性名
     * @param array|string $attributes list of attributes to be validated. This can be either an array of
     * the attribute names or a string of comma-separated attribute names.
     * 参数4 初始化这个验证器对象的其他属性（这些属性由子类根据自己的验证情况来实现）
     * @param array $params initial values to be applied to the validator properties.
     * @return Validator the validator
	 * 何时才需要创建验证器呢？当然在Model中了，顺着Model的validate()方法往下走，你会发现的！
     */
    public static function createValidator($type, $model, $attributes, $params = [])
    {
        $params['attributes'] = $attributes;
		//熟悉不？行内验证器，直接在rules里写验证函数（就是Closure）；或者写个函数名在rules中，函数的实现
		//则写成模型的方法（hasMethod())
        if ($type instanceof \Closure || $model->hasMethod($type)) {
            // method-based validator
            $params['class'] = __NAMESPACE__ . '\InlineValidator';
            $params['method'] = $type;
        } else {
			//是数组的形式，属于内置验证器,这种情况最多
            if (isset(static::$builtInValidators[$type])) {
                $type = static::$builtInValidators[$type];
            }
			//数组的话，会带一部分验证器的特殊属性配置，这里主要指time,datetime,integer等内置验证器
            if (is_array($type)) {
                $params = array_merge($type, $params);
            } else {
                $params['class'] = $type;
            }
        }
        //底层还是使用Yii助手实例化验证器
		//多么的熟悉啊，怎么？不信？你看$params参数就知道了
		//有class，可以知道具体实例化哪个类【这个参数最关键】
		//有attributes，实例化后可以配置其attributes属性
		//有method，行内验证器的method属性配置
        return Yii::createObject($params);
    }

    /**
	 * init方法我们已经知道，所有Object子类实例化时，在构造函数内部会调用init方法初始化属性
	 * 而且一般会先执行parent::init()
	 * 所以，验证器也一样，在实例化时就确定了这个验证器的三个属性
	 * 这三个属性分别表示将来能验证哪些属性，适用哪些场景，排除哪些场景
	 * 方便子类验证器属性的初始化（子类只需初始化自己特有的属性，这三个公共属性只需parent::init()即可，方便吧）
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        $this->attributes = (array) $this->attributes;
        $this->on = (array) $this->on;
        $this->except = (array) $this->except;
    }

    /**
	 * 验证指定的对象
     * Validates the specified object.
	 * $model是正被验证的数据模型
     * @param \yii\base\Model $model the data model being validated
	 * 
	 * $attributes是当前场景下所有的激活属性，数组或null
     * @param array|null $attributes the list of attributes to be validated.
	 * 注意，既然存储所有激活属性，就有可能出现某个激活属性不在当前验证器关联的属性列表里，或者某个属性以"!"开头
     * Note that if an attribute is not associated with the validator, or is is prefixed with `!` char - it will be
	 * 这两种属性都忽略。如果attributes是null,那么只验证当前验证器关联的模型属性（这些模型属性存储在验证器的attributes属性中）
     * ignored. If this parameter is null, every attribute listed in [[attributes]] will be validated.
     */
    public function validateAttributes($model, $attributes = null)
    {
        if (is_array($attributes)) {
            $newAttributes = [];
			//筛选与当前验证器关联的属性，包含!前缀的也算
            foreach ($attributes as $attribute) {
                if (in_array($attribute, $this->attributes) || in_array('!' . $attribute, $this->attributes)) {
                    $newAttributes[] = $attribute;
                }
            }
            $attributes = $newAttributes;
		
        } else {
            $attributes = [];
			//从当前验证器的属性列表里，进行过滤，若某些属性前带有前缀的'!'，则去掉这个前缀继续包含使用
            foreach ($this->attributes as $attribute) {
                $attributes[] = $attribute[0] === '!' ? substr($attribute, 1) : $attribute;
            }
        }
		//遍历过滤后的属性，一个个属性在validateAttribute()方法里验证
		//skip,when等过滤条件，就是上述我们所说的：已经验证出错时是否验证，为空时是否验证，以及判空逻辑
		//这些过滤条件筛选过的才去执行validateAttribute()
        foreach ($attributes as $attribute) {
            $skip = $this->skipOnError && $model->hasErrors($attribute)
                || $this->skipOnEmpty && $this->isEmpty($model->$attribute);
			//在不略过时下，是否有条件验证
            if (!$skip) {
                if ($this->when === null || call_user_func($this->when, $model, $attribute)) {
					//层层过滤后，这才去执行验证
                    $this->validateAttribute($model, $attribute);
                }
            }
        }
    }

    /**
	 * 验证单独的一个属性
     * Validates a single attribute.
	 * 每个验证器子类，应该实现这个方法，来提供具体的验证逻辑
     * Child classes must implement this method to provide the actual validation logic.
	 * 参数1 是要验证的模型
     * @param \yii\base\Model $model the data model to be validated
	 * 参数2 是要验证的一个属性名
     * @param string $attribute the name of the attribute to be validated.
     */
    public function validateAttribute($model, $attribute)
    {
        $result = $this->validateValue($model->$attribute);
        if (!empty($result)) {
            $this->addError($model, $attribute, $result[0], $result[1]);
        }
    }

    /**
	 * 验证一个给定的值
     * Validates a given value.
	 * 这个方法允许开发人员在脱离数据模型上下文之外来验证一个值
     * You may use this method to validate a value out of the context of a data model.
	 * 参数1 要验证的值
     * @param mixed $value the data value to be validated.
	 * 参数2 验证不通过时，填充错误信息，引用类型
     * @param string $error the error message to be returned, if the validation fails.
     * @return bool whether the data is valid.
     */
    public function validate($value, &$error = null)
    {
		//看出来，实际的验证逻辑，最终的验证逻辑还是在validateValue方法里
        $result = $this->validateValue($value);
        if (empty($result)) {
            return true;
        }

		//错误信息的处理，涉及国际化问题
        list($message, $params) = $result;
        $params['attribute'] = Yii::t('yii', 'the input value');
        if (is_array($value)) {
            $params['value'] = 'array()';
        } elseif (is_object($value)) {
            $params['value'] = 'object';
        } else {
            $params['value'] = $value;
        }
        $error = Yii::$app->getI18n()->format($message, $params, Yii::$app->language);

        return false;
    }

    /**
	 * 验证一个属性的值的最终逻辑，且只有在最终逻辑时才会产生错误信息
	 * 该方法是protected，可见不是直接在外部调用，而且由内部其他的方法调用
     * Validates a value.
	 * 一个验证器子类应该在model的上下文之外，实现这个方法
     * A validator class can implement this method to support data validation out of the context of a data model.
	 * 参数$value 是具体的被验证的值，不是属性，而是属性的值
     * @param mixed $value the data value to be validated.
	 *
	 * 返回错误信息，数组或null,将来用于放到error的message里
     * @return array|null the error message and the parameters to be inserted into the error message.
	 * 如果验证无误(验证通过）的话，应该返回null
     * Null should be returned if the data is valid.
	 * 如果当前验证器不支持验证这个$value值，抛出异常
	 * 从Model里看出来，调用栈是validateAttributes--->validateAttribute----->validateValue
	 * 当然，这是Yii框架已经设计好的验证栈，我们还可以在模型之外单独调用验证器的validate方法来执行验证
     * @throws NotSupportedException if the validator does not supporting data validation without a model
     */
    protected function validateValue($value)
    {
        throw new NotSupportedException(get_class($this) . ' does not support validateValue().');
    }

    /**
	 * 给出JavaScript代码，用来执行客户端验证
     * Returns the JavaScript needed for performing client-side validation.
     * 调用getClientOptions()来生成选项数组在客户端验证时使用
     * Calls [[getClientOptions()]] to generate options array for client-side validation.
     * 如果当前验证器支持客户端验证时，开发人员可以覆盖这个方法
     * You may override this method to return the JavaScript validation code if
     * the validator can support client-side validation.
     * 下面列出了在客户端验证时预定义的几个变量
     * The following JavaScript variables are predefined and can be used in the validation code:
     * attribute 是JQuery对象，表示要验证的属性
     * - `attribute`: an object describing the the attribute being validated.
	 * value 是被验证的值
     * - `value`: the value being validated.
	 * message 一个数组，存储当前属性的错误信息
     * - `messages`: an array used to hold the validation error messages for the attribute.
	 *  deferred,数组，存储了deferred对象用来进行异步验证（ajax吧）
     * - `deferred`: an array used to hold deferred objects for asynchronous validation
	 * $form 是Jquery对象，包含所有的表单元素
     * - `$form`: a jQuery object containing the form element
     * 
	 * 这个attribute对象包含下述几个属性
     * The `attribute` object contains the following properties:
	 * id属性，存储HTML标签的ID属性值
     * - `id`: a unique ID identifying the attribute (e.g. "loginform-username") in the form
	 * name属性，存储HTML标签的name属性值
     * - `name`: attribute name or expression (e.g. "[0]content" for tabular input)
	 * container 是当前input容器的Jquery选择器
     * - `container`: the jQuery selector of the container of the input field
	 * input  是当前input的Jquery选择器
     * - `input`: the jQuery selector of the input field under the context of the form
	 * error  在当前容器下的，用来显示错误信息的HTML标签里的Jquery选择器
     * - `error`: the jQuery selector of the error tag under the context of the container
	 *status  是input域的状态，0是空，意味着是没输入之前；1验证通过了；2待验证；3通过验证
     * - `status`: status of the input field, 0: empty, not entered before, 1: validated, 2: pending validation, 3: validating
     *
     * @param \yii\base\Model $model the data model being validated
     * @param string $attribute the name of the attribute to be validated.
     * @param \yii\web\View $view the view object that is going to be used to render views or view files
     * containing a model form with this validator applied.
	 * 如果不支持客户端脚本验证，则返回Null
     * @return string the client-side validation script. Null if the validator does not support
     * client-side validation.
     * @see getClientOptions()
     * @see \yii\widgets\ActiveForm::enableClientValidation
     */
    public function clientValidateAttribute($model, $attribute, $view)
    {
        return null;
    }

    /**
	 * 返回客户端验证选项
     * Returns the client-side validation options.
	 * 这个方法通常在clientValidateAttribute()里调用
     * This method is usually called from [[clientValidateAttribute()]]. You may override this method to modify options
	 * 开发人员可以覆盖这个方法，以返回验证器的自定义的选项
     * that will be passed to the client-side validation.
     * @param \yii\base\Model $model the model being validated
     * @param string $attribute the attribute name being validated
     * @return array the client-side validation options
     * @since 2.0.11
     */
    public function getClientOptions($model, $attribute)
    {
        return [];
    }

    /**返回一个boolean,表明当前验证器是否在$scenario场景下是激活的
     * Returns a value indicating whether the validator is active for the given scenario and attribute.
     * 如何认定一个验证器是激活还是没有激活呢？两个条件
     * A validator is active if
     *它的on属性是空的（说明任何场景都适用）
     * - the validator's `on` property is empty, or
     * 它的on属性中的场景，包含当前的场景
     * - the validator's `on` property contains the specified scenario
     *从函数中看到还有一种情况：当前场景不在排除场景列表之内，且排除的优先级要高！
	 * 即如果某个场景出现在except里也出现在on里，那么except将会起作用，也就是说会排除这个场景
     * @param string $scenario scenario name
     * @return bool whether the validator applies to the specified scenario.
	 * 这个方法，是在Model中调用的。用于在众多验证器中找出适用指定属性的验证器
     */
    public function isActive($scenario)
    {
        return !in_array($scenario, $this->except, true) && (empty($this->on) || in_array($scenario, $this->on, true));
    }

    /** 
	 * 当validateValue()里实现的验证逻辑验证出问题时，会返回错误信息，
	 * 这里把错误信息填充到$model里
     * Adds an error about the specified attribute to the model object.
	 * 这里仅仅是一个错误信息的处理，最终填充到$model中
     * This is a helper method that performs message selection and internationalization.
     * @param \yii\base\Model $model the data model being validated
     * @param string $attribute the attribute being validated
	 * $message字符串，具体的错误信息
     * @param string $message the error message
	 * 一个placeholder待填充变量
     * @param array $params values for the placeholders in the error message
     */
    public function addError($model, $attribute, $message, $params = [])
    {
        $params['attribute'] = $model->getAttributeLabel($attribute);
        if (!isset($params['value'])) {
            $value = $model->$attribute;
            if (is_array($value)) {
                $params['value'] = 'array()';
            } elseif (is_object($value) && !method_exists($value, '__toString')) {
                $params['value'] = '(object)';
            } else {
                $params['value'] = $value;
            }
        }
		//使用了app的国际化组件（I18n），还美化了下错误信息，嘿嘿
		//这里暂且略过I18n组件是何方神圣，只需了解内部大概是sprintf函数的实现。继续专心研究model的验证
        $model->addError($attribute, Yii::$app->getI18n()->format($message, $params, Yii::$app->language));
    }

    /**检测给定的值是否是empty,在验证器决定是否真正验证当前属性时的过滤逻辑
     * Checks if the given value is empty.
     * A value is considered empty if it is null, an empty array, or an empty string.
     * Note that this method is different from PHP empty(). It will return false when the value is 0.
     * @param mixed $value the value to be checked
     * @return bool whether the value is empty
     */
    public function isEmpty($value)
    {
		//自定义判空逻辑
        if ($this->isEmpty !== null) {
            return call_user_func($this->isEmpty, $value);
        } else {
		//Yii框架默认的判空逻辑就这三个条件，null,[],''(双引号也行)
            return $value === null || $value === [] || $value === '';
        }
    }
}
