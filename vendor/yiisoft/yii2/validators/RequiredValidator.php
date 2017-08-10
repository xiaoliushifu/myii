<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\validators;

use Yii;

/**
 * 这个验证器专门验证指定的属性null和empty 
 * RequiredValidator validates that the specified attribute does not have null or empty value.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class RequiredValidator extends Validator
{
    /**
	 * boolean，是否跳过验证，当正要验证的属性是empty时
     * @var bool whether to skip this validator if the value being validated is empty.
	 * 看过其父类Validator就明白了，这明显是覆盖了父类的同名属性，且设置为false。
	 * 这个好理解，我们这个子类本身就是要验证出空值，故肯定不能略过呀，是吧？
     */
    public $skipOnEmpty = false;
    /**
	 * 渴望值列表
     * @var mixed the desired value that the attribute must have.
	 * 如果这个requiredValue为null，则只验证属性empty
     * If this is null, the validator will validate that the specified attribute is not empty.
	 * 如果requiredValue不为null,则判断属性的值是否和requiredValue相等
     * If this is set as a value that is not null, the validator will validate that
     * the attribute has a value that is the same as this property value.
     * Defaults to null.
     * @see strict
     */
    public $requiredValue;
    /** 
	 * 比较时，是否启动严格比较（数值和数据类型）
     * @var bool whether the comparison between the attribute value and [[requiredValue]] is strict.
     * When this is true, both the values and types must match.
     * Defaults to false, meaning only the values need to match.
	 * 还有一个特别注意的地方，就是$requiredValue为空，但是$strict为true时，验证的属性仅仅根据null来判空而非empty
     * Note that when [[requiredValue]] is null, if this property is true, the validator will check
     * if the attribute value is null; If this property is false, the validator will call [[isEmpty]]
	 * 其他情况，验证属性则用isEmpty方法判空
     * to check if the attribute value is empty.
     */
    public $strict = false;
    /**用户自定义的错误信息
     * @var string the user-defined error message. It may contain the following placeholders which
     * will be replaced accordingly by the validator:
     *
     * - `{attribute}`: the label of the attribute being validated
     * - `{value}`: the value of the attribute being validated
     * - `{requiredValue}`: the value of [[requiredValue]]
     */
    public $message;


    /**
     * @inheritdoc
     */
    public function init()
    {
		//调用父类的init方法，为三个属性初始化
        parent::init();
        if ($this->message === null) {
			//初始化信息格式，待验证错误时赋值给model的addErrors()
            $this->message = $this->requiredValue === null ? Yii::t('yii', '{attribute} cannot be blank.')
                : Yii::t('yii', '{attribute} must be "{requiredValue}".');
        }
    }

    /**
	* 这个方法是必须的，它实现了自己（必填验证器）的验证逻辑
	* 几乎每个验证器都会覆盖这个方法，来实现自己特有的逻辑
     * @inheritdoc
     */
    protected function validateValue($value)
    {
		//一个小if的括号里写了那么多，真是逻辑深啊
        if ($this->requiredValue === null) {
            if ($this->strict && $value !== null || !$this->strict && !$this->isEmpty(is_string($value) ? trim($value) : $value)) {
				//返回null说明验证没有问题
                return null;
            }
        } elseif (!$this->strict && $value == $this->requiredValue || $this->strict && $value === $this->requiredValue) {
            return null;
        }
        if ($this->requiredValue === null) {
            return [$this->message, []];
        } else {
            return [$this->message, [
                'requiredValue' => $this->requiredValue,
            ]];
        }
    }

    /**
     * @inheritdoc
	 * 看到了没？在服务端实现客户端的验证，其实就是返回一段JS函数的调用字符串而已
     */
    public function clientValidateAttribute($model, $attribute, $view)
    {
        ValidationAsset::register($view);
        $options = $this->getClientOptions($model, $attribute);

        return 'yii.validation.required(value, messages, ' . json_encode($options, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . ');';
    }

    /**
     * @inheritdoc
     */
    public function getClientOptions($model, $attribute)
    {
        $options = [];
        if ($this->requiredValue !== null) {
            $options['message'] = Yii::$app->getI18n()->format($this->message, [
                'requiredValue' => $this->requiredValue,
            ], Yii::$app->language);
            $options['requiredValue'] = $this->requiredValue;
        } else {
            $options['message'] = $this->message;
        }
        if ($this->strict) {
            $options['strict'] = 1;
        }

        $options['message'] = Yii::$app->getI18n()->format($options['message'], [
            'attribute' => $model->getAttributeLabel($attribute),
        ], Yii::$app->language);

        return $options;
    }
}
