<?php

namespace Roave\BetterReflectionTest\NodeCompiler;

use Roave\BetterReflection\NodeCompiler\CompilerContext;
use Roave\BetterReflection\Reflector\ClassReflector;
use Roave\BetterReflection\SourceLocator\Type\StringSourceLocator;

/**
 * @covers \Roave\BetterReflection\NodeCompiler\CompilerContext
 */
class CompilerContextTest extends \PHPUnit_Framework_TestCase
{
    public function testCreatingContextWithoutSelf()
    {
        $reflector = new ClassReflector(new StringSourceLocator('<?php'));
        $context = new CompilerContext($reflector, null);

        $this->assertFalse($context->hasSelf());
        $this->assertSame($reflector, $context->getReflector());

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('The current context does not have a class for self');
        $context->getSelf();
    }

    public function testCreatingContextWithSelf()
    {
        $reflector = new ClassReflector(new StringSourceLocator('<?php class Foo {}'));
        $self = $reflector->reflect('Foo');

        $context = new CompilerContext($reflector, $self);

        $this->assertTrue($context->hasSelf());
        $this->assertSame($reflector, $context->getReflector());
        $this->assertSame($self, $context->getSelf());
    }
}
