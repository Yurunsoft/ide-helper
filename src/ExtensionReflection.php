<?php
namespace Yurun\IDEHelper;

use Yurun\IDEHelper\ReflectionUtil;

class ExtensionReflection
{
    /**
     * ReflectionExtension
     *
     * @var ReflectionExtension
     */
    private $ref;

    /**
     * 保存路径
     *
     * @var string
     */
    private $savePath;

    public function __construct($name)
    {
        $this->ref = new \ReflectionExtension($name);
    }

    /**
     * 保存
     *
     * @param string $path
     * @return void
     */
    public function save($path)
    {
        $this->savePath = $path;
        if(!is_dir($path))
        {
            mkdir($path, 0777, true);
        }
        $this->generateConsts();
        $this->generateFunctions();
        $this->generateClasses();
    }

    /**
     * 生成常量
     *
     * @return void
     */
    private function generateConsts()
    {
        $result = '<?php' . PHP_EOL;
        foreach($this->ref->getConstants() as $name => $value)
        {
            $value = var_export($value, true);
            $result .= <<<CODE
define('{$name}', {$value});

CODE;
        }
        file_put_contents($this->savePath . '/consts.php', $result);
    }

    /**
     * 生成函数
     *
     * @return void
     */
    private function generateFunctions()
    {
        $namespaceFunctions = [];
        foreach ($this->ref->getFunctions() as $function)
        {
            $args = [];
            $comments = [];
            foreach ($function->getParameters() as $param)
            {
                // 方法参数定义
                $args[] = $this->getMethodParamDefine($param);
                $comments[] = '@var ' . ReflectionUtil::getTypeComments($param->getType()) . ' $' . $param->getName();
            }
            $comments[] = '@return ' . ReflectionUtil::getTypeComments($function->getReturnType());
            $args = implode(', ', $args);
            if ([] === $comments)
            {
                $comment = '';
            }
            else
            {
                $comment = implode(\PHP_EOL . '     * ', $comments);
                $comment = <<<COMMENT
    /**
     * {$comment}
     */
COMMENT;
            }
            $namespace = $function->getNamespaceName();
            if (!isset($namespaceFunctions[$namespace]))
            {
                $namespaceFunctions[$namespace] = <<<CODE
namespace {$namespace}
{

CODE;
            }
            $namespaceFunctions[$namespace] .= <<<CODE
{$comment}
    function {$function->getShortName()}({$args}){}


CODE;
        }
        $result = '<?php' . \PHP_EOL;
        foreach ($namespaceFunctions as $content)
        {
            $result .= $content . '}' . \PHP_EOL;
        }
        file_put_contents($this->savePath . '/functions.php', $result);
    }

    /**
     * 生成类、接口、trait
     *
     * @return void
     */
    private function generateClasses()
    {
        foreach($this->ref->getClassNames() as $className)
        {
            $class = new \ReflectionClass($className);
            if($class->isInterface())
            {
                $this->generateInterface($class);
            }
            else if($class->isTrait())
            {
                $this->generateTrait($class);
            }
            else
            {
                $this->generateClass($class);
            }
        }
    }

    /**
     * 获取方法参数定义模版
     * @param \ReflectionParameter $param
     * @return string
     */
    private static function getMethodParamDefine(\ReflectionParameter $param)
    {
        // 类型
        $result = ReflectionUtil::getTypeCode($param->getType()) . ' ';
        if($param->isPassedByReference())
        {
            // 引用传参
            $result .= '&';
        }
        else if($param->isVariadic())
        {
            // 可变参数...
            $result .= '...';
        }
        // $参数名
        $result .= '$' . $param->name;
        // 默认值
        if($param->isOptional() && !$param->isVariadic())
        {
            if($param->isDefaultValueAvailable())
            {
                $result .= ' = ' . var_export($param->getDefaultValue(), true);
            }
            else
            {
                $result .= ' = null';
            }
        }
        return $result;
    }

    /**
     * 生成类常量
     *
     * @param \ReflectionClass $class
     * @return string
     */
    private function getClassConsts($class)
    {
        $result = '';
        foreach($class->getConstants() as $name => $value)
        {
            $value = var_export($value, true);
            $result .= <<<CODE

    const {$name} = $value;

CODE;
        }
        return $result;
    }

    /**
     * 生成类方法
     *
     * @param \ReflectionClass $class
     * @return string
     */
    private function getClassMethods($class)
    {
        $result = '';

        foreach($class->getMethods(\ReflectionMethod::IS_PUBLIC) as $method)
        {
            $args = [];
            $comments = [];
            foreach($method->getParameters() as $param)
            {
                // 方法参数定义
                $args[] = $this->getMethodParamDefine($param);
                $comments[] = '@var ' . ReflectionUtil::getTypeComments($param->getType()) . ' $' . $param->name;
            }
            $comments[] = '@return ' . ReflectionUtil::getTypeComments($method->getReturnType());
            $args = implode(', ', $args);
            if([] === $comments)
            {
                $comment = '';
            }
            else
            {
                $comment = implode(PHP_EOL . '     * ', $comments);
                $comment = <<<COMMENT

    /**
     * {$comment}
     */
COMMENT;
            }
            if($method->isStatic())
            {
                $static = ' static';
            }
            else
            {
                $static = '';
            }
            $result .= <<<CODE
{$comment}
    public{$static} function {$method->name}({$args}){}

CODE;
        }

        return $result;
    }

    /**
     * 生成类属性
     *
     * @param \ReflectionClass $class
     * @return string
     */
    public function getClassProperties($class)
    {
        $result = '';
        foreach($class->getProperties(\ReflectionProperty::IS_PUBLIC) as $property)
        {
            $static = $property->isStatic() ? ' static' : '';
            $name = $property->name;
            $result .= <<<CODE
    public{$static} \${$name};

CODE;
        }
        return $result;
    }

    /**
     * 生成接口
     *
     * @param \ReflectionClass $class
     * @return void
     */
    private function generateInterface($class)
    {
        $consts = $this->getClassConsts($class);
        $methods = $this->getClassMethods($class);

        $result = '<?php' . PHP_EOL;

        $className = $class->getShortName();
        $namespace = $class->getNamespaceName();
        if('' !== $namespace)
        {
            $namespace = 'namespace ' . $namespace . ';';
        }
        $result .= <<<CODE
{$namespace}

interface {$className}
{
{$consts}{$methods}
}

CODE;
        $path = $this->savePath . '/interfaces/' . str_replace('\\', '/', $class->getNamespaceName()) . '/';
        if(!is_dir($path))
        {
            mkdir($path, 0777, true);
        }
        file_put_contents($path . $class->getShortName() . '.php', $result);
    }

    /**
     * 生成trait
     *
     * @param \ReflectionClass $class
     * @return void
     */
    private function generateTrait($class)
    {
        $consts = $this->getClassConsts($class);
        $methods = $this->getClassMethods($class);
        $properties = $this->getClassProperties($class);

        $result = '<?php' . PHP_EOL;

        $className = $class->getShortName();
        $namespace = $class->getNamespaceName();
        if('' !== $namespace)
        {
            $namespace = 'namespace ' . $namespace . ';';
        }
        $result .= <<<CODE
{$namespace}

trait {$className}
{
{$consts}{$properties}{$methods}
}

CODE;
        $path = $this->savePath . '/traits/' . str_replace('\\', '/', $class->getNamespaceName()) . '/';
        if(!is_dir($path))
        {
            mkdir($path, 0777, true);
        }
        file_put_contents($path . $class->getShortName() . '.php', $result);
    }

    /**
     * 生成类
     *
     * @param \ReflectionClass $class
     * @return void
     */
    private function generateClass($class)
    {
        $consts = $this->getClassConsts($class);
        $methods = $this->getClassMethods($class);
        $properties = $this->getClassProperties($class);

        $result = '<?php' . PHP_EOL;

        $className = $class->getShortName();
        $namespace = $class->getNamespaceName();
        if('' !== $namespace)
        {
            $namespace = 'namespace ' . $namespace . ';';
        }
        $result .= <<<CODE
{$namespace}

class {$className}
{
{$consts}{$properties}{$methods}
}

CODE;
        $path = $this->savePath . '/classes/' . str_replace('\\', '/', $class->getNamespaceName()) . '/';
        if(!is_dir($path))
        {
            mkdir($path, 0777, true);
        }
        file_put_contents($path . $class->getShortName() . '.php', $result);
    }

    /**
     * Get reflectionExtension
     *
     * @return \ReflectionExtension
     */ 
    public function getRef()
    {
        return $this->ref;
    }
}