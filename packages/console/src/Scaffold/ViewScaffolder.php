<?php

declare(strict_types=1);

namespace Velm\Console\Scaffold;

use Velm\Registry;

final class ViewScaffolder
{
    /**
     * @return array{path: string, technical: string, viewStem: string, fileStem: string}
     */
    public function scaffold(
        string $modelInput,
        string $moduleName,
        string $modulePath,
        bool $fromModel = true,
        bool $force = false,
        ?Registry $registry = null,
    ): array {
        $technical = $this->resolveTechnical($modelInput, $moduleName, $registry);
        [$fileStem, $viewStem, $technical] = $this->normalizeForViews($technical, $moduleName, $registry);

        $target = $modulePath.'/views/'.$fileStem.'.php';

        if (is_file($target) && ! $force) {
            throw new \RuntimeException(
                "{$target} already exists — pass --force to overwrite.",
            );
        }

        if (! is_dir($modulePath.'/views') && ! mkdir($modulePath.'/views', 0775, true) && ! is_dir($modulePath.'/views')) {
            throw new \RuntimeException("Could not create views directory under {$modulePath}.");
        }

        if ($fromModel) {
            if ($registry === null) {
                throw new \InvalidArgumentException(
                    "Cannot introspect {$technical} — pass --minimal for a stub view, or fix module roots.",
                );
            }

            $built = (new ViewScaffoldBuilder)->build($registry, $technical);
        } else {
            $title = $this->titleFromStem($fileStem);
            $built = [
                'list' => ["'name'"],
                'sections' => [
                    ['id' => 'main', 'title' => $title, 'fields' => ["'name'"]],
                ],
            ];
        }

        $title = $this->titleFromStem($fileStem);

        file_put_contents(
            $target,
            $this->viewContents($technical, $viewStem, $title, $built['list'], $built['sections']),
        );

        ManifestPatcher::appendData($modulePath.'/__velm__.php', "views/{$fileStem}.php");

        return [
            'path' => $target,
            'technical' => $technical,
            'viewStem' => $viewStem,
            'fileStem' => $fileStem,
        ];
    }

    public function resolveTechnical(string $modelInput, string $moduleName, ?Registry $registry): string
    {
        $modelInput = strtolower(trim($modelInput));

        if ($registry !== null) {
            if ($registry->has($modelInput)) {
                return $modelInput;
            }

            $suffix = '.'.$modelInput;
            $matches = [];

            foreach (array_keys($registry->models()) as $name) {
                if (str_ends_with($name, $suffix)) {
                    $matches[] = $name;
                }
            }

            if (count($matches) === 1) {
                return $matches[0];
            }
        }

        if (! str_contains($modelInput, '.')) {
            return "{$moduleName}.{$modelInput}";
        }

        return $modelInput;
    }

    /**
     * @return array{0: string, 1: string, 2: string} file stem, view stem, technical name
     */
    public function normalizeForViews(
        string $technical,
        string $moduleName,
        ?Registry $registry = null,
    ): array {
        $technical = strtolower(trim($technical));

        if ($technical === '') {
            throw new \InvalidArgumentException('Model name must not be empty.');
        }

        $parts = explode('.', $technical);
        $fileStem = $parts[array_key_last($parts)] ?? $technical;

        if ($parts === [] || ! preg_match('/^[a-z][a-z0-9_]{0,49}$/', $fileStem)) {
            throw new \InvalidArgumentException('Invalid model suffix for views.');
        }

        if (isset($parts[0]) && $parts[0] === $moduleName) {
            $rest = array_slice($parts, 1);
        } else {
            $rest = count($parts) > 1 ? array_slice($parts, 1) : $parts;
        }

        $viewStem = $rest !== [] ? implode('_', $rest) : $fileStem;

        return [$fileStem, $viewStem, $technical];
    }

    /**
     * @param  list<string>  $listColumns
     * @param  list<array{id: string, title: string, fields: list<string>}>  $sections
     */
    private function viewContents(
        string $technical,
        string $viewStem,
        string $title,
        array $listColumns,
        array $sections,
    ): string {
        $listBody = implode(",\n                ", $listColumns);
        $sectionBlocks = [];

        foreach ($sections as $section) {
            $fields = implode(",\n                ", $section['fields']);
            $sectionBlocks[] = <<<PHP
            ->section('{$section['id']}', '{$section['title']}', [
                {$fields},
            ])
PHP;
        }

        $formSections = implode("\n", $sectionBlocks);

        return <<<PHP
<?php

declare(strict_types=1);

use Velm\Views\Authoring\Field;
use Velm\Views\Authoring\FormView;
use Velm\Views\Authoring\ListView;
use Velm\Views\Data\ViewsData;

return ViewsData::make()
    ->views(
        ListView::make('{$viewStem}.list')
            ->model('{$technical}')
            ->title('{$title}')
            ->formView('{$viewStem}.form')
            ->columns([
                {$listBody},
            ]),
        FormView::make('{$viewStem}.form')
            ->model('{$technical}')
{$formSections}
    );

PHP;
    }

    private function titleFromStem(string $stem): string
    {
        return implode(' ', array_map(
            static fn (string $part): string => $part !== '' ? ucfirst($part) : '',
            explode('_', $stem),
        ));
    }
}
