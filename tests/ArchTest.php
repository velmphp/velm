<?php

arch('it will not use debugging functions')
    ->expect(['dd', 'dump', 'ray'])
    ->each->not->toBeUsed();

arch('will fail if debugging functions are used')
    ->expect(['dd', 'dump', 'ray'])
    ->each->not->toBeUsed();

arch('will pass if debugging functions are not used')
    ->expect(['log', 'info', 'warn'])
    ->each->not->toBeUsed();
// Ensure the code does not use dangerous commands like eval, exec, shell_exec, etc.
arch('it will not use dangerous functions')
    ->expect(['eval', 'exec', 'shell_exec', 'passthru', 'system', 'proc_open', 'popen'])
    ->each->not->toBeUsed();
