<?php

namespace Roave\BetterReflectionTest\Util\Autoload\ClassLoaderMethod\Exception;

use Roave\BetterReflection\Reflection\ReflectionClass;
use Roave\BetterReflection\Util\Autoload\ClassLoaderMethod\Exception\SignatureCheckFailed;

/**
 * @covers \Roave\BetterReflection\Util\Autoload\ClassLoaderMethod\Exception\SignatureCheckFailed
 */
class SignatureCheckFailedTest extends \PHPUnit_Framework_TestCase
{
    public function testFromReflectionClass()
    {
        $className = uniqid('class name', true);
        $reflection = $this->createMock(ReflectionClass::class);
        $reflection->expects(self::any())->method('getName')->willReturn($className);

        $exception = SignatureCheckFailed::fromReflectionClass($reflection);

        self::assertInstanceOf(SignatureCheckFailed::class, $exception);
        self::assertSame(
            sprintf('Failed to verify the signature of the cached file for %s', $className),
            $exception->getMessage()
        );
    }
}
