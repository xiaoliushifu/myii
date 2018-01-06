<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\helpers;

use Yii;

/**
 * BaseStringHelper provides concrete implementation for [[StringHelper]].
 *
 * Do not use BaseStringHelper. Use [[StringHelper]] instead.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @author Alex Makarov <sam@rmcreative.ru>
 * @since 2.0
 */
class BaseStringHelper
{
    /**返回给定字符串的字节长度，注意是字节长度（字母数字都被各大字符集兼容为ascii，
	故这些个字符，一个字符就是一个字节；如果涉及中文的话，那就得看是gbk还是utf8了。
	一个utf-8的汉字是三个字节，一个gbk的则是两个字节）
     * Returns the number of bytes in the given string.
	 该方法使用mb_strlen的底层php函数。第二个参数8bit,意味着按照字节长度读取字符串
     * This method ensures the string is treated as a byte array by using `mb_strlen()`.
     * @param string $string the string being measured for length
     * @return int the number of bytes in the given string.
     */
    public static function byteLength($string)
    {
        return mb_strlen($string, '8bit');
    }

    /**
	 * 在字节层面上截取字符串的一部分（portion),还是用的mb_substr函数，
	 这个函数非常好用，只需配置最后一个参数，说明字符串的层面或字符集就行，扩展性非常强。
     * Returns the portion of string specified by the start and length parameters.
     * This method ensures the string is treated as a byte array by using `mb_substr()`.
     * @param string $string the input string. Must be one character or longer.
     * @param int $start the starting position
     * @param int $length the desired portion length. If not specified or `null`, there will be
     * no limit on length i.e. the output will be until the end of the string.
     * @return string the extracted part of string, or FALSE on failure or an empty string.
     * @see http://www.php.net/manual/en/function.substr.php
     */
    public static function byteSubstr($string, $start, $length = null)
    {
        return mb_substr($string, $start, $length === null ? mb_strlen($string, '8bit') : $length, '8bit');
    }

    /**
	* 和php原生函数basename()的唯一差别就是会把斜杠和反斜杠都当做目录分隔符，不用看操作系统。
     * Returns the trailing name component of a path.
     * This method is similar to the php function `basename()` except that it will
     * treat both \ and / as directory separators, independent of the operating system.
     * This method was mainly created to work on php namespaces. When working with real
     * file paths, php's `basename()` should work fine for you.
     * Note: this method is not aware of the actual filesystem, or path components such as "..".
     *
     * @param string $path A path string.
     * @param string $suffix If the name component ends in suffix this will also be cut off.
     * @return string the trailing name component of the given path.
     * @see http://www.php.net/manual/en/function.basename.php
     */
    public static function basename($path, $suffix = '')
    {
        if (($len = mb_strlen($suffix)) > 0 && mb_substr($path, -$len) === $suffix) {
            $path = mb_substr($path, 0, -$len);
        }
        $path = rtrim(str_replace('\\', '/', $path), '/\\');
        if (($pos = mb_strrpos($path, '/')) !== false) {
            return mb_substr($path, $pos + 1);
        }

        return $path;
    }

    /**与php原生函数dirname的区别是，把斜杠/和反斜杠\都会当做目录分隔符，不用看操作系统，平台兼容。
     * Returns parent directory's path.
     * This method is similar to `dirname()` except that it will treat
     * both \ and / as directory separators, independent of the operating system.
     *
     * @param string $path A path string.
     * @return string the parent directory's path.
     * @see http://www.php.net/manual/en/function.basename.php
     */
    public static function dirname($path)
    {
        $pos = mb_strrpos(str_replace('\\', '/', $path), '/');
        if ($pos !== false) {
            return mb_substr($path, 0, $pos);
        } else {
            return '';
        }
    }
    
    /**截取字符串为指定的长度，然后默认以"..."来填充
     * Truncates a string to the number of characters specified.
     *字符串的长度，是按照字符集来算的，所以可以是utf-8,gbk,8bit都有可能
     * @param string $string The string to truncate.
     * @param int $length How many characters from original string to include into truncated string.
     * @param string $suffix String to append to the end of truncated string.
     * @param string $encoding The charset to use, defaults to charset currently used by application.
     * @param bool $asHtml Whether to treat the string being truncated as HTML and preserve proper HTML tags.
     * This parameter is available since version 2.0.1.
     * @return string the truncated string.
     */
    public static function truncate($string, $length, $suffix = '...', $encoding = null, $asHtml = false)
    {
        if ($asHtml) {
            return static::truncateHtml($string, $length, $suffix, $encoding ?: Yii::$app->charset);
        }
        
        if (mb_strlen($string, $encoding ?: Yii::$app->charset) > $length) {
            return rtrim(mb_substr($string, 0, $length, $encoding ?: Yii::$app->charset)) . $suffix;
        } else {
            return $string;
        }
    }
    
