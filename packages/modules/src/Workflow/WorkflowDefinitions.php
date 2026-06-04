<?php

declare(strict_types=1);

namespace Velm\Modules\Workflow;

use Velm\Environment;

final class WorkflowDefinitions
{
    public static function seedPartnerDemo(Environment $env): void
    {
        if (! $env->registry->has('workflow.definition') || ! $env->registry->has('res.partner')) {
            return;
        }

        self::upsert($env, 'Partner onboarding', [
            'version' => 1,
            'model' => 'res.partner',
            'auto_start' => true,
            'states' => [
                ['key' => 'draft', 'label' => 'Draft', 'initial' => true],
                ['key' => 'review', 'label' => 'Under review'],
                ['key' => 'approved', 'label' => 'Approved', 'final' => true],
                ['key' => 'rejected', 'label' => 'Rejected', 'cancelled' => true],
            ],
            'transitions' => [
                [
                    'key' => 'submit',
                    'label' => 'Submit for approval',
                    'from' => ['draft'],
                    'to' => 'approved',
                    'kind' => 'approval',
                    'approval' => [
                        'strategy' => 'any',
                        'assignee_type' => 'group',
                        'deadline_hours' => 72,
                    ],
                    'form' => [
                        'title' => 'Submission',
                        'fields' => [
                            [
                                'name' => 'submission_note',
                                'label' => 'Why should this partner be approved?',
                                'type' => 'text',
                                'source' => 'stage',
                                'required' => true,
                            ],
                        ],
                    ],
                    'reject_to' => 'rejected',
                ],
                [
                    'key' => 'reset',
                    'label' => 'Reset to draft',
                    'from' => ['review', 'approved', 'rejected'],
                    'to' => 'draft',
                    'kind' => 'user',
                ],
            ],
        ], 'res.partner', 'Sample approval flow for business partners.');
    }

