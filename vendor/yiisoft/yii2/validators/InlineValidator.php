<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\validators;

/**行内验证器也表示一种验证器，但它的验证逻辑（函数体）是在被验证对象（一般是模型对象）中定义的
 * InlineValidator represents a validator which is defined as a method in the object being validated.
 *验证方法的签名如下：
 * The validation method must have the following signature:
 *
 * ```php
 * function foo($attribute, $params, $validator)
 * ```
 *
 * where `$attribute` refers to the name of the attribute being validated, while `$params` is an array representing the
 * additional parameters supplied in the validation rule. Parameter `$validator` refers to the related
 * [[InlineValidator]] object and is available since version 2.0.11.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class InlineValidator extends Validator
{
    /**
     * @var string|\Closure an anonymous function or the name of a model class method that will be
     * called to perform the actual validation. The signature of the method should be like the following:
     *
     * ```php
     * function foo($attribute, $params, $validator)
     * ```
     *
     * - `$attribute` is the name of the attribute to be validated;
     * - `$params` contains the value of [[params]] that you specify when declaring the inline validation rule;
     * - `$validator` is a reference to related [[InlineValidator]] object. This parameter is available since version 2.0.11.
     */
    public $method;
    /**
     * @var mixed additional parameters that are passed to the validation method
     */
    public $params;
    /**
     * @var string|\Closure an anonymous function or the name of a model class method that returns the client validation code.
     * The signature of the method should be like the following:
     *
     * ```php
     * function foo($attribute, $params, $validator)
     * {
     *     return "javascript";
     * }
     * ```
     *
     * where `$attribute` refers to the attribute name to be validated.
     *
     * Please refer to [[clientValidateAttribute()]] for details on how to return client validation code.
     */
    public $clientValidate;


    /**
     * @inheritdoc
     */
    public function validateAttribute($model, $attribute)
    {
        //对于行内验证器，它的method属性仅仅存储方法名
        $method = $this->method;
        if (is_string($method)) {
            //方法的定义，还是属于模型对象的
            $method = [$model, $method];
        }
        call_user_func($method, $attribute, $this->params, $this);
    }

    /**
     * @inheritdoc
     */
    public function clientValidateAttribute($model, $attribute, $view)
    {
        if ($this->clientValidate !== null) {
            $method = $this->clientValidate;
            if (is_string($method)) {
                $method = [$model, $method];
            }

            return call_user_func($method, $attribute, $this->params, $this);
        } else {
            return null;
        }
    }
}
