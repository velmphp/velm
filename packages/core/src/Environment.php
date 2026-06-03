<?php

declare(strict_types=1);

namespace Velm;

use Velm\Database\Connection;
use Velm\Exception\AccessDeniedException;
use Velm\Fields\Many2oneField;
use Velm\Recordset\Recordset;
use Velm\Support\VelmDatetime;

final class Environment
{
    public const int SUPERUSER_ID = 1;

    private bool $aclBypass = false;

    /** @var array<string, bool> */
    private array $accessCache = [];

    /** @var list<int>|null */
    private ?array $userGroupsCache = null;

    /** @var array<string, list<list<mixed>>> */
    private array $recordRulesCache = [];

    public function __construct(
        public readonly Connection $connection,
        public readonly Registry $registry,
        public readonly ?int $uid = self::SUPERUSER_ID,
        /** @var array<string, mixed> */
        public readonly array $context = [],
        public readonly RecordCache $cache = new RecordCache,
    ) {}

    public function model(string $name): Recordset
    {
        $class = $this->registry->modelClass($name);

        return new Recordset($this, $class, []);
    }

    /**
     * @param  list<int>  $ids
     */
    public function browse(string $name, array $ids): Recordset
    {
        $class = $this->registry->modelClass($name);

        return new Recordset($this, $class, array_values($ids));
    }

    public function isSuperuser(): bool
    {
        return $this->uid === self::SUPERUSER_ID;
    }

    public function companyId(): ?int
    {
        $id = $this->context['company_id'] ?? null;

        if ($id === null || $id === '') {
            return null;
        }

        return (int) $id;
    }

    /**
     * Active company timezone for datetime display and form input (defaults to UTC).
     */
    public function timezone(): string
    {
        $tz = $this->context['timezone'] ?? null;

        if (! is_string($tz) || $tz === '') {
            return 'UTC';
        }

        return VelmDatetime::normalizeTimezone($tz);
    }

    /**
     * Load {@see res.company} timezone for binding into request context.
     */
    public function resolveCompanyTimezone(?int $companyId = null): string
    {
        $companyId ??= $this->companyId();

        if ($companyId === null || ! $this->registry->has('res.company')) {
            return 'UTC';
        }

        return $this->withAclBypass(function () use ($companyId): string {
            $rows = $this->browse('res.company', [$companyId])->read(['timezone']);
            $tz = (string) ($rows[0]['timezone'] ?? 'UTC');

            return VelmDatetime::normalizeTimezone($tz);
        });
    }

    /**
     * Active company from the cookie/request, validated against allowed companies.
     */
    public function resolveActiveCompanyId(?int $requestedFromCookie): ?int
    {
        $allowed = $this->allowedCompanyIds();

        if ($requestedFromCookie !== null && $requestedFromCookie > 0) {
            if (($this->isSuperuser() || in_array($requestedFromCookie, $allowed, true))
                && $this->companyExists($requestedFromCookie)) {
                return $requestedFromCookie;
            }
        }

        if ($this->allowsAllCompaniesMode()) {
            return null;
        }

        return $allowed[0] ?? null;
    }

    public function allowsAllCompaniesMode(): bool
    {
        return $this->isSuperuser();
    }

    public function userDefaultCompanyId(): ?int
    {
        if ($this->uid === null || ! $this->registry->has('res.users')) {
            return null;
        }

        return $this->withAclBypass(function (): ?int {
            $users = $this->model('res.users')->search([['id', '=', $this->uid]], limit: 1);

            if ($users->count() === 0) {
                return null;
            }

            $row = $users->read(['company_id'])[0] ?? [];
            $companyId = $row['company_id'] ?? null;

            if ($companyId === null || $companyId === '') {
                return null;
            }

            return (int) $companyId;
        });
    }

    /**
     * @return list<int>
     */
    public function allowedCompanyIds(): array
    {
        if (! $this->registry->has('res.company')) {
            return [];
        }

        if ($this->isSuperuser()) {
            return $this->withAclBypass(function (): array {
                $ids = [];

                foreach ($this->model('res.company')->search()->read(['id']) as $row) {
                    $id = (int) ($row['id'] ?? 0);

                    if ($id > 0) {
                        $ids[] = $id;
                    }
                }

                return $ids;
            });
        }

        $default = $this->userDefaultCompanyId();

        return $default !== null && $default > 0 ? [$default] : [];
    }

