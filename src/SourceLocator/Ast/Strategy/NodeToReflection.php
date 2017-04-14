<?php

namespace Roave\BetterReflection\SourceLocator\Ast\Strategy;

use Roave\BetterReflection\Reflector\Reflector;
use Roave\BetterReflection\SourceLocator\Located\LocatedSource;
use Roave\BetterReflection\Reflection\ReflectionClass;
use Roave\BetterReflection\Reflection\ReflectionFunction;
use Roave\BetterReflection\Reflection\Reflection;
use PhpParser\Node;

/**
 * @internal
 */
class NodeToReflection implements AstConversionStrategy
{
    /**
     * Take an AST node in some located source (potentially in a namespace) and
     * convert it to a Reflection
     *
     * @param Reflector $reflector
     * @param Node $node
     * @param LocatedSource $locatedSource
     * @param Node\Stmt\Namespace_|null $namespace
     * @return Reflection|null
     */
    public function __invoke(Reflector $reflector, Node $node, LocatedSource $locatedSource, Node\Stmt\Namespace_ $namespace = null)
    {
        if ($node instanceof Node\Stmt\ClassLike) {
            return ReflectionClass::createFromNode(
                $reflector,
                $node,
                $locatedSource,
                $namespace
            );
        }

        if ($node instanceof Node\FunctionLike) {
            return ReflectionFunction::createFromNode(
                $reflector,
                $node,
                $locatedSource,
                $namespace
            );
        }

        return null;
    }
}