    /**
     * Truncates a string to the number of words specified.
     *words是啥呢？words是单词，是英文体系的，是按照空格（空白）拆分的字符串
	 *这里与字符串的区别就是，words属于字符串的子集，按照空白拆分出单词。而字符串可以啥都有，
	 单引号或者双引号括起来的都是字符串
     * @param string $string The string to truncate.
     * @param int $count How many words from original string to include into truncated string.
     * @param string $suffix String to append to the end of truncated string.
     * @param bool $asHtml Whether to treat the string being truncated as HTML and preserve proper HTML tags.
     * This parameter is available since version 2.0.1.
     * @return string the truncated string.
     */
    public static function truncateWords($string, $count, $suffix = '...', $asHtml = false)
    {
        if ($asHtml) {
            return static::truncateHtml($string, $count, $suffix);
        }

        $words = preg_split('/(\s+)/u', trim($string), null, PREG_SPLIT_DELIM_CAPTURE);
		//这里为啥除以2？是因为空格（单词的分隔符）也算一个字符，占据一个数。
        if (count($words) / 2 > $count) {
            return implode('', array_slice($words, 0, ($count * 2) - 1)) . $suffix;
        } else {
            return $string;
        }
    }
    
    /**
     * Truncate a string while preserving the HTML.
     * 太长了，没有看
     * @param string $string The string to truncate
     * @param int $count
     * @param string $suffix String to append to the end of the truncated string.
     * @param string|bool $encoding
     * @return string
     * @since 2.0.1
     */
    protected static function truncateHtml($string, $count, $suffix, $encoding = false)
    {
        $config = \HTMLPurifier_Config::create(null);
        $config->set('Cache.SerializerPath', \Yii::$app->getRuntimePath());
        $lexer = \HTMLPurifier_Lexer::create($config);
        $tokens = $lexer->tokenizeHTML($string, $config, new \HTMLPurifier_Context());
        $openTokens = [];
        $totalCount = 0;
        $truncated = [];
        foreach ($tokens as $token) {
            if ($token instanceof \HTMLPurifier_Token_Start) { //Tag begins
                if ($totalCount < $count) {
                    $openTokens[$token->name] = isset($openTokens[$token->name]) ? $openTokens[$token->name] + 1 : 1;
                    $truncated[] = $token;
                }
            } elseif ($token instanceof \HTMLPurifier_Token_Text && $totalCount <= $count) { //Text
                if (false === $encoding) {
                    preg_match('/^(\s*)/um', $token->data, $prefixSpace) ?: $prefixSpace = ['',''];
                    $token->data = $prefixSpace[1] . self::truncateWords(ltrim($token->data), $count - $totalCount, '');
                    $currentCount = self::countWords($token->data);
                } else {
                    $token->data = self::truncate($token->data, $count - $totalCount, '', $encoding);
                    $currentCount = mb_strlen($token->data, $encoding);
                }
                $totalCount += $currentCount;
                $truncated[] = $token;
            } elseif ($token instanceof \HTMLPurifier_Token_End) { //Tag ends
                if (!empty($openTokens[$token->name])) {
                    $openTokens[$token->name]--;
                    $truncated[] = $token;
                }
            } elseif ($token instanceof \HTMLPurifier_Token_Empty) { //Self contained tags, i.e. <img/> etc.
                $truncated[] = $token;
            }
            if (0 === $openTokens && $totalCount >= $count) {
                break;
            }
        }
        $context = new \HTMLPurifier_Context();
        $generator = new \HTMLPurifier_Generator($config, $context);
        return $generator->generateFromTokens($truncated) . ($totalCount >= $count ? $suffix : '');
    }

