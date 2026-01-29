<?php

interface TenantManager
{
    public function getCurrentTenant(): ?string;

    public function setCurrentTenant(?string $tenant): void;

    public function listTenants(): array;

    public function availableTenants(): array;
}
