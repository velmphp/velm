<?php

namespace Velm\Core\Compiler;

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;

final class VelmClassReferenceCollector extends NodeVisitorAbstract
{
    /** @var array<string, true> */
    public array $classes = [];

    public function enterNode(Node $node): null
    {
        // Foo::class
        if (
            $node instanceof Node\Expr\ClassConstFetch &&
            $node->class instanceof Node\Name
        ) {
            $this->classes[ltrim($node->class->toString(), '\\')] = true;

            return null;
        }

        // Fully qualified names (after NameResolver)
        if ($node instanceof Node\Name\FullyQualified) {
            $this->classes[ltrim($node->toString(), '\\')] = true;

            return null;
        }

        // new Foo()
        if ($node instanceof Node\Expr\New_ && $node->class instanceof Node\Name) {
            $this->classes[ltrim($node->class->toString(), '\\')] = true;

            return null;
        }

        // Foo::bar(), Foo::$prop
        if (
            ($node instanceof Node\Expr\StaticCall ||
                $node instanceof Node\Expr\StaticPropertyFetch)
            && $node->class instanceof Node\Name
        ) {
            $this->classes[ltrim($node->class->toString(), '\\')] = true;

            return null;
        }

        // Union / nullable types
        if ($node instanceof Node\UnionType || $node instanceof Node\NullableType) {
            foreach ($node->types as $type) {
                if ($type instanceof Node\Name) {
                    $this->classes[ltrim($type->toString(), '\\')] = true;
                }
            }

            return null;
        }

        return null;
    }
}