    public static function seedChangeManagement(Environment $env): void
    {
        if (! $env->registry->has('it.change')) {
            return;
        }

        $cabGroup = $env->model('res.groups')->search([['name', '=', 'Change Advisory Board']], limit: 1);
        $cabGroupId = $cabGroup->count() > 0 ? $cabGroup->ids()[0] : null;
        $managerGroup = $env->model('res.groups')->search([['name', '=', 'Change Manager']], limit: 1);
        $managerGroupId = $managerGroup->count() > 0 ? $managerGroup->ids()[0] : null;

        self::upsert($env, 'ICT Change lifecycle', [
            'version' => 1,
            'model' => 'it.change',
            'auto_start' => true,
            'states' => [
                ['key' => 'draft', 'label' => 'Draft RFC', 'initial' => true],
                ['key' => 'submitted', 'label' => 'Submitted'],
                ['key' => 'risk_review', 'label' => 'Risk & impact review'],
                ['key' => 'cab_review', 'label' => 'CAB review'],
                ['key' => 'approved', 'label' => 'Approved'],
                ['key' => 'scheduled', 'label' => 'Scheduled'],
                ['key' => 'implementing', 'label' => 'Implementing'],
                ['key' => 'validating', 'label' => 'Post-implementation review'],
                ['key' => 'closed', 'label' => 'Closed', 'final' => true],
                ['key' => 'rejected', 'label' => 'Rejected', 'cancelled' => true],
            ],
            'transitions' => [
                [
                    'key' => 'submit_rfc',
                    'label' => 'Submit RFC',
                    'from' => ['draft'],
                    'to' => 'submitted',
                    'kind' => 'user',
                    'form' => [
                        'title' => 'Submit change request',
                        'fields' => [
                            [
                                'name' => 'business_justification',
                                'label' => 'Business justification',
                                'type' => 'text',
                                'source' => 'record',
                                'required' => true,
                            ],
                        ],
                    ],
                ],
                [
                    'key' => 'start_risk_review',
                    'label' => 'Start risk review',
                    'from' => ['submitted'],
                    'to' => 'risk_review',
                    'kind' => 'user',
                ],
                [
                    'key' => 'complete_risk',
                    'label' => 'Complete risk assessment',
                    'from' => ['risk_review'],
                    'to' => 'cab_review',
                    'kind' => 'approval',
                    'approval' => [
                        'strategy' => 'any',
                        'assignee_type' => 'group',
                        'group_id' => $managerGroupId,
                        'deadline_hours' => 48,
                    ],
                    'form' => [
                        'title' => 'Risk assessment',
                        'fields' => [
                            [
                                'name' => 'risk_level',
                                'label' => 'Risk level',
                                'type' => 'selection',
                                'source' => 'record',
                                'required' => true,
                            ],
                            [
                                'name' => 'risk_notes',
                                'label' => 'Risk notes',
                                'type' => 'text',
                                'source' => 'stage',
                                'required' => true,
                            ],
                        ],
                    ],
                    'reject_to' => 'rejected',
                ],
                [
                    'key' => 'cab_decision',
                    'label' => 'CAB approve',
                    'from' => ['cab_review'],
                    'to' => 'approved',
                    'kind' => 'approval',
                    'approval' => [
                        'strategy' => 'all',
                        'assignee_type' => 'group',
                        'group_id' => $cabGroupId,
                        'deadline_hours' => 72,
                    ],
                    'form' => [
                        'title' => 'CAB decision',
                        'fields' => [
                            [
                                'name' => 'cab_notes',
                                'label' => 'CAB notes',
                                'type' => 'text',
                                'source' => 'stage',
                                'required' => false,
                            ],
                        ],
                    ],
                    'reject_to' => 'rejected',
                ],
                [
                    'key' => 'schedule',
                    'label' => 'Schedule implementation',
                    'from' => ['approved'],
                    'to' => 'scheduled',
                    'kind' => 'user',
                    'form' => [
                        'title' => 'Schedule window',
                        'fields' => [
                            [
                                'name' => 'planned_start',
                                'label' => 'Planned start',
                                'type' => 'datetime',
                                'source' => 'record',
                                'required' => true,
                            ],
                            [
                                'name' => 'planned_end',
                                'label' => 'Planned end',
                                'type' => 'datetime',
                                'source' => 'record',
                                'required' => true,
                            ],
                        ],
                    ],
                ],
                [
                    'key' => 'start_implementation',
                    'label' => 'Start implementation',
                    'from' => ['scheduled'],
                    'to' => 'implementing',
                    'kind' => 'user',
                ],
                [
                    'key' => 'complete_implementation',
                    'label' => 'Complete implementation',
                    'from' => ['implementing'],
                    'to' => 'validating',
                    'kind' => 'user',
                    'form' => [
                        'title' => 'Implementation notes',
                        'fields' => [
                            [
                                'name' => 'implementation_notes',
                                'label' => 'What was done',
                                'type' => 'text',
                                'source' => 'record',
                                'required' => true,
                            ],
                        ],
                    ],
                ],
                [
                    'key' => 'close_change',
                    'label' => 'Close change',
                    'from' => ['validating'],
                    'to' => 'closed',
                    'kind' => 'approval',
                    'approval' => [
                        'strategy' => 'any',
                        'assignee_type' => 'group',
                        'group_id' => $managerGroupId,
                        'deadline_hours' => 24,
                    ],
                    'form' => [
                        'title' => 'PIR sign-off',
                        'fields' => [
                            [
                                'name' => 'pir_outcome',
                                'label' => 'Post-implementation outcome',
                                'type' => 'text',
                                'source' => 'stage',
                                'required' => true,
                            ],
                        ],
                    ],
                    'reject_to' => 'implementing',
                ],
                [
                    'key' => 'reject',
                    'label' => 'Reject',
                    'from' => ['submitted', 'risk_review', 'cab_review', 'approved', 'scheduled'],
                    'to' => 'rejected',
                    'kind' => 'user',
                    'form' => [
                        'title' => 'Rejection reason',
                        'fields' => [
                            [
                                'name' => 'rejection_reason',
                                'label' => 'Reason',
                                'type' => 'text',
                                'source' => 'stage',
                                'required' => true,
                            ],
                        ],
                    ],
                ],
                [
                    'key' => 'reopen',
                    'label' => 'Reopen as draft',
                    'from' => ['rejected', 'closed'],
                    'to' => 'draft',
                    'kind' => 'user',
                ],
            ],
        ], 'it.change', 'Full ICT change management lifecycle (RFC → CAB → implementation → PIR).');
    }

    /**
     * @param  array<string, mixed>  $defn
     */
    private static function upsert(
        Environment $env,
        string $name,
        array $defn,
        string $model,
        string $description,
    ): void {
        $payload = [
            'name' => $name,
            'description' => $description,
            'model' => $model,
            'definition' => json_encode($defn, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT),
            'active' => true,
        ];

        $existing = $env->model('workflow.definition')->search([['name', '=', $name]], limit: 1);

        if ($existing->count() > 0) {
            $existing->write([
                'description' => $payload['description'],
                'definition' => $payload['definition'],
                'active' => $payload['active'],
            ]);

            return;
        }

        $env->model('workflow.definition')->create($payload);
    }
}
