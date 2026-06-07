<?php

declare(strict_types=1);

use Illuminate\Auth\GenericUser;
use Livewire\Livewire;
use Velm\Admin\Pages\WorkflowBuilderPage;
use Velm\Admin\Tests\TestCase;
use Velm\Framework\VelmManager;

uses(TestCase::class);

beforeEach(function (): void {
    if (! extension_loaded('pdo_sqlite')) {
        skip('The pdo_sqlite extension is required.');
    }

    app(VelmManager::class)->install('workflow');
});

test('workflow builder page creates new workflow config', function (): void {
    Livewire::actingAs(new GenericUser(['id' => 1, 'email' => 'admin@test']))
        ->test(WorkflowBuilderPage::class)
        ->assertOk()
        ->assertSet('config', fn (array $config): bool => $config !== [])
        ->assertSee('New workflow', false);
});

test('workflow builder page loads existing workflow by id', function (): void {
    $env = app(\Velm\Environment::class);
    $workflowId = $env->model('workflow.definition')->search(limit: 1)->ids()[0];

    expect($workflowId)->not->toBeNull();

    $page = Livewire::actingAs(new GenericUser(['id' => 1, 'email' => 'admin@test']))
        ->test(WorkflowBuilderPage::class, ['workflowId' => $workflowId]);

    expect($page->instance()->getTitle())->not->toBe('New workflow')
        ->and($page->instance()->config['meta']['name'] ?? null)->not->toBeNull();
});

test('workflow builder page returns 404 for missing workflow id', function (): void {
    Livewire::actingAs(new GenericUser(['id' => 1, 'email' => 'admin@test']))
        ->test(WorkflowBuilderPage::class, ['workflowId' => 999999])
        ->assertStatus(404);
});
