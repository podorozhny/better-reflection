<?php

namespace Roave\BetterReflectionTest\Reflector;

use Roave\BetterReflection\Identifier\Identifier;
use Roave\BetterReflection\Identifier\IdentifierType;
use Roave\BetterReflection\Reflection\ReflectionClass;
use Roave\BetterReflection\Reflection\ReflectionFunction;
use Roave\BetterReflection\Reflector\ClassReflector;
use Roave\BetterReflection\Reflector\Exception\IdentifierNotFound;
use Roave\BetterReflection\Reflector\FunctionReflector;
use Roave\BetterReflection\SourceLocator\Ast\Exception\ParseToAstFailure;
use Roave\BetterReflection\SourceLocator\Ast\Locator;
use Roave\BetterReflection\SourceLocator\Located\LocatedSource;
use Roave\BetterReflection\SourceLocator\Type\StringSourceLocator;

/**
 * @covers \Roave\BetterReflection\SourceLocator\Ast\Locator
 */
class LocatorTest extends \PHPUnit_Framework_TestCase
{
    private function getIdentifier($name, $type)
    {
        return new Identifier($name, new IdentifierType($type));
    }

    public function testReflectingWithinNamespace()
    {
        $php = '<?php
        namespace Foo;
        class Bar {}
        ';

        $classInfo = (new Locator())->findReflection(
            new ClassReflector(new StringSourceLocator($php)),
            new LocatedSource($php, null),
            $this->getIdentifier('Foo\Bar', IdentifierType::IDENTIFIER_CLASS)
        );

        $this->assertInstanceOf(ReflectionClass::class, $classInfo);
    }

    public function testReflectingTopLevelClass()
    {
        $php = '<?php
        class Foo {}
        ';

        $classInfo = (new Locator())->findReflection(
            new ClassReflector(new StringSourceLocator($php)),
            new LocatedSource($php, null),
            $this->getIdentifier('Foo', IdentifierType::IDENTIFIER_CLASS)
        );

        $this->assertInstanceOf(ReflectionClass::class, $classInfo);
    }

    public function testReflectingTopLevelFunction()
    {
        $php = '<?php
        function foo() {}
        ';

        $functionInfo = (new Locator())->findReflection(
            new FunctionReflector(new StringSourceLocator($php)),
            new LocatedSource($php, null),
            $this->getIdentifier('foo', IdentifierType::IDENTIFIER_FUNCTION)
        );

        $this->assertInstanceOf(ReflectionFunction::class, $functionInfo);
    }

    public function testReflectThrowsExeptionWhenClassNotFoundAndNoNodesExist()
    {
        $php = '<?php';

        $this->expectException(IdentifierNotFound::class);
        (new Locator())->findReflection(
            new ClassReflector(new StringSourceLocator($php)),
            new LocatedSource($php, null),
            $this->getIdentifier('Foo', IdentifierType::IDENTIFIER_CLASS)
        );
    }

    public function testReflectThrowsExeptionWhenClassNotFoundButNodesExist()
    {
        $php = "<?php
        namespace Foo;
        echo 'Hello world';
        ";

        $this->expectException(IdentifierNotFound::class);
        (new Locator())->findReflection(
            new ClassReflector(new StringSourceLocator($php)),
            new LocatedSource($php, null),
            $this->getIdentifier('Foo', IdentifierType::IDENTIFIER_CLASS)
        );
    }

    public function testFindReflectionsOfTypeThrowsParseToAstFailureExceptionWithInvalidCode()
    {
        $locator = new Locator();

        $phpCode = '<?php syntax error';

        $identifierType = new IdentifierType(IdentifierType::IDENTIFIER_CLASS);
        $sourceLocator = new StringSourceLocator($phpCode);
        $reflector = new ClassReflector($sourceLocator);

        $locatedSource = new LocatedSource($phpCode, null);

        $this->expectException(ParseToAstFailure::class);
        $locator->findReflectionsOfType($reflector, $locatedSource, $identifierType);
    }
}
