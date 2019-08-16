<?php
namespace Wkii\Yii2Rbac\helpers;
/**
 * PHP获取文档注释
 * User: chaitao
 * Date: 16/3/1
 * Time: 14:48
 */
class PhpDoc
{
    private $object = null;

    /**
     * @var null|\ReflectionClass
     */
    private $reflector = null;

    public function __construct($object)
    {
        $this->reflector = new \ReflectionClass($object);
        $this->object = $object;
    }

    /**
     * 获取类的注释文档
     * @return string
     */
    public function getClassDoc()
    {
        $comment = $this->reflector->getDocComment();
        return self::description($comment);
    }

    /**
     * 返回类中方法的文档
     * @param $method
     * @return string
     */
    public function getMethodDoc($method)
    {
        $result = "";
        if ($this->reflector->hasMethod($method)) {
            $comment = $this->reflector->getMethod($method)->getDocComment();
            $result = self::description($comment);
        }

        return $result;
    }

    /**
     * 分析备注
     * @param $comment
     * @return array
     */
    private function analyze($comment)
    {
        $pattern = "#@?\w+.*#u";
        if (preg_match_all($pattern, $comment, $matches)) {
            return $matches[0];
        }
        return [];
    }

    /**
     * 注释转换成文档
     * @param $comment
     * @return string
     */
    private function description($comment)
    {
        $result = [];

        if (empty($comment)) {
            return "";
        }

        $comment_array = self::analyze($comment);

        foreach ($comment_array as $ele) {
            if (strpos($ele, "@") === 0) {
                break;
            }
            array_push($result, $ele);
        }

        return implode(PHP_EOL, $result);
    }
}