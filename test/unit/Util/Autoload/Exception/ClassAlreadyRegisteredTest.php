<?php
declare(strict_types=1);

namespace Roave\BetterReflectionTest\Util\Autoload\Exception;

use Roave\BetterReflection\Reflection\ReflectionClass;
use Roave\BetterReflection\Util\Autoload\Exception\ClassAlreadyRegistered;

/**
 * @covers \Roave\BetterReflection\Util\Autoload\Exception\ClassAlreadyRegistered
 */
final class ClassAlreadyRegisteredTest extends \PHPUnit_Framework_TestCase
{
    public function testFromReflectionClass() : void
    {
        $className = uniqid('class name', true);

        /** @var ReflectionClass|\PHPUnit_Framework_MockObject_MockObject $reflection */
        $reflection = $this->createMock(ReflectionClass::class);
        $reflection->expects(self::any())->method('getName')->willReturn($className);

        $exception = ClassAlreadyRegistered::fromReflectionClass($reflection);

        self::assertInstanceOf(ClassAlreadyRegistered::class, $exception);
        self::assertSame(
            sprintf('Class %s already registered', $className),
            $exception->getMessage()
        );
    }
}
