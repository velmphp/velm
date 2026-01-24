<?php

return [
    'persistence' => [
        // Bind the ModuleStateRepository interface to an Eloquent implementation
        'module_state_repository' => \Velm\Core\Persistence\Eloquent\EloquentModuleStateRepository::class,
    ],
];
