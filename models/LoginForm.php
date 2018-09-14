<?php

namespace app\models;

use Yii;
use yii\base\Model;

/**
 * LoginForm is the model behind the login form.
 *
 * @property User|null $user This property is read-only.
 *
 */
class LoginForm extends Model
{
    public $username;
    public $password;
    public $verifyCode;
    public $rememberMe = true;

    private $_user = false;

    /**
     * @return array the validation rules.
     */
    public function rules()
    {
        return [
            // username and password are both required
            [['username', 'password'], 'required'],
            [['username'], 'string','max'=>'5'],
            // rememberMe must be a boolean value
            ['rememberMe', 'boolean'],
            //登录场景下需要验证码
            ['verifyCode', 'captcha', 'on' => ['login']],
            // password is validated by validatePassword()
            //针对这种规则，将会使用行内验证器，但是验证逻辑写在当前model的一个同名方法里，
            //方法名validatePassword存储在行内验证器（InlineValidator)的method属性中
            ['password', 'validatePassword'],
        ];
    }

    /**
     * Validates the password.
     * This method serves as the inline validation for password.
     * 行内验证器，本来应该验证password这个表单字段的合法性（比如长度，类型，范围等）
     * 这里直接就是用户名密码的一致验证了。就是认证逻辑。嘿嘿。
     * @param string $attribute the attribute currently being validated
     * @param array $params the additional name-value pairs given in the rule
     */
    public function validatePassword($attribute, $params)
    {
        if (!$this->hasErrors()) {
            $user = $this->getUser();
            //没有这个用户，或者有这个用户但是密码不对
            if (!$user || !$user->validatePassword($this->password)) {
				//注意学习AR对象错误信息是如何保存的，是以字段名为下标
                $this->addError($attribute, 'Incorrect username or password.');
            }
        }
    }

    /**最终还是由user组件的login方法完成登录
     * Logs in a user using the provided username and password.
     * @return bool whether the user is logged in successfully
     */
    public function login()
    {
        //在validate()验证里，其实就已经比对了用户名和密码，
        if ($this->validate()) {
            //在验证成功后直接login这个user即可，可见认证逻辑（在validate()中）和登录逻辑（Yii::$app->user->login)是两回事
            //通过设置rememberMe还能控制cookie保存identity信息，默认是30天有效期免输入用户名密码登录
            return Yii::$app->user->login($this->getUser(), $this->rememberMe ? 3600*24*30 : 0);
        }
        return false;
    }

    /**
     * Finds user by [[username]]
     *
     * @return User|null
     */
    public function getUser()
    {
        if ($this->_user === false) {
            $this->_user = User::findByUsername($this->username);
        }

        return $this->_user;
    }
}
