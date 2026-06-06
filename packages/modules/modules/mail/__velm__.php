<?php

declare(strict_types=1);

use Velm\Modules\Mail\MailInstallHooks;
use Velm\Modules\Mail\MailSyncHooks;
use Velm\Modules\Mail\Models\Follower;
use Velm\Modules\Mail\Models\MailThread;
use Velm\Modules\Mail\Models\Message;
use Velm\Modules\Manifest;

return Manifest::make('mail')
    ->version(0, 1, 0)
    ->depends('base', 'admin')
    ->models(Message::class, Follower::class, MailThread::class)
    ->installHook(MailInstallHooks::class)
    ->syncHook(MailSyncHooks::class)
    ->summary('Discuss on records — messages, followers, and the chatter sidebar.')
    ->category('Productivity')
    ->icon('heroicon-o-chat-bubble-left-right');
