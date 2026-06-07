<?php

declare(strict_types=1);

namespace Velm\Admin\Arch;

use Velm\Environment;
use Velm\Fields\CharField;
use Velm\Fields\TextField;

final class AnalyticsSearchDomain
{
    public function __construct(
        private readonly ListDomainBuilder $domainBuilder = new ListDomainBuilder,
    ) {}

    /**
     * @param  list<mixed>|list<list<mixed>>  $staticDomain
     * @return list<mixed>|list<list<mixed>>
     */
    public function build(string $model, Environment $env, string $search, array $staticDomain = []): array
    {
        if (trim($search) === '') {
            return $staticDomain;
        }

        $modelClass = $env->registry->modelClass($model);
        $fields = [];

        foreach ($modelClass::fields() as $name => $field) {
            if ($field instanceof CharField || $field instanceof TextField) {
                $fields[] = ['name' => $name];
            }
        }

        return $this->domainBuilder->build(
            [
                'model' => $model,
                'fields' => $fields,
                'domain' => $staticDomain,
            ],
            $env,
            new ListQuery(search: $search),
        );
    }
}
