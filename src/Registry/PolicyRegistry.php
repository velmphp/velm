<?php

namespace Velm\Core\Registry;

use Illuminate\Support\Facades\Gate;
use Velm\Core\Auth\VelmPolicyDispatcher;
use Velm\Core\Models\LogicalModel;

class PolicyRegistry
{
    /**
     * The registry of policies in the format [modelLogicalName => [policyExtension1, policyExtension2, ...], ...]
     */
    private array $policies = [];

    public function register(string $modelLogicalName, string $policyExtension): void
    {
        if (! isset($this->policies[$modelLogicalName])) {
            $this->policies[$modelLogicalName] = [];
        }
        $this->policies[$modelLogicalName][] = $policyExtension;
    }

    public function getPoliciesForModel(string $modelLogicalName): array
    {
        return $this->policies[$modelLogicalName] ?? [];
    }

    public function discoverForModel(string $modelLogicalName): array
    {
        // discover from ../Policies/{ModelLogicalName}/*.php
        $extensions = velm()->registry()->models()->definitions($modelLogicalName);
        if (empty($extensions)) {
            return [];
        }
        $policies = [];
        foreach ($extensions as $extension) {
            // get the registered policy for the class
            $policies[] = Gate::getPolicyFor($extension);
        }

        return array_filter($policies);
    }

    public function bootPolicies(): void
    {
        Gate::before(function ($user, string $ability, array $arguments) {
            if (count($arguments) != 1) {
                return null;
            }

            // If instance of LogicalModel, use VelmPolicyDispatcher
            $model = $arguments[0];
            if ($model instanceof LogicalModel) {
                return VelmPolicyDispatcher::authorize($ability, [$user, $model]);
            }

            return null;
        });
    }
}
