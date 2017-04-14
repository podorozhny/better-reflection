<?php

namespace Roave\BetterReflectionTest\TypesFinder;

use Roave\BetterReflection\Reflection\ReflectionClass;
use Roave\BetterReflection\Reflection\ReflectionFunction;
use Roave\BetterReflection\Reflection\ReflectionMethod;
use Roave\BetterReflection\Reflector\ClassReflector;
use Roave\BetterReflection\SourceLocator\Located\LocatedSource;
use Roave\BetterReflection\SourceLocator\Type\StringSourceLocator;
use Roave\BetterReflection\TypesFinder\FindParameterType;
use PhpParser\Node\Param as ParamNode;
use phpDocumentor\Reflection\Types;

/**
 * @covers \Roave\BetterReflection\TypesFinder\FindParameterType
 */
class FindParameterTypeTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @return array
     */
    public function parameterTypeProvider()
    {
        return [
            ['@param int|string $foo', 'foo', [Types\Integer::class, Types\String_::class]],
            ['@param array $foo', 'foo', [Types\Array_::class]],
            ['@param \stdClass $foo', 'foo', [Types\Object_::class]],
            ['@param int|int[]|int[][] $foo', 'foo', [Types\Integer::class, Types\Array_::class, Types\Array_::class]],
            ['', 'foo', []],
        ];
    }

    public function testNamespaceResolutionForProperty()
    {
        $php = '<?php
            namespace MyNamespace;

            use Psr\Log\LoggerInterface;

            class ThingThatLogs
            {
                /**
                 * @param LoggerInterface $bar
                 */
                public function foo($bar) {}
            }
        ';

        $param = (new ClassReflector(new StringSourceLocator($php)))
            ->reflect('MyNamespace\ThingThatLogs')
            ->getMethod('foo')
            ->getParameter('bar');

        $this->assertSame(['\Psr\Log\LoggerInterface'], $param->getDocBlockTypeStrings());
    }

    /**
     * @param string $docBlock
     * @param string $nodeName
     * @param string[] $expectedInstances
     * @dataProvider parameterTypeProvider
     */
    public function testFindParameterTypeForFunction($docBlock, $nodeName, $expectedInstances)
    {
        $node = new ParamNode($nodeName);
        $docBlock = "/**\n * $docBlock\n */";

        $function = $this->createMock(ReflectionFunction::class);

        $function
            ->expects($this->once())
            ->method('getDocComment')
            ->will($this->returnValue($docBlock));

        $function
            ->expects($this->once())
            ->method('getLocatedSource')
            ->will($this->returnValue(new LocatedSource('<?php', null)));

        /* @var ReflectionFunction $function */
        $foundTypes = (new FindParameterType())->__invoke($function, $node);

        $this->assertCount(count($expectedInstances), $foundTypes);

        foreach ($expectedInstances as $i => $expectedInstance) {
            $this->assertInstanceOf($expectedInstance, $foundTypes[$i]);
        }
    }

    /**
     * @param string $docBlock
     * @param string $nodeName
     * @param string[] $expectedInstances
     * @dataProvider parameterTypeProvider
     */
    public function testFindParameterTypeForMethod($docBlock, $nodeName, $expectedInstances)
    {
        $node = new ParamNode($nodeName);
        $docBlock = "/**\n * $docBlock\n */";

        $class = $this->createMock(ReflectionClass::class);

        $class
            ->expects($this->once())
            ->method('getLocatedSource')
            ->will($this->returnValue(new LocatedSource('<?php', null)));

        $method = $this->createMock(ReflectionMethod::class);

        $method
            ->expects($this->once())
            ->method('getDocComment')
            ->will($this->returnValue($docBlock));

        $method
            ->expects($this->once())
            ->method('getDeclaringClass')
            ->will($this->returnValue($class));

        /* @var ReflectionMethod $method */
        $foundTypes = (new FindParameterType())->__invoke($method, $node);

        $this->assertCount(count($expectedInstances), $foundTypes);

        foreach ($expectedInstances as $i => $expectedInstance) {
            $this->assertInstanceOf($expectedInstance, $foundTypes[$i]);
        }
    }
}
