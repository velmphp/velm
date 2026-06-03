<?php

declare(strict_types=1);

namespace Velm\Modules\FileManager;

use Illuminate\Http\UploadedFile;
use Velm\Environment;
use Velm\Exception\AccessDeniedException;
use Velm\Modules\Base\Models\Attachment;
use Velm\Storage\AttachmentStorage;
use Velm\Support\AttachmentRow;

final class FileManagerService
{
    public function __construct(
        private readonly Environment $env,
    ) {}

    public function ensureInstalled(): void
    {
        if (! $this->env->registry->has('ir.attachment') || ! $this->env->registry->has('res.attachment.folder')) {
            abort(404, 'file_manager module is not installed');
        }
    }

    /**
     * @return array{folders: list<array<string, mixed>>, unfiled_count: int}
     */
    public function folderTree(): array
    {
        $this->env->checkAccess('res.attachment.folder', 'read');

        $folders = $this->env->model('res.attachment.folder')
            ->search([], order: '"sequence" ASC, "name" ASC');

        $rows = $folders->read(['id', 'name', 'parent_id', 'sequence', 'color']);
        $counts = $this->attachmentCountsByFolder();
        $childCounts = [];

        foreach ($rows as $row) {
            $parentId = $row['parent_id'] ?? null;

            if ($parentId !== null && $parentId !== '') {
                $parentId = (int) $parentId;
                $childCounts[$parentId] = ($childCounts[$parentId] ?? 0) + 1;
            }
        }

        $payload = [];

        foreach ($rows as $row) {
            $id = (int) $row['id'];
            $parentId = $row['parent_id'] ?? null;
            $payload[] = [
                'id' => $id,
                'name' => (string) ($row['name'] ?? ''),
                'parent_id' => $parentId !== null && $parentId !== '' ? (int) $parentId : null,
                'sequence' => (int) ($row['sequence'] ?? 10),
                'color' => (string) ($row['color'] ?? ''),
                'child_count' => $childCounts[$id] ?? 0,
                'file_count' => $counts[$id] ?? 0,
            ];
        }

        return [
            'folders' => $payload,
            'unfiled_count' => $counts[0] ?? 0,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function browse(?int $folderId, string $accept = '', string $query = '', int $limit = 120): array
    {
        $this->env->checkAccess('ir.attachment', 'read');

        $query = trim($query);

        if ($query !== '') {
            return [
                'folder_id' => $folderId,
                'breadcrumb' => [],
                'folders' => [],
                'rows' => $this->pickerRows($accept, $query, $limit),
                'searching' => true,
            ];
        }

        $target = $folderId;
        $folders = [];

        if ($this->env->registry->has('res.attachment.folder')) {
            $subs = $this->env->model('res.attachment.folder')->search(
                [['parent_id', '=', $target]],
                order: '"sequence" ASC, "name" ASC',
            )->read(['id', 'name']);

            $allFolders = $this->env->model('res.attachment.folder')->search()->read(['id', 'parent_id']);
            $childCounts = [];

            foreach ($allFolders as $f) {
                $parent = $f['parent_id'] ?? null;

                if ($parent !== null && $parent !== '') {
                    $pid = (int) $parent;
                    $childCounts[$pid] = ($childCounts[$pid] ?? 0) + 1;
                }
            }

            $counts = $this->attachmentCountsByFolder();

            foreach ($subs as $f) {
                $id = (int) $f['id'];
                $folders[] = [
                    'id' => $id,
                    'name' => (string) ($f['name'] ?? ''),
                    'child_count' => $childCounts[$id] ?? 0,
                    'file_count' => $counts[$id] ?? 0,
                ];
            }
        }

        $domain = [['folder_id', '=', $target]];
        $domain = array_merge($domain, $this->libraryCompanyDomain());
        $domain = array_merge($domain, $this->acceptMimeDomain($accept));

        $attachments = $this->env->model('ir.attachment')->search(
            $domain,
            limit: $limit,
            order: '"id" DESC',
        )->read(['id', 'name', 'mimetype', 'file_size', 'type', 'url', 'public', 'folder_id']);

        return [
            'folder_id' => $target,
            'breadcrumb' => $this->folderBreadcrumb($target),
            'folders' => $folders,
            'rows' => array_map($this->attachmentToRow(...), $attachments),
            'searching' => false,
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function pickerRows(string $accept, string $query, int $limit = 60): array
    {
        $domain = $this->libraryCompanyDomain();

        if ($query !== '') {
            $domain[] = ['name', 'ilike', '%'.$query.'%'];
        }

        $domain = array_merge($domain, $this->acceptMimeDomain($accept));

        $rows = $this->env->model('ir.attachment')->search(
            $domain,
            limit: $limit,
            order: '"id" DESC',
        )->read(['id', 'name', 'mimetype', 'file_size', 'type', 'url', 'public', 'folder_id']);

        return array_map($this->attachmentToRow(...), $rows);
    }

    /**
     * @return array<string, mixed>
     */
    public function storeUpload(UploadedFile $file, bool $public = false, ?int $folderId = null): array
    {
        $this->env->checkAccess('ir.attachment', 'create');

        $content = $file->getContent();

        if ($content === '') {
            abort(400, 'Empty file');
        }

        $originalName = $file->getClientOriginalName() ?: 'file';
        $backend = AttachmentStorage::backend();
        $storageKey = $backend->save($originalName, $content);
        $mimetype = $file->getMimeType()
            ?: (is_string($file->getRealPath()) ? mime_content_type($file->getRealPath()) : null)
            ?: 'application/octet-stream';

        $values = [
            'name' => $originalName,
            'datas_fname' => $originalName,
            'mimetype' => $mimetype,
            'file_size' => strlen($content),
            'type' => 'binary',
            'storage_key' => $storageKey,
            'datas' => $storageKey === '' ? base64_encode($content) : null,
            'public' => $public,
        ];

        if ($this->env->companyId() !== null && $this->env->registry->hasFieldSet('ir.attachment')) {
            $fields = $this->env->registry->fieldSet('ir.attachment');

            if (isset($fields['company_id'])) {
                $values['company_id'] = $this->env->companyId();
            }
        }

        if ($folderId !== null && $folderId > 0) {
            $this->folderOrFail($folderId);
            $values['folder_id'] = $folderId;
        }

        $att = $this->env->model('ir.attachment')->create($values);
        $row = $att->read(['id', 'name', 'mimetype', 'file_size', 'type', 'url', 'public', 'folder_id'])[0] ?? [];

        return $this->attachmentToRow($row);
    }

    /**
     * @return array<string, mixed>
     */
    /**
     * @return array<string, mixed>
     */
    public function propertiesViewData(int $attId): array
    {
        $context = $this->propertiesContext($attId);
        $att = $context['att'] ?? [];
        $bytes = (int) ($att['file_size'] ?? 0);

        return [
            'att' => $att,
            'mimetype' => $context['mimetype'] ?? '',
            'isImage' => (bool) ($context['is_image'] ?? false),
            'extension' => $context['extension'] ?? '',
            'dimensions' => $context['dimensions'] ?? null,
            'ownerUrl' => $context['owner_url'] ?? '',
            'folderChain' => $context['folder_chain'] ?? [],
            'fileSizeLabel' => self::humanSize($bytes),
            'panelOnly' => false,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function propertiesContext(int $attId): array
    {
        $this->env->checkAccess('ir.attachment', 'read');

        $att = $this->env->browse('ir.attachment', [$attId]);

        if ($att->count() === 0) {
            abort(404, "ir.attachment({$attId}) not found");
        }

        $row = $att->read([
            'id', 'name', 'datas_fname', 'mimetype', 'file_size', 'type', 'url', 'public',
            'res_model', 'res_id', 'folder_id', 'storage_key', 'created_at', 'updated_at',
        ])[0] ?? [];

        $mimetype = strtolower(explode(';', (string) ($row['mimetype'] ?? ''), 2)[0]);
        $isImage = str_starts_with($mimetype, 'image/');
        $source = explode('.', (string) ($row['datas_fname'] ?? $row['name'] ?? ''), 2);
        $extension = count($source) === 2 && $source[1] !== '' ? strtolower($source[1]) : '';

        $dimensions = null;

        if ($isImage) {
            $payload = Attachment::fetchContentFromRow($row);

            if ($payload !== '') {
                $size = @getimagesizefromstring($payload);

                if (is_array($size)) {
                    $dimensions = ['width' => $size[0], 'height' => $size[1]];
                }
            }
        }

        $ownerUrl = '';

        if (! empty($row['res_model']) && ! empty($row['res_id']) && $this->env->registry->has((string) $row['res_model'])) {
            $panel = (string) config('velm.panel_path', 'velm');
            $ownerUrl = '/'.$panel.'/view/'.rawurlencode((string) $row['res_model']).'/record/'.(int) $row['res_id'];
        }

        return [
            'att' => $row,
            'mimetype' => $mimetype,
            'is_image' => $isImage,
            'extension' => $extension,
            'dimensions' => $dimensions,
            'owner_url' => $ownerUrl,
            'folder_chain' => $this->folderChainFromRow($row),
        ];
    }

    /**
     * @param  list<int>  $ids
     */
    public function moveAttachments(array $ids, ?int $folderId): int
    {
        $this->env->checkAccess('ir.attachment', 'write');

        if ($folderId !== null && $folderId > 0) {
            $this->folderOrFail($folderId);
        } else {
            $folderId = null;
        }

        $updated = 0;

        foreach ($ids as $id) {
            $att = $this->env->browse('ir.attachment', [$id]);

            if ($att->count() > 0) {
                $att->write(['folder_id' => $folderId]);
                $updated++;
            }
        }

        return $updated;
    }

    /**
     * Duplicate attachments into a folder (or Unfiled). Each copy is an
     * independent row with its own stored bytes.
     *
     * @param  list<int>  $ids
     */
    public function copyAttachments(array $ids, ?int $folderId): int
    {
        $this->env->checkAccess('ir.attachment', 'create');

        if ($folderId !== null && $folderId > 0) {
            $this->folderOrFail($folderId);
        } else {
            $folderId = null;
        }

        $backend = AttachmentStorage::backend();
        $hasCompany = isset($this->env->registry->fieldSet('ir.attachment')['company_id']);
        $copied = 0;

        foreach ($ids as $id) {
            $att = $this->env->browse('ir.attachment', [$id]);

            if ($att->count() === 0) {
                continue;
            }

            $row = $att->read([
                'name', 'datas_fname', 'mimetype', 'file_size', 'type', 'url', 'public',
            ])[0] ?? [];

            $name = (string) ($row['name'] ?? 'file');
            $values = [
                'name' => $name,
                'datas_fname' => (string) ($row['datas_fname'] ?? $name),
                'mimetype' => (string) ($row['mimetype'] ?? 'application/octet-stream'),
                'file_size' => (int) ($row['file_size'] ?? 0),
                'res_model' => null,
                'res_id' => null,
                'type' => (string) ($row['type'] ?? 'binary'),
                'public' => (bool) ($row['public'] ?? false),
                'folder_id' => $folderId,
            ];

            if ($hasCompany && $this->env->companyId() !== null) {
                $values['company_id'] = $this->env->companyId();
            }

            if (($values['type'] ?? '') === 'url') {
                $values['url'] = (string) ($row['url'] ?? '');
            } else {
                $content = Attachment::fetchContentFromRow($row);
                $storageKey = $content !== '' ? $backend->save($name, $content) : '';
                $values['storage_key'] = $storageKey;
                $values['datas'] = $storageKey === '' && $content !== '' ? base64_encode($content) : null;
            }

            $this->env->model('ir.attachment')->create($values);
            $copied++;
        }

        return $copied;
    }

    /**
     * @param  list<int>  $ids
     */
    public function deleteAttachments(array $ids): void
    {
        if ($ids === []) {
            abort(400, 'ids is required');
        }

        $att = $this->env->browse('ir.attachment', $ids);
        $att->unlink();
    }

    /**
     * @param  list<int>  $ids
     */
    public function setPublic(array $ids, bool $public): int
    {
        $this->env->checkAccess('ir.attachment', 'write');

        if ($ids === []) {
            abort(400, 'ids is required');
        }

        $att = $this->env->browse('ir.attachment', $ids);
        $att->write(['public' => $public]);

        return $att->count();
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{id: int, name: string, parent_id: int|null}
     */
    public function createFolder(array $payload): array
    {
        $this->env->checkAccess('res.attachment.folder', 'create');

        $name = trim((string) ($payload['name'] ?? ''));

        if ($name === '') {
            abort(400, 'name is required');
        }

        $values = ['name' => $name];
        $parentId = $payload['parent_id'] ?? null;

        if ($parentId !== null && $parentId !== '' && (int) $parentId > 0) {
            $this->folderOrFail((int) $parentId);
            $values['parent_id'] = (int) $parentId;
        }

        $folder = $this->env->model('res.attachment.folder')->create($values);

        return [
            'id' => $folder->ids()[0],
            'name' => $name,
            'parent_id' => $values['parent_id'] ?? null,
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function updateFolder(int $folderId, array $payload): array
    {
        $this->env->checkAccess('res.attachment.folder', 'write');

        $rec = $this->folderOrFail($folderId);
        $values = [];

        if (array_key_exists('name', $payload)) {
            $name = trim((string) ($payload['name']));

            if ($name === '') {
                abort(400, 'name cannot be empty');
            }

            $values['name'] = $name;
        }

        if (array_key_exists('parent_id', $payload)) {
            $newParent = $payload['parent_id'];

            if ($newParent === null || $newParent === '' || (int) $newParent === 0) {
                $values['parent_id'] = null;
            } else {
                $newParentId = (int) $newParent;
                $this->folderOrFail($newParentId);

                if ($this->folderChainIncludes($folderId, $newParentId)) {
                    abort(400, 'parent_id would create a folder cycle');
                }

                $values['parent_id'] = $newParentId;
            }
        }

        if ($values !== []) {
            $rec->write($values);
        }

        $row = $rec->read(['id', 'name', 'parent_id'])[0] ?? [];

        return [
            'id' => (int) ($row['id'] ?? $folderId),
            'name' => (string) ($row['name'] ?? ''),
            'parent_id' => isset($row['parent_id']) && $row['parent_id'] !== null && $row['parent_id'] !== ''
                ? (int) $row['parent_id']
                : null,
        ];
    }

    public function deleteFolder(int $folderId): void
    {
        $this->env->checkAccess('res.attachment.folder', 'unlink');

        $rec = $this->folderOrFail($folderId);

        if ($this->env->model('res.attachment.folder')->search([['parent_id', '=', $folderId]], limit: 1)->count() > 0) {
            abort(409, 'Folder has subfolders — empty it first');
        }

        if ($this->env->model('ir.attachment')->search([['folder_id', '=', $folderId]], limit: 1)->count() > 0) {
            abort(409, 'Folder has files — empty it first');
        }

        $rec->unlink();
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    private function attachmentToRow(array $row): array
    {
        $base = AttachmentRow::toArray($row);
        $base['icon'] = FileIconKey::resolve(
            (string) ($row['mimetype'] ?? ''),
            (string) ($row['name'] ?? ''),
        );
        $base['size'] = (int) ($row['file_size'] ?? 0);

        return $base;
    }

    /**
     * @return array<int, int>
     */
    private function attachmentCountsByFolder(): array
    {
        $counts = [];
        $domain = $this->libraryCompanyDomain();
        $rows = $this->env->model('ir.attachment')->search($domain)->read(['folder_id']);

        foreach ($rows as $row) {
            $folderId = $row['folder_id'] ?? null;
            $key = $folderId !== null && $folderId !== '' ? (int) $folderId : 0;
            $counts[$key] = ($counts[$key] ?? 0) + 1;
        }

        return $counts;
    }

    /**
     * @return list<list<mixed>>
     */
    private function libraryCompanyDomain(): array
    {
        if ($this->env->companyId() === null) {
            return [];
        }

        $fields = $this->env->registry->fieldSet('ir.attachment');

        if (! isset($fields['company_id'])) {
            return [];
        }

        return [['company_id', '=', $this->env->companyId()]];
    }

    /**
     * @return list<list<mixed>>
     */
    private function acceptMimeDomain(string $accept): array
    {
        $accept = trim($accept);

        if ($accept === '') {
            return [];
        }

        $clauses = [];

        foreach (array_filter(array_map(trim(...), explode(',', $accept))) as $token) {
            if (str_ends_with($token, '/*')) {
                $clauses[] = ['mimetype', 'ilike', substr($token, 0, -1).'%'];
            } elseif (str_contains($token, '/')) {
                $clauses[] = ['mimetype', '=', $token];
            }
        }

        if ($clauses === []) {
            return [];
        }

        if (count($clauses) === 1) {
            return $clauses;
        }

        return array_merge(array_fill(0, count($clauses) - 1, ['|']), $clauses);
    }

    /**
     * @return list<array{id: int, name: string}>
     */
    private function folderBreadcrumb(?int $folderId): array
    {
        if ($folderId === null || $folderId <= 0 || ! $this->env->registry->has('res.attachment.folder')) {
            return [];
        }

        $chain = [];
        $cursorId = $folderId;

        for ($i = 0; $i < 32; $i++) {
            $rows = $this->env->browse('res.attachment.folder', [$cursorId])->read(['id', 'name', 'parent_id']);

            if ($rows === []) {
                break;
            }

            $row = $rows[0];
            $chain[] = ['id' => (int) $row['id'], 'name' => (string) ($row['name'] ?? '')];
            $parent = $row['parent_id'] ?? null;

            if ($parent === null || $parent === '') {
                break;
            }

            $cursorId = (int) $parent;
        }

        return array_reverse($chain);
    }

    /**
     * @param  array<string, mixed>  $row
     * @return list<array{id: int, name: string}>
     */
    private function folderChainFromRow(array $row): array
    {
        $folderId = $row['folder_id'] ?? null;

        if ($folderId === null || $folderId === '') {
            return [];
        }

        return $this->folderBreadcrumb((int) $folderId);
    }

    private function folderOrFail(int $folderId): \Velm\Recordset\Recordset
    {
        $rec = $this->env->browse('res.attachment.folder', [$folderId]);

        if ($rec->count() === 0) {
            abort(404, "res.attachment.folder({$folderId}) not found");
        }

        return $rec;
    }

    private static function humanSize(int $bytes): string
    {
        if ($bytes <= 0) {
            return '0 B';
        }

        if ($bytes < 1024) {
            return $bytes.' B';
        }

        if ($bytes < 1024 * 1024) {
            return number_format($bytes / 1024, 1).' KB';
        }

        return number_format($bytes / 1024 / 1024, 1).' MB';
    }

    private function folderChainIncludes(int $ancestorId, int $candidateId): bool
    {
        if ($ancestorId === $candidateId) {
            return true;
        }

        $cursorId = $candidateId;

        for ($i = 0; $i < 32; $i++) {
            $rows = $this->env->browse('res.attachment.folder', [$cursorId])->read(['parent_id']);

            if ($rows === []) {
                return false;
            }

            $parent = $rows[0]['parent_id'] ?? null;

            if ($parent === null || $parent === '') {
                return false;
            }

            $parentId = (int) $parent;

            if ($parentId === $ancestorId) {
                return true;
            }

            $cursorId = $parentId;
        }

        return false;
    }
}
