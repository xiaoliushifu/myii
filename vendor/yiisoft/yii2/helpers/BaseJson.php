<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\helpers;

use yii\base\InvalidParamException;
use yii\base\Arrayable;
use yii\web\JsExpression;

/**
* BaseJson类为JSON这个语法格式提供了具体实在的实现
 * BaseJson provides concrete implementation for [[Json]].
 * 应该直接使用Json，而不是BaseJson
 * Do not use BaseJson. Use [[Json]] instead.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class BaseJson
{
    /**
     * List of JSON Error messages assigned to constant names for better handling of version differences
     * @var array
     * @since 2.0.7
     */
    public static $jsonErrorMessages = [
        'JSON_ERROR_DEPTH' => 'The maximum stack depth has been exceeded.',
        'JSON_ERROR_STATE_MISMATCH' => 'Invalid or malformed JSON.',
        'JSON_ERROR_CTRL_CHAR' => 'Control character error, possibly incorrectly encoded.',
        'JSON_ERROR_SYNTAX' => 'Syntax error.',
        'JSON_ERROR_UTF8' => 'Malformed UTF-8 characters, possibly incorrectly encoded.', // PHP 5.3.3
        'JSON_ERROR_RECURSION' => 'One or more recursive references in the value to be encoded.', // PHP 5.5.0
        'JSON_ERROR_INF_OR_NAN' => 'One or more NAN or INF values in the value to be encoded', // PHP 5.5.0
        'JSON_ERROR_UNSUPPORTED_TYPE' => 'A value of a type that cannot be encoded was given', // PHP 5.5.0
    ];


    /**
	* 编码给定值为JSON字符串，注意，json在php来说，就是一个字符串而已。
     * Encodes the given value into a JSON string.
     *
	 * 该方法通过支持JavaScript表达式封装了php原生函数json_encode
     * The method enhances `json_encode()` by supporting JavaScript expressions.
     * In particular, the method will not encode a JavaScript expression that is
     * represented in terms of a [[JsExpression]] object.
     * 注意，要编码为JSON的数据，其字符编码必须是UTF-8的。（这是JSON格式的要求）
     * Note that data encoded as JSON must be UTF-8 encoded according to the JSON specification.
	 * 开发人员需要确保$value是UTF-8的。
     * You must ensure strings passed to this method have proper encoding before passing them.
     *
     * @param mixed $value the data to be encoded.
     * @param int $options the encoding options. For more details please refer to
     * <http://www.php.net/manual/en/function.json-encode.php>. Default is `JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE`.
     * @return string the encoding result.
     * @throws InvalidParamException if there is any encoding error.
     */
    public static function encode($value, $options = 320)
    {
        $expressions = [];
        $value = static::processData($value, $expressions, uniqid('', true));
		//临时注册一个错误处理函数，在json格式化时可能出错。
        set_error_handler(function () {
            static::handleJsonError(JSON_ERROR_SYNTAX);
        }, E_WARNING);
		//这才是核心，php原生函数
        $json = json_encode($value, $options);

		//再把刚才注册的错误处理函数取消。（注册取消【错误处理函数】，属于进出栈操作）
        restore_error_handler();
        static::handleJsonError(json_last_error());

        return $expressions === [] ? $json : strtr($json, $expressions);
    }

    /**
     * Encodes the given value into a JSON string HTML-escaping entities so it is safe to be embedded in HTML code.
     *
     * The method enhances `json_encode()` by supporting JavaScript expressions.
     * In particular, the method will not encode a JavaScript expression that is
     * represented in terms of a [[JsExpression]] object.
     *
     * Note that data encoded as JSON must be UTF-8 encoded according to the JSON specification.
     * You must ensure strings passed to this method have proper encoding before passing them.
     *
     * @param mixed $value the data to be encoded
     * @return string the encoding result
     * @since 2.0.4
     * @throws InvalidParamException if there is any encoding error
     */
    public static function htmlEncode($value)
    {
        return static::encode($value, JSON_UNESCAPED_UNICODE | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS);
    }

    /**
     * Decodes the given JSON string into a PHP data structure.
     * @param string $json the JSON string to be decoded
     * @param bool $asArray whether to return objects in terms of associative arrays.
     * @return mixed the PHP data
     * @throws InvalidParamException if there is any decoding error
     */
    public static function decode($json, $asArray = true)
    {
        if (is_array($json)) {
            throw new InvalidParamException('Invalid JSON data.');
        } elseif ($json === null || $json === '') {
            return null;
        }
        $decode = json_decode((string) $json, $asArray);
        static::handleJsonError(json_last_error());

        return $decode;
    }

    /**
     * Handles [[encode()]] and [[decode()]] errors by throwing exceptions with the respective error message.
     *
     * @param int $lastError error code from [json_last_error()](http://php.net/manual/en/function.json-last-error.php).
     * @throws \yii\base\InvalidParamException if there is any encoding/decoding error.
     * @since 2.0.6
     */
    protected static function handleJsonError($lastError)
    {
        if ($lastError === JSON_ERROR_NONE) {
            return;
        }

        $availableErrors = [];
        foreach (static::$jsonErrorMessages as $const => $message) {
            if (defined($const)) {
                $availableErrors[constant($const)] = $message;
            }
        }

        if (isset($availableErrors[$lastError])) {
            throw new InvalidParamException($availableErrors[$lastError], $lastError);
        }

        throw new InvalidParamException('Unknown JSON encoding/decoding error.');
    }

    /**
	* 在php原生函数json_encode之前，预处理数据
	*  主要是不是二维数组，或者是对象。其他数据类型的，则没有什么处理。直接返回了。
     * Pre-processes the data before sending it to `json_encode()`.
     * @param mixed $data the data to be processed
     * @param array $expressions collection of JavaScript expressions
     * @param string $expPrefix a prefix internally used to handle JS expressions
     * @return mixed the processed data
     */
    protected static function processData($data, &$expressions, $expPrefix)
    {
        if (is_object($data)) {
            if ($data instanceof JsExpression) {
                $token = "!{[$expPrefix=" . count($expressions) . ']}!';
                $expressions['"' . $token . '"'] = $data->expression;

                return $token;
            } elseif ($data instanceof \JsonSerializable) {
                return static::processData($data->jsonSerialize(), $expressions, $expPrefix);
            } elseif ($data instanceof Arrayable) {
                $data = $data->toArray();
            } elseif ($data instanceof \SimpleXMLElement) {
                $data = (array) $data;
            } else {
                $result = [];
                foreach ($data as $name => $value) {
                    $result[$name] = $value;
                }
                $data = $result;
            }

            if ($data === []) {
                return new \stdClass();
            }
        }
		//一般使用json的，多半是数组格式的，对象的少
        if (is_array($data)) {
			//遍历之
            foreach ($data as $key => $value) {
				//每个数组的元素是数组或者对象的话，递归处理之
                if (is_array($value) || is_object($value)) {
                    $data[$key] = static::processData($value, $expressions, $expPrefix);
                }
            }
        }

        return $data;
    }
}