    /**
	 * 检测某个字符串，是否以某个子串开头的
     * Check if given string starts with specified substring.
     * Binary and multibyte safe.
     *
     * @param string $string Input string
     * @param string $with Part to search inside the $string
     * @param bool $caseSensitive Case sensitive search. Default is true. When case sensitive is enabled, $with must exactly match the starting of the string in order to get a true value.
     * @return bool Returns true if first input starts with second input, false otherwise
     */
    public static function startsWith($string, $with, $caseSensitive = true)
    {
        if (!$bytes = static::byteLength($with)) {
            return true;
        }
        if ($caseSensitive) {
			//使用这个php函数，注意，这是完成字符串比较的首选函数，区分大小写，且可以传递比较长度
            return strncmp($string, $with, $bytes) === 0;
        } else {
            return mb_strtolower(mb_substr($string, 0, $bytes, '8bit'), Yii::$app->charset) === mb_strtolower($with, Yii::$app->charset);
        }
    }

    /**
     * Check if given string ends with specified substring.
     * Binary and multibyte safe.
     * 是不是某个子串结尾的
     * @param string $string Input string to check
     * @param string $with Part to search inside of the $string.
     * @param bool $caseSensitive Case sensitive search. Default is true. When case sensitive is enabled, $with must exactly match the ending of the string in order to get a true value.
     * @return bool Returns true if first input ends with second input, false otherwise
     */
    public static function endsWith($string, $with, $caseSensitive = true)
    {
        if (!$bytes = static::byteLength($with)) {
            return true;
        }
        if ($caseSensitive) {
            // Warning check, see http://php.net/manual/en/function.substr-compare.php#refsect1-function.substr-compare-returnvalues
            if (static::byteLength($string) < $bytes) {
                return false;
            }
			//这个函数不错，从偏移位置比较两个字符串，二进制安全，比strncmp灵活多了呀
			//因为strncmp是从开头比较的，而本函数想判断某子串结尾，故应该从右侧开始的比较。这才使用substr_compare函数
            return substr_compare($string, $with, -$bytes, $bytes) === 0;
        } else {
            return mb_strtolower(mb_substr($string, -$bytes, mb_strlen($string, '8bit'), '8bit'), Yii::$app->charset) === mb_strtolower($with, Yii::$app->charset);
        }
    }

    /**
     * Explodes string into array, optionally trims values and skips empty ones
     * 把字符串按照某个字符拆分成数组，还有可选地trim和跳空处理
     * @param string $string String to be exploded.
     * @param string $delimiter Delimiter. Default is ','. 默认的分隔符是逗号
     * @param mixed $trim Whether to trim each element. Can be:  trim处理可以传递匿名函数，也可以是默认的trim函数
     *   - boolean - to trim normally;
     *   - string - custom characters to trim. Will be passed as a second argument to `trim()` function.
     *   - callable - will be called for each value instead of trim. Takes the only argument - value.
     * @param bool $skipEmpty Whether to skip empty strings between delimiters. Default is false.
	 当出现连续的两个分隔符时，那么就会分割出空字符串，是否跳空呢？
     * @return array
     * @since 2.0.4
     */
    public static function explode($string, $delimiter = ',', $trim = true, $skipEmpty = false)
    {
        $result = explode($delimiter, $string);
        if ($trim) {
            if ($trim === true) {
                $trim = 'trim';
            } elseif (!is_callable($trim)) {
                $trim = function ($v) use ($trim) {
                    return trim($v, $trim);
                };
            }
            $result = array_map($trim, $result);
        }
        if ($skipEmpty) {
            // Wrapped with array_values to make array keys sequential after empty values removing
            $result = array_values(array_filter($result, function ($value) {
                return $value !== '';
            }));
        }
        return $result;
    }

    /**
	* 字符串中，单词的数量。
     * Counts words in a string
     * @since 2.0.8
     *看过实现就知道了，在字符串中如何定义单词，怎么认为是一个单词呢？没有别的办法，两个空格（空白）之间的
	 就是单词。而且，由于正则函数的第四个参数是PREG_SPLIT_NO_EMPTY，所以排除为空的。
     * @param string $string
     * @return int
     */
    public static function countWords($string)
    {
        return count(preg_split('/\s+/u', $string, null, PREG_SPLIT_NO_EMPTY));
    }

    /**
     * Returns string represenation of number value with replaced commas to dots, if decimal point
	 把数字表示法里的英文逗号换成英文句号。初步理解。
     * of current locale is comma
     * @param int|float|string $value
     * @return string
     * @since 2.0.11
     */
    public static function normalizeNumber($value)
    {
        $value = "$value";

        $localeInfo = localeconv();
        $decimalSeparator = isset($localeInfo['decimal_point']) ? $localeInfo['decimal_point'] : null;

        if ($decimalSeparator !== null && $decimalSeparator !== '.') {
            $value = str_replace($decimalSeparator, '.', $value);
        }

        return $value;
    }
}
