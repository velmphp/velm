<?php

declare(strict_types=1);

namespace Velm\Modules;

final readonly class ModuleUninstallPreview
{
    /**
     * @param  list<string>  $reverseDependencies
     * @param  list<string>  $modelExtensions
     * @param  list<string>  $systemBlockers
     */
    public function __construct(
        public string $module,
        public bool $canUninstall,
        public array $reverseDependencies = [],
        public array $modelExtensions = [],
        public array $systemBlockers = [],
    ) {}

    /**
     * @return list<string>
     */
    public function blockers(): array
    {
        $blockers = $this->systemBlockers;

        if ($this->reverseDependencies !== []) {
            $blockers[] = 'The following modules depend on it: '.implode(', ', $this->reverseDependencies);
        }

        $extensionsOnly = array_values(array_diff($this->modelExtensions, $this->reverseDependencies));

        if ($extensionsOnly !== []) {
            $blockers[] = 'The following modules extend its models: '.implode(', ', $extensionsOnly);
        }

        return $blockers;
    }
}
