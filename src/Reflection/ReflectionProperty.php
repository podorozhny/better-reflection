<?php

namespace Roave\BetterReflection\Reflection;

use Roave\BetterReflection\NodeCompiler\CompileNodeToValue;
use Roave\BetterReflection\NodeCompiler\CompilerContext;
use Roave\BetterReflection\Reflector\Reflector;
use Roave\BetterReflection\TypesFinder\FindPropertyType;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\Property as PropertyNode;
use phpDocumentor\Reflection\Type;

class ReflectionProperty implements \Reflector
{
    /**
     * @var ReflectionClass
     */
    private $declaringClass;

    /**
     * @var PropertyNode
     */
    private $node;

    /**
     * @var bool
     */
    private $declaredAtCompileTime = true;

    /**
     * @var Reflector
     */
    private $reflector;

    private function __construct()
    {
    }

    public static function export()
    {
        throw new \Exception('Unable to export statically');
    }

    /**
     * Create a reflection of a class's property by it's name
     *
     * @param string $className
     * @param string $propertyName
     * @return ReflectionMethod
     */
    public static function createFromName($className, $propertyName)
    {
        return ReflectionClass::createFromName($className)->getProperty($propertyName);
    }

    /**
     * Create a reflection of an instance's property by it's name
     *
     * @param object $instance
     * @param string $propertyName
     * @return ReflectionMethod
     */
    public static function createFromInstance($instance, $propertyName)
    {
        return ReflectionClass::createFromInstance($instance)->getProperty($propertyName);
    }

    /**
     * Return string representation of this little old property.
     *
     * @return string
     */
    public function __toString()
    {
        return sprintf(
            'Property [%s %s%s $%s ]',
            $this->isStatic() ? '' : ($this->isDefault() ? ' <default>' : ' <dynamic>'),
            $this->getVisibilityAsString(),
            $this->isStatic() ? ' static' : '',
            $this->getName()
        );
    }

    /**
     * @param Reflector $reflector
     * @param PropertyNode $node
     * @param ReflectionClass $declaringClass
     * @param bool $declaredAtCompileTime
     * @return ReflectionProperty
     */
    public static function createFromNode(
        Reflector $reflector,
        PropertyNode $node,
        ReflectionClass $declaringClass,
        $declaredAtCompileTime = true
    ) {
        $prop = new self();
        $prop->reflector = $reflector;
        $prop->node = $node;
        $prop->declaringClass = $declaringClass;
        $prop->declaredAtCompileTime = $declaredAtCompileTime;
        return $prop;
    }

    /**
     * @return string
     */
    private function getVisibilityAsString()
    {
        if ($this->isProtected()) {
            return 'protected';
        }

        if ($this->isPrivate()) {
            return 'private';
        }

        return 'public';
    }

    /**
     * Set the default visibility of this property. Use the core \ReflectionProperty::IS_* values as parameters, e.g.:
     *
     * @param int $newVisibility
     * @throws \InvalidArgumentException
     */
    public function setVisibility($newVisibility)
    {
        $this->node->flags &= ~Class_::MODIFIER_PRIVATE & ~Class_::MODIFIER_PROTECTED & ~Class_::MODIFIER_PUBLIC;

        switch ($newVisibility) {
            case \ReflectionProperty::IS_PRIVATE:
                $this->node->flags |= Class_::MODIFIER_PRIVATE;
                break;
            case \ReflectionProperty::IS_PROTECTED:
                $this->node->flags |= Class_::MODIFIER_PROTECTED;
                break;
            case \ReflectionProperty::IS_PUBLIC:
                $this->node->flags |= Class_::MODIFIER_PUBLIC;
                break;
            default:
                throw new \InvalidArgumentException('Visibility should be \ReflectionProperty::IS_PRIVATE, ::IS_PROTECTED or ::IS_PUBLIC constants');
        }
    }

    /**
     * Has the property been declared at compile-time?
     *
     * Note that unless the property is static, this is hard coded to return
     * true, because we are unable to reflect instances of classes, therefore
     * we can be sure that all properties are always declared at compile-time.
     *
     * @return bool
     */
    public function isDefault()
    {
        return $this->declaredAtCompileTime;
    }

    /**
     * Get the core-reflection-compatible modifier values.
     *
     * @return int
     */
    public function getModifiers()
    {
        $val = 0;
        $val += $this->isStatic() ? \ReflectionProperty::IS_STATIC : 0;
        $val += $this->isPublic() ? \ReflectionProperty::IS_PUBLIC : 0;
        $val += $this->isProtected() ? \ReflectionProperty::IS_PROTECTED : 0;
        $val += $this->isPrivate() ? \ReflectionProperty::IS_PRIVATE : 0;
        return $val;
    }

    /**
     * Get the name of the property.
     *
     * @return string
     */
    public function getName()
    {
        return $this->node->props[0]->name;
    }

    /**
     * Is the property private?
     *
     * @return bool
     */
    public function isPrivate()
    {
        return $this->node->isPrivate();
    }

    /**
     * Is the property protected?
     *
     * @return bool
     */
    public function isProtected()
    {
        return $this->node->isProtected();
    }

    /**
     * Is the property public?
     *
     * @return bool
     */
    public function isPublic()
    {
        return $this->node->isPublic();
    }

    /**
     * Is the property static?
     *
     * @return bool
     */
    public function isStatic()
    {
        return $this->node->isStatic();
    }

    /**
     * Get the DocBlock type hints as an array of strings.
     *
     * @return string[]
     */
    public function getDocBlockTypeStrings()
    {
        $stringTypes = [];

        foreach ($this->getDocBlockTypes() as $type) {
            $stringTypes[] = (string)$type;
        }
        return $stringTypes;
    }

    /**
     * Get the types defined in the DocBlocks. This returns an array because
     * the parameter may have multiple (compound) types specified (for example
     * when you type hint pipe-separated "string|null", in which case this
     * would return an array of Type objects, one for string, one for null.
     *
     * @return Type[]
     */
    public function getDocBlockTypes()
    {
        return (new FindPropertyType())->__invoke($this);
    }

    /**
     * @return ReflectionClass
     */
    public function getDeclaringClass()
    {
        return $this->declaringClass;
    }

    /**
     * @return string
     */
    public function getDocComment()
    {
        if (!$this->node->hasAttribute('comments')) {
            return '';
        }

        /* @var \PhpParser\Comment\Doc $comment */
        $comment = $this->node->getAttribute('comments')[0];
        return $comment->getReformattedText();
    }

    /**
     * Get the default value of the property (as defined before constructor is
     * called, when the property is defined)
     *
     * @return mixed
     */
    public function getDefaultValue()
    {
        $defaultValueNode = $this->node->props[0]->default;

        if (null === $defaultValueNode) {
            return null;
        }

        return (new CompileNodeToValue())->__invoke(
            $defaultValueNode,
            new CompilerContext($this->reflector, $this->getDeclaringClass())
        );
    }

    /**
     * {@inheritdoc}
     */
    public function __clone()
    {
        throw Exception\Uncloneable::fromClass(__CLASS__);
    }
}
