<?php
/**
 *   @author liumingwei  liumingwei@xiyun.com.cn
 *   @date  2018年3月7日 下午2:16:58
*/
namespace app\common\validators;

use yii\validators\Validator;
use yii\base\InvalidConfigException;
use Yii;
use yii\base\Model;

class ArrayValidator extends Validator
{

    /**
     * @var string|array 所要采用的验证方法,可以为string,也可以为如果个方法组成的array
     *      所有的方法必须属于RegexValidator
     *      在模型的rules方法里使用
     */
    public $method = null;

    /**
     * @var array 验证的方法列表
     * 方法必须属于RegexValidator
     */
    private $_methodArray = [];

    
    
    
    private $_validator;

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        // 过滤方法名
        $this->_methodArray = (array) $this->method;
        if (empty($this->_methodArray)) {
            throw new InvalidConfigException("Configuration error:没有验证方法!");
        }
        foreach ($this->_methodArray as $method) {
            if (! $this->hasMethod($method)) {
                throw new InvalidConfigException("Validating method:{$method}不存在!");
            }
        }
    }

    /**
     * @inheritdoc
     * 在模型上下文下执行验证，应该实现该方法
     */
    public function validateAttribute($model, $attribute)
    {
        $this->message = (array)$this->message;
        foreach($this->_methodArray as $k => $method){
            $ret = call_user_func([$this, $method], $model,$attribute);
            if($ret === false){
                return false;
            }
        }
    }
    
    /**
     * ArrayValidator验证器的验证方法之一
     * 找到数组的重复值
     * @param array $serialArr
     * @return boolean
     */
    public function unique($model,$attribute)
    {
        // 获取去掉重复数据的数组
        $serialArr = $model->$attribute;
        
        $unique_arr = array_unique($serialArr);
        // array_diff_assoc是比较所有的数组后，返回只在$serialArr中的元素，也就是重复的元素
        $arr = array_diff_assoc($serialArr, $unique_arr);
        // 重复的元素也可能是重复的
        $unique = array_unique($arr);
        if (! empty($unique)) {
            $model->addError($attribute, "发现重复值：" . implode(',', $unique));
            return false;
        }
        return true;
    }
}