    public function modelHasCompanyField(string $modelName): bool
    {
        if (! $this->registry->has($modelName)) {
            return false;
        }

        $fields = $this->registry->hasFieldSet($modelName)
            ? $this->registry->fieldSet($modelName)
            : $this->registry->modelClass($modelName)::fields();

        $field = $fields['company_id'] ?? null;

        return $field instanceof Many2oneField && $field->comodel === 'res.company';
    }

    /**
     * Domain leaves applied to search/read/write when a company is active.
     *
     * @return list<list<mixed>>
     */
    public function companySearchConstraints(string $modelName): array
    {
        $companyId = $this->companyId();

        if ($companyId === null || ! $this->modelHasCompanyField($modelName)) {
            return [];
        }

        return [['company_id', '=', $companyId]];
    }

    /**
     * @param  array<string, mixed>  $values
     * @return array<string, mixed>
     */
    public function enforceCompanyOnCreate(string $modelName, array $values): array
    {
        if (! $this->modelHasCompanyField($modelName)) {
            return $values;
        }

        $companyId = $this->companyId();

        if ($companyId === null) {
            return $values;
        }

        if (! array_key_exists('company_id', $values) || $values['company_id'] === null || $values['company_id'] === '') {
            $values['company_id'] = $companyId;

            return $values;
        }

        if ((int) $values['company_id'] !== $companyId) {
            throw AccessDeniedException::forCompanyMismatch($modelName, $this->uid);
        }

        return $values;
    }

    /**
     * @param  array<string, mixed>  $values
     * @return array<string, mixed>
     */
    public function enforceCompanyOnWrite(string $modelName, array $values): array
    {
        if (! array_key_exists('company_id', $values) || ! $this->modelHasCompanyField($modelName)) {
            return $values;
        }

        $companyId = $this->companyId();

        if ($companyId === null) {
            return $values;
        }

        $written = $values['company_id'];

        if ($written === null || $written === '') {
            return $values;
        }

        if ((int) $written !== $companyId) {
            throw AccessDeniedException::forCompanyMismatch($modelName, $this->uid);
        }

        return $values;
    }

    public function companyExists(int $id): bool
    {
        if (! $this->registry->has('res.company')) {
            return false;
        }

        return $this->withAclBypass(
            fn (): bool => $this->model('res.company')->search([['id', '=', $id]], limit: 1)->count() > 0,
        );
    }

    /**
     * @param  array<string, mixed>  $context
     */
    public function withContext(array $context): self
    {
        return new self(
            $this->connection,
            $this->registry,
            $this->uid,
            [...$this->context, ...$context],
            $this->cache,
        );
    }

    /**
     * @template T
     *
     * @param  callable(): T  $callback
     * @return T
     */
    public function withAclBypass(callable $callback): mixed
    {
        $previous = $this->aclBypass;
        $this->aclBypass = true;

        try {
            return $callback();
        } finally {
            $this->aclBypass = $previous;
        }
    }

    /**
     * @return list<int>
     */
    public function userGroupIds(): array
    {
        if ($this->userGroupsCache !== null) {
            return $this->userGroupsCache;
        }

        if ($this->uid === null || ! $this->registry->has('res.users')) {
            $this->userGroupsCache = [];

            return $this->userGroupsCache;
        }

        $this->userGroupsCache = $this->withAclBypass(function (): array {
            $users = $this->model('res.users')->search([['id', '=', $this->uid]], limit: 1);

            if ($users->count() === 0) {
                return [];
            }

            $rows = $users->read(['group_ids']);

            return array_values(array_map(intval(...), $rows[0]['group_ids'] ?? []));
        });

        return $this->userGroupsCache;
    }

    public function hasAccess(string $modelName, string $perm): bool
    {
        return $this->accessGranted($modelName, $perm);
    }

    public function checkAccess(string $modelName, string $perm): void
    {
        if ($this->accessGranted($modelName, $perm)) {
            return;
        }

        throw AccessDeniedException::forPermission($modelName, $perm, $this->uid);
    }

    /**
     * @return array{read: bool, write: bool, create: bool, unlink: bool}
     */
    public function accessFlags(string $modelName): array
    {
        return [
            'read' => $this->hasAccess($modelName, 'read'),
            'write' => $this->hasAccess($modelName, 'write'),
            'create' => $this->hasAccess($modelName, 'create'),
            'unlink' => $this->hasAccess($modelName, 'unlink'),
        ];
    }

