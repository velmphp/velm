<?php

namespace Velm\Core\Compiler\Concerns;

use PhpParser\Node;
use PhpParser\NodeFinder;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\ParserFactory;
use PhpParser\PhpVersion;
use PhpParser\PrettyPrinter\Standard as PrettyPrinter;
use ReflectionClass;
use Velm\Core\Concerns\BelongsToVelmModule;
use Velm\Core\Domain\Models\VelmModel;
use Velm\Core\Domain\Models\VelmModelProxy;
use Velm\Core\Domain\Policies\VelmPolicy;
use Velm\Core\Domain\Policies\VelmPolicyProxy;
use Velm\Core\Modules\ModuleDescriptor;

trait CompilesDomainFilesUsingParser
{
    /* ============================================================
     | AST CORE
     |============================================================ */

    protected function parseFile(string $file): array
    {
        static $parser;

        $parser ??= new ParserFactory()->createForVersion(PhpVersion::getHostVersion());

        $ast = $parser->parse(file_get_contents($file));

        $traverser = new NodeTraverser;
        $traverser->addVisitor(new NameResolver);

        return $traverser->traverse($ast);
    }

    protected function resolveClassFromFile(string $file): string
    {
        $finder = new NodeFinder;
        $ast = $this->parseFile($file);

        $class = $finder->findFirstInstanceOf($ast, Node\Stmt\Class_::class);

        if (! $class || ! $class->namespacedName) {
            throw new \RuntimeException("No class found in {$file}");
        }

        return $class->namespacedName->toString();
    }

    /* ============================================================
     | PIPELINE COLLECTION
     |============================================================ */

    protected function collectPipelinesForBucket(array $sortedClasses): array
    {
        $pipelines = [];
        $finder = new NodeFinder;

        foreach ($sortedClasses as $class) {
            $this->ensureNoForeignClasses($class);

            $rc = new ReflectionClass($class);
            $ast = $this->parseFile($rc->getFileName());

            /** @var Node\Stmt\ClassMethod[] $methods */
            $methods = $finder->findInstanceOf($ast, Node\Stmt\ClassMethod::class);

            foreach ($methods as $method) {
                if ($method->isStatic()) {
                    continue;
                }

                if ($method->flags & Node\Stmt\Class_::MODIFIER_PRIVATE) {
                    continue;
                }

                $pipelines[$method->name->toString()][] = [
                    'class' => $class,
                    'method' => $method,
                ];
            }
        }

        return $pipelines;
    }

    /* ============================================================
     | IMPORTS
     |============================================================ */

    /**
     * @throws \ReflectionException
     */
    protected function compileImports(array $classes): array
    {
        $imports = [];

        foreach ($classes as $class) {
            $rc = new ReflectionClass($class);
            $file = $rc->getFileName();
            if (! $file) {
                continue;
            }

            foreach ($this->extractUseStatements($file) as $alias => $fqcn) {
                if (
                    is_subclass_of($fqcn, VelmModel::class)
                    && ! is_subclass_of($fqcn, VelmModelProxy::class)
                ) {
                    $fqcn = '\\Velm\\Models\\'.class_basename($fqcn);
                }

                $imports[$alias] = $fqcn;
            }
        }

        return $imports;
    }

    protected function extractUseStatements(string $filePath): array
    {
        $imports = [];
        $ast = $this->parseFile($filePath);

        foreach ($ast as $node) {
            if ($node instanceof Node\Stmt\Use_) {
                foreach ($node->uses as $use) {
                    $alias = $use->alias?->toString()
                        ?? $use->name->getLast();

                    $imports[$alias] = $use->name->toString();
                }
            }
        }

        return $imports;
    }

    /* ============================================================
     | METHOD RECONSTRUCTION
     |============================================================ */

