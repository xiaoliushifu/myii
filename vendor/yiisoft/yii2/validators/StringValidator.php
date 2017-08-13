<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\validators;

use Yii;

/**
 * StringValidator验证器验证什么的？验证属性值是否是某个长度
 * StringValidator validates that the attribute value is of certain length.
 * 注意，这个验证器应该针对字符串类型的属性使用
 * Note, this validator should only be used with string-typed attributes.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class StringValidator extends Validator
{
    /** 整数或者数组，指定被验证属性的长度限制
     * @var int|array specifies the length limit of the value to be validated.
     * This can be specified in one of the following forms:
     * 一个整数时，确切地限制属性的值长度
     * - an integer: the exact length that the value should be of;
	 * 一个元素的数组，限制属性的最小长度，将会覆盖min
     * - an array of one element: the minimum length that the value should be of. For example, `[8]`.
     *   This will overwrite [[min]].
	 * 两个元素的数组，限制属性的最小和最大长度
     * - an array of two elements: the minimum and maximum lengths that the value should be of.
	 * 比如 [8,128]，表示限制属性在8--128之间
     *   For example, `[8, 128]`. This will overwrite both [[min]] and [[max]].
	 * 太短，自定义过短时的报错信息
     * @see tooShort for the customized message for a too short string.
	 * 太长，自定义过长时的报错信息
     * @see tooLong for the customized message for a too long string.
	 * 不等，不是指定长度时自定义的报错信息
     * @see notEqual for the customized message for a string that does not match desired length.
     */
    public $length;
    /**
     * @var int maximum length. If not set, it means no maximum length limit.
     * @see tooLong for the customized message for a too long string.
     */
    public $max;
    /**
     * @var int minimum length. If not set, it means no minimum length limit.
     * @see tooShort for the customized message for a too short string.
     */
    public $min;
    /**当属性值不是字符串时，自定义报错信息
     * @var string user-defined error message used when the value is not a string.
     */
    public $message;
    /**
     * @var string user-defined error message used when the length of the value is smaller than [[min]].
     */
    public $tooShort;
    /**
     * @var string user-defined error message used when the length of the value is greater than [[max]].
     */
    public $tooLong;
    /**
     * @var string user-defined error message used when the length of the value is not equal to [[length]].
     */
    public $notEqual;
    /**指定验证属性的字符编码,比如utf-8,gbk等（默认是$app::charset)
     * @var string the encoding of the string value to be validated (e.g. 'UTF-8').
     * If this property is not set, [[\yii\base\Application::charset]] will be used.
     */
    public $encoding;


    /**
     * @inheritdoc
     */
    public function init()
    {
		//子类都要借用父类的init方法，达到为自己初始化所有验证器的那三个公共属性的功能。
		//哪三个公共属性？
		//attributes
		//on
		//except
        parent::init();
		//然后下面是子验证器特有的属性的初始化，比如min,max,tooShort,message等
        if (is_array($this->length)) {
            if (isset($this->length[0])) {
                $this->min = $this->length[0];
            }
            if (isset($this->length[1])) {
                $this->max = $this->length[1];
            }
            $this->length = null;
        }
        if ($this->encoding === null) {
            $this->encoding = Yii::$app->charset;
        }
        if ($this->message === null) {
            $this->message = Yii::t('yii', '{attribute} must be a string.');
        }
        if ($this->min !== null && $this->tooShort === null) {
            $this->tooShort = Yii::t('yii', '{attribute} should contain at least {min, number} {min, plural, one{character} other{characters}}.');
        }
        if ($this->max !== null && $this->tooLong === null) {
            $this->tooLong = Yii::t('yii', '{attribute} should contain at most {max, number} {max, plural, one{character} other{characters}}.');
        }
        if ($this->length !== null && $this->notEqual === null) {
            $this->notEqual = Yii::t('yii', '{attribute} should contain {length, number} {length, plural, one{character} other{characters}}.');
        }
    }

    /**
	* 这个验证器，针对自己独有的验证逻辑，应该覆盖基类的validateAttribute()方法
     * @inheritdoc
     */
    public function validateAttribute($model, $attribute)
    {
        $value = $model->$attribute;

        if (!is_string($value)) {
            $this->addError($model, $attribute, $this->message);

            return;
        }

        $length = mb_strlen($value, $this->encoding);

        if ($this->min !== null && $length < $this->min) {
            $this->addError($model, $attribute, $this->tooShort, ['min' => $this->min]);
        }
        if ($this->max !== null && $length > $this->max) {
            $this->addError($model, $attribute, $this->tooLong, ['max' => $this->max]);
        }
        if ($this->length !== null && $length !== $this->length) {
            $this->addError($model, $attribute, $this->notEqual, ['length' => $this->length]);
        }
    }

    /**
     * @inheritdoc
	 * 具体的验证逻辑的实现，是子类验证器应该覆盖基类验证器的方法之一
     */
    protected function validateValue($value)
    {
		//字符串验证器，就得属性值是字符串时才能进行后续的验证
        if (!is_string($value)) {
            return [$this->message, []];
        }

        $length = mb_strlen($value, $this->encoding);

        if ($this->min !== null && $length < $this->min) {
            return [$this->tooShort, ['min' => $this->min]];
        }
        if ($this->max !== null && $length > $this->max) {
            return [$this->tooLong, ['max' => $this->max]];
        }
        if ($this->length !== null && $length !== $this->length) {
            return [$this->notEqual, ['length' => $this->length]];
        }

        return null;
    }

    /**
	* 客户端验证逻辑的实现
	* 第三个参数是视图对象，\yii\web\View

	* 我们看到，返回的是yii.validation.string(aaa,bbb,ccc)
	* 明显是一个JS函数的调用语句，也就是说实际的JS方法已经提前写好了，是在yii.validation.js里的
	* 里面有string,required,Number等方法
     * @inheritdoc
     */
    public function clientValidateAttribute($model, $attribute, $view)
    {
		//这是什么呢？看到没有Asset，就是前端资源（js,css)呗，实现客户端验证必须加载的前端验证资源，
		//其中最重要的就是yii.validation.js文件了
        ValidationAsset::register($view);
        $options = $this->getClientOptions($model, $attribute);

        return 'yii.validation.string(value, messages, ' . json_encode($options, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . ');';
    }

    public function getClientOptions($model, $attribute)
    {
        $label = $model->getAttributeLabel($attribute);

        $options = [
            'message' => Yii::$app->getI18n()->format($this->message, [
                'attribute' => $label,
            ], Yii::$app->language),
        ];

        if ($this->min !== null) {
            $options['min'] = $this->min;
            $options['tooShort'] = Yii::$app->getI18n()->format($this->tooShort, [
                'attribute' => $label,
                'min' => $this->min,
            ], Yii::$app->language);
        }
        if ($this->max !== null) {
            $options['max'] = $this->max;
            $options['tooLong'] = Yii::$app->getI18n()->format($this->tooLong, [
                'attribute' => $label,
                'max' => $this->max,
            ], Yii::$app->language);
        }
        if ($this->length !== null) {
            $options['is'] = $this->length;
            $options['notEqual'] = Yii::$app->getI18n()->format($this->notEqual, [
                'attribute' => $label,
                'length' => $this->length,
            ], Yii::$app->language);
        }
        if ($this->skipOnEmpty) {
            $options['skipOnEmpty'] = 1;
        }

        return $options;
    }
}
