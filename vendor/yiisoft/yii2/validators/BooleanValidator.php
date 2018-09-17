<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\validators;

use Yii;

/**BooleanValidator验证器是为了检测属性值是否是布尔值
 * BooleanValidator checks if the attribute value is a boolean value.
 *可能的布尔值，可以通过[[trueValue]]和[[falseValue]] 来配置，它们的比较也可以是严格或非严格的
 * Possible boolean values can be configured via the [[trueValue]] and [[falseValue]] properties.
 * And the comparison can be either [[strict]] or not.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class BooleanValidator extends Validator
{
    /**
     * 默认的真值是1
     * @var mixed the value representing true status. Defaults to '1'.
     */
    public $trueValue = '1';
    /**
     * 默认的假值是"0"
     * @var mixed the value representing false status. Defaults to '0'.
     */
    public $falseValue = '0';
    /**
     * 布尔，比较时是否使用严格模式
     * @var bool whether the comparison to [[trueValue]] and [[falseValue]] is strict.
     * 当值为true时，属性值和属性值的类型都会比较，使用全等===
     * When this is true, the attribute value and type must both match those of [[trueValue]] or [[falseValue]].
     * 默认是非严格比较，也就是说仅仅比较属性值（==）
     * Defaults to false, meaning only the value needs to be matched.
     */
    public $strict = false;


    /**
     * 每个核心验证器的init方法一般都是先处理message
     * message是所有核心验证器继承自父类Validator而来的
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        if ($this->message === null) {
            $this->message = Yii::t('yii', '{attribute} must be either "{true}" or "{false}".');
        }
    }

    /**Boolean验证器的验证核心，分为两种情况：
     * 非严格模式就比较1或0(默认），
     * 严格模式使用===
     * @inheritdoc
     */
    protected function validateValue($value)
    {
        //这行代码写的真棒，利用了逻辑运算符的短路，分成了或||的两个部分
        $valid = !$this->strict && ($value == $this->trueValue || $value == $this->falseValue)
                 ||//两个或部分拆开来非常好
            $this->strict && ($value === $this->trueValue || $value === $this->falseValue);

        //出现错误，就组织一下错误信息
        if (!$valid) {
            return [$this->message, [
                'true' => $this->trueValue === true ? 'true' : $this->trueValue,
                'false' => $this->falseValue === false ? 'false' : $this->falseValue,
            ]];
        }
        //没有错误就返回null
        return null;
    }

    /**
     * 生成客户端验证的逻辑
     * 一般在activeForm里开始的
     * @inheritdoc
     */
    public function clientValidateAttribute($model, $attribute, $view)
    {
        ValidationAsset::register($view);
        $options = $this->getClientOptions($model, $attribute);

        return 'yii.validation.boolean(value, messages, ' . json_encode($options, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . ');';
    }

    /**
     * 获得客户端的配置数组
     * @inheritdoc
     */
    public function getClientOptions($model, $attribute)
    {
        $options = [
            'trueValue' => $this->trueValue,
            'falseValue' => $this->falseValue,
            'message' => Yii::$app->getI18n()->format($this->message, [
                'attribute' => $model->getAttributeLabel($attribute),
                'true' => $this->trueValue === true ? 'true' : $this->trueValue,
                'false' => $this->falseValue === false ? 'false' : $this->falseValue,
            ], Yii::$app->language),
        ];
        if ($this->skipOnEmpty) {
            $options['skipOnEmpty'] = 1;
        }
        if ($this->strict) {
            $options['strict'] = 1;
        }

        return $options;
    }
}
