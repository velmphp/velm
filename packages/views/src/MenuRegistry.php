<?php

declare(strict_types=1);

namespace Velm\Views;

use Velm\Environment;

final class MenuRegistry
{
    /**
     * @return list<array{menu: array<string, mixed>, children: list<array<string, mixed>>}>
     */
    public function tree(Environment $env): array
    {
        $rows = $env->model('ir.ui.menu')->search([], order: '"sequence" ASC, "id" ASC')->read();

        /** @var array<int, array{menu: array<string, mixed>, children: list<int>}> $nodes */
        $nodes = [];
        $rootIds = [];

        foreach ($rows as $row) {
            $id = (int) $row['id'];
            $nodes[$id] = ['menu' => $row, 'children' => []];
        }

        foreach ($nodes as $id => $node) {
            $parentId = $node['menu']['parent_id'];

            if ($parentId === null) {
                $rootIds[] = $id;

                continue;
            }

            $parentId = (int) $parentId;

            if (isset($nodes[$parentId])) {
                $nodes[$parentId]['children'][] = $id;
            }
        }

        return array_map(fn (int $id): array => $this->nodeTree($nodes, $id), $rootIds);
    }

    /**
     * @param  array<int, array{menu: array<string, mixed>, children: list<int>}>  $nodes
     * @return array{menu: array<string, mixed>, children: list<array<string, mixed>>}
     */
    private function nodeTree(array $nodes, int $id): array
    {
        $node = $nodes[$id];

        return [
            'menu' => $node['menu'],
            'children' => array_map(
                fn (int $childId): array => $this->nodeTree($nodes, $childId),
                $node['children'],
            ),
        ];
    }
}