    private function accessGranted(string $modelName, string $perm): bool
    {
        if ($this->isSuperuser() || $this->aclBypass) {
            return true;
        }

        if (! $this->registry->has('ir.model.access')) {
            return true;
        }

        $cacheKey = $modelName.'|'.$perm;

        if (array_key_exists($cacheKey, $this->accessCache)) {
            return $this->accessCache[$cacheKey];
        }

        $granted = $this->withAclBypass(function () use ($modelName, $perm): bool {
            $access = $this->model('ir.model.access');
            $domain = [
                ['model', '=', $modelName],
                ['perm_'.$perm, '=', true],
            ];

            if ($this->uid === null) {
                return $access->search([...$domain, ['group_id', '=', null]], limit: 1)->count() > 0;
            }

            if ($access->search([...$domain, ['group_id', '=', null]], limit: 1)->count() > 0) {
                return true;
            }

            $groupIds = $this->userGroupIds();

            if ($groupIds === []) {
                return false;
            }

            return $access->search([...$domain, ['group_id', 'in', $groupIds]], limit: 1)->count() > 0;
        });

        $this->accessCache[$cacheKey] = $granted;

        return $granted;
    }

    /**
     * Domain leaves from `ir.rule` rows to AND into `search()` for *modelName* / *perm*.
     *
     * @return list<list<mixed>>
     */
    public function collectRecordRules(string $modelName, string $perm): array
    {
        if ($this->isSuperuser() || $this->aclBypass) {
            return [];
        }

        if (! $this->registry->has('ir.rule')) {
            return [];
        }

        $cacheKey = $modelName.'|'.$perm;

        if (array_key_exists($cacheKey, $this->recordRulesCache)) {
            return $this->recordRulesCache[$cacheKey];
        }

        $leaves = $this->withAclBypass(function () use ($modelName, $perm): array {
            $ruleModel = $this->model('ir.rule');
            $baseDomain = [
                ['model', '=', $modelName],
                ['perm_'.$perm, '=', true],
            ];

            $ruleIds = [];

            if ($this->uid === null) {
                $ruleIds = $ruleModel->search([...$baseDomain, ['group_id', '=', null]])->ids();
            } else {
                $ruleIds = $ruleModel->search([...$baseDomain, ['group_id', '=', null]])->ids();
                $groupIds = $this->userGroupIds();

                if ($groupIds !== []) {
                    $ruleIds = array_values(array_unique([
                        ...$ruleIds,
                        ...$ruleModel->search([...$baseDomain, ['group_id', 'in', $groupIds]])->ids(),
                    ]));
                }
            }

            if ($ruleIds === []) {
                return [];
            }

            $out = [];

            foreach ($this->browse('ir.rule', $ruleIds)->read(['domain']) as $row) {
                $raw = json_decode((string) ($row['domain'] ?? '[]'), true);

                if (! is_array($raw)) {
                    throw new \InvalidArgumentException('ir.rule.domain must be a JSON array.');
                }

                $out = [...$out, ...$this->resolveRuleLeaves($raw)];
            }

            return $out;
        });

        $this->recordRulesCache[$cacheKey] = $leaves;

        return $leaves;
    }

    public function resolveRulePlaceholder(string $name): mixed
    {
        if ($name === 'uid' || $name === 'user_id') {
            return $this->uid;
        }

        if ($name === 'company_id') {
            return $this->context['company_id'] ?? null;
        }

        throw new \InvalidArgumentException("Unknown ir.rule placeholder {$name}.");
    }

    /**
     * @param  list<mixed>  $rawDomain
     * @return list<list<mixed>>
     */
    private function resolveRuleLeaves(array $rawDomain): array
    {
        $resolved = [];

        foreach ($rawDomain as $leaf) {
            if (! is_array($leaf) || count($leaf) !== 3) {
                continue;
            }

            [$field, $operator, $value] = $leaf;

            if (is_array($value) && array_key_exists('placeholder', $value)) {
                $value = $this->resolveRulePlaceholder((string) $value['placeholder']);
            } elseif (is_array($value)) {
                $value = array_map(function (mixed $item): mixed {
                    if (is_array($item) && array_key_exists('placeholder', $item)) {
                        return $this->resolveRulePlaceholder((string) $item['placeholder']);
                    }

                    return $item;
                }, $value);
            }

            $resolved[] = [$field, $operator, $value];
        }

        return $resolved;
    }
}
