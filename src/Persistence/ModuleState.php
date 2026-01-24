<?php

namespace Velm\Core\Persistence;

use Velm\Core\Modules\ModuleDescriptor;

final class ModuleState
{
    public function __construct(
        public readonly string $package,
        public readonly string $version,
        public readonly \DateTimeImmutable $installedAt,
        public readonly ?string $tenant = null,
        public bool $isEnabled = false,
        public ?\DateTimeImmutable $updatedAt = null,
        public ?\DateTimeImmutable $enabledAt = null,
        public ?\DateTimeImmutable $disabledAt = null,
    ) {}

    public function descriptor(): ModuleDescriptor
    {
        return velm()->registry()->modules()->findOrFail($this->package);
    }
}
