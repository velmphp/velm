<?php

declare(strict_types=1);

namespace Velm\Modules\Mail;

use Velm\Environment;
use Velm\Exception\AccessDeniedException;

/**
 * Odoo-style mail.thread: messages and followers keyed by (model, res_id).
 *
 * Models opt in by setting {@see Model::$mailThread} on the model class (registered at module load).
 */
final class MailThreadService
{
    /** @var array<string, true> */
    private static array $threadModels = [];

    public static function registerModel(string $model): void
    {
        if ($model !== '') {
            self::$threadModels[$model] = true;
        }
    }

    public static function hasThread(string $model): bool
    {
        return isset(self::$threadModels[$model]);
    }

    /**
     * @return array<string, true>
     */
    public static function registeredModels(): array
    {
        return self::$threadModels;
    }

    /**
     * @param  array<string, true>  $models
     */
    public static function seedRegisteredModelsForTesting(array $models): void
    {
        self::$threadModels = $models;
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function threadContext(Environment $env, string $resModel, int $resId): ?array
    {
        if ($resModel === '' || $resId <= 0 || ! self::hasThread($resModel)) {
            return null;
        }

        if (! $env->registry->has('mail.message')) {
            return null;
        }

        try {
            $env->checkAccess($resModel, 'read');
        } catch (AccessDeniedException) {
            return null;
        }

        $readonly = false;

        try {
            $env->checkAccess('mail.message', 'read');
        } catch (AccessDeniedException) {
            $readonly = true;
        }

        $canPost = ! $readonly;

        if ($canPost) {
            try {
                $env->checkAccess('mail.message', 'create');
            } catch (AccessDeniedException) {
                $canPost = false;
            }
        }

        $uid = $env->uid;
        $following = $uid !== null && self::isFollowing($env, $resModel, $resId, $uid);

        return [
            'has_thread' => true,
            'res_model' => $resModel,
            'res_id' => $resId,
            'readonly' => $readonly,
            'can_post' => $canPost,
            'following' => $following,
            'follower_count' => self::followerCount($env, $resModel, $resId),
            'messages' => $readonly ? [] : self::listMessages($env, $resModel, $resId),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function postMessage(
        Environment $env,
        string $resModel,
        int $resId,
        string $body,
        string $messageType = 'comment',
    ): array {
        if (! self::hasThread($resModel)) {
            throw new \InvalidArgumentException("Model {$resModel} does not support mail.thread.");
        }

        $body = trim($body);

        if ($body === '') {
            throw new \InvalidArgumentException('Message body is required.');
        }

        $env->checkAccess($resModel, 'read');
        $env->checkAccess('mail.message', 'create');

        $uid = $env->uid;

        if ($uid === null) {
            throw AccessDeniedException::forPermission('mail.message', 'create', null);
        }

        $record = $env->browse($resModel, [$resId]);

        if ($record->count() === 0) {
            throw new \InvalidArgumentException('Related record not found.');
        }

        $messageId = $env->model('mail.message')->create([
            'model' => $resModel,
            'res_id' => $resId,
            'body' => $body,
            'message_type' => $messageType === 'notification' ? 'notification' : 'comment',
            'author_id' => $uid,
        ])->ids()[0];

        $messages = self::listMessages($env, $resModel, $resId);

        foreach ($messages as $row) {
            if ((int) ($row['id'] ?? 0) === $messageId) {
                return $row;
            }
        }

        return [
            'id' => $messageId,
            'body' => $body,
            'body_html' => self::bodyToHtml($body),
            'message_type' => $messageType,
            'author_name' => self::authorName($env, $uid),
            'author_id' => $uid,
            'date_display' => '',
        ];
    }

    public static function setFollowing(
        Environment $env,
        string $resModel,
        int $resId,
        bool $follow,
    ): void {
        if (! self::hasThread($resModel)) {
            throw new \InvalidArgumentException("Model {$resModel} does not support mail.thread.");
        }

        $env->checkAccess($resModel, 'read');
        $env->checkAccess('mail.follower', $follow ? 'create' : 'unlink');

        $uid = $env->uid;

        if ($uid === null) {
            throw AccessDeniedException::forPermission('mail.follower', $follow ? 'create' : 'unlink', null);
        }

        $existing = $env->model('mail.follower')->search([
            ['model', '=', $resModel],
            ['res_id', '=', $resId],
            ['user_id', '=', $uid],
        ], limit: 1);

        if ($follow) {
            if ($existing->count() === 0) {
                $env->model('mail.follower')->create([
                    'model' => $resModel,
                    'res_id' => $resId,
                    'user_id' => $uid,
                ]);
            }

            return;
        }

        if ($existing->count() > 0) {
            $existing->unlink();
        }
    }

    private static function isFollowing(Environment $env, string $resModel, int $resId, int $uid): bool
    {
        if (! $env->registry->has('mail.follower')) {
            return false;
        }

        return $env->model('mail.follower')->search([
            ['model', '=', $resModel],
            ['res_id', '=', $resId],
            ['user_id', '=', $uid],
        ], limit: 1)->count() > 0;
    }

    private static function followerCount(Environment $env, string $resModel, int $resId): int
    {
        if (! $env->registry->has('mail.follower')) {
            return 0;
        }

        return $env->model('mail.follower')->search([
            ['model', '=', $resModel],
            ['res_id', '=', $resId],
        ])->count();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private static function listMessages(Environment $env, string $resModel, int $resId): array
    {
        $rows = $env->model('mail.message')->search(
            [
                ['model', '=', $resModel],
                ['res_id', '=', $resId],
            ],
            order: 'id desc',
        )->read(['id', 'body', 'message_type', 'author_id', 'created_at']);

        $out = [];

        foreach ($rows as $row) {
            $authorId = is_array($row['author_id'] ?? null)
                ? (int) ($row['author_id'][0] ?? 0)
                : (int) ($row['author_id'] ?? 0);
            $createdAt = (string) ($row['created_at'] ?? '');

            $out[] = [
                'id' => (int) $row['id'],
                'body' => (string) ($row['body'] ?? ''),
                'body_html' => self::bodyToHtml((string) ($row['body'] ?? '')),
                'message_type' => (string) ($row['message_type'] ?? 'comment'),
                'author_id' => $authorId,
                'author_name' => $authorId > 0 ? self::authorName($env, $authorId) : 'Unknown',
                'date_display' => $createdAt !== '' ? $createdAt : '',
            ];
        }

        return $out;
    }

    private static function authorName(Environment $env, int $uid): string
    {
        if (! $env->registry->has('res.users')) {
            return (string) $uid;
        }

        $rows = $env->browse('res.users', [$uid])->read(['name']);

        if ($rows === []) {
            return (string) $uid;
        }

        return (string) ($rows[0]['name'] ?? $uid);
    }

    private static function bodyToHtml(string $body): string
    {
        if ($body === '') {
            return '';
        }

        if (preg_match('/<[a-z][\s\S]*>/i', $body)) {
            return $body;
        }

        return nl2br(htmlspecialchars($body, ENT_QUOTES | ENT_HTML5, 'UTF-8'), false);
    }
}
