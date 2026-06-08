<?php

declare(strict_types=1);

use Velm\Views\Authoring\Menus;
use Velm\Views\Data\ViewsData;

$m = new Menus('system_audit');

return ViewsData::make()
    ->menus(
        $m->group('security.audit', 'Audit')
            ->parent('admin', 'security')
            ->sequence(30)
            ->children(
                $m->item('security.audit.log', 'Audit log')
                    ->view('audit_log.list')
                    ->icon('shield-check')
                    ->sequence(10),
                $m->item('security.audit.logins', 'Login history')
                    ->view('login_log.list')
                    ->icon('arrow-right-on-rectangle')
                    ->sequence(20),
                $m->item('security.audit.lifecycle', 'User lifecycle')
                    ->view('user_lifecycle.list')
                    ->icon('user-circle')
                    ->sequence(30),
            ),
    );