    protected function reconstructMethod(
        Node\Stmt\ClassMethod $method,
        bool $asClosure = true
    ): string {
        $printer = new PrettyPrinter;

        $bodyStmts = $method->getStmts() ?? [];
        $body = $printer->prettyPrint($bodyStmts);

        // $this->super(...) → $super(...)
        $body = preg_replace('/\$this->super\s*\(/', '$super(', $body);

        $params = $this->generateParamsFromAst($method);
        $return = $this->returnTypeFromAst($method);

        if ($asClosure) {
            return <<<PHP
function (callable \$super{$params}){$return} {
{$this->indent($body, 1)}
}
PHP;
        }

        return <<<PHP
{$this->methodSignatureFromAst($method)}
{
{$this->indent($body, 1)}
}
PHP;
    }

    protected function generateParamsFromAst(Node\Stmt\ClassMethod $method): string
    {
        $printer = new PrettyPrinter;
        $params = [];

        foreach ($method->params as $param) {
            $code = '';

            if ($param->type instanceof Node\Name) {
                $type = $param->type->getAttribute('resolvedName')?->toString()
                    ?? $param->type->toString();

                $code .= $this->rewriteVelmType($type).' ';
            }

            if ($param->variadic) {
                $code .= '...';
            }

            $code .= '$'.$param->var->name;

            if ($param->default) {
                $code .= ' = '.$printer->prettyPrintExpr($param->default);
            }

            $params[] = $code;
        }

        return $params ? ', '.implode(', ', $params) : '';
    }

    protected function returnTypeFromAst(Node\Stmt\ClassMethod $method): string
    {
        if (! $method->returnType) {
            return ' :mixed';
        }

        if ($method->returnType instanceof Node\Name) {
            $type = $method->returnType->getAttribute('resolvedName')?->toString()
                ?? $method->returnType->toString();

            return ' :'.$this->rewriteVelmType($type);
        }

        return '';
    }

    protected function methodSignatureFromAst(Node\Stmt\ClassMethod $method): string
    {
        $visibility =
            $method->isPublic() ? 'public ' :
                ($method->isProtected() ? 'protected ' : 'private ');

        return $visibility.'function '.$method->name->toString().'('.ltrim($this->generateParamsFromAst($method), ', ').')'
            .$this->returnTypeFromAst($method);
    }

    /* ============================================================
     | TYPE REWRITING
     |============================================================ */

    protected function rewriteVelmType(string $fqcn): string
    {
        if (
            is_subclass_of($fqcn, VelmModel::class)
            && ! is_subclass_of($fqcn, VelmModelProxy::class)
        ) {
            return '\\Velm\\Models\\'.class_basename($fqcn);
        }

        if (
            is_subclass_of($fqcn, VelmPolicy::class)
            && ! is_subclass_of($fqcn, VelmPolicyProxy::class)
        ) {
            return '\\Velm\\Policies\\'.class_basename($fqcn);
        }

        return '\\'.ltrim($fqcn, '\\');
    }

    /* ============================================================
     | MODULE AUTONOMY (AST-CORRECT)
     |============================================================ */

    /**
     * @param  class-string<BelongsToVelmModule>  $sourceClass
     *
     * @throws \ReflectionException
     */
    protected function ensureNoForeignClasses(string $sourceClass): void
    {
        $sourceModule = $sourceClass::velm()->module;
        if (! $sourceModule) {
            return;
        }

        $rc = new ReflectionClass($sourceClass);
        $ast = $this->parseFile($rc->getFileName());
        $finder = new NodeFinder;

        $names = $finder->findInstanceOf($ast, Node\Name::class);

        foreach ($names as $name) {
            $fqcn = $name->getAttribute('resolvedName')?->toString();
            if (! $fqcn) {
                continue;
            }
            /** @var ModuleDescriptor|null $refModule */
            $refModule = $fqcn::velm()->module ?? null;
            if ($refModule && $refModule->packageName !== $sourceModule->packageName) {
                throw new \RuntimeException(
                    "Module '{$sourceModule->name}' cannot reference '{$fqcn}' from '{$refModule->name}'."
                );
            }
        }
    }

    /* ============================================================
     | UTILS
     |============================================================ */

    protected function indent(string $code, int $level): string
    {
        $pad = str_repeat('    ', $level);

        return implode("\n", array_map(
            fn ($l) => $pad.$l,
            explode("\n", $code)
        ));
    }
}
