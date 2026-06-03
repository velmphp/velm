<?php

declare(strict_types=1);

namespace Velm\Support;

/**
 * Serialize {@see ir.attachment} rows for APIs and the file library UI.
 */
final class AttachmentRow
{
    private const string IMAGE_PREFIX = 'image/';

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    public static function toArray(array $row, bool $includeThumbnail = true): array
    {
        $id = (int) ($row['id'] ?? 0);
        $mimetype = strtolower((string) ($row['mimetype'] ?? ''));
        $type = (string) ($row['type'] ?? 'binary');
        $url = (string) ($row['url'] ?? '');

        $thumbnail = '';

        if ($includeThumbnail) {
            if ($type === 'url' && $url !== '' && str_starts_with($mimetype, self::IMAGE_PREFIX)) {
                $thumbnail = $url;
            } elseif (str_starts_with($mimetype, self::IMAGE_PREFIX) && $id > 0) {
                $thumbnail = '/api/attachment/'.$id.'/download';
            }
        }

        return [
            'id' => $id,
            'name' => (string) ($row['name'] ?? ''),
            'mimetype' => (string) ($row['mimetype'] ?? ''),
            'size' => (int) ($row['file_size'] ?? 0),
            'type' => $type,
            'public' => (bool) ($row['public'] ?? false),
            'folder_id' => isset($row['folder_id']) ? (int) $row['folder_id'] : null,
            'thumbnail_url' => $thumbnail,
            'download_url' => $id > 0 ? '/api/attachment/'.$id.'/download' : '',
        ];
    }
}
