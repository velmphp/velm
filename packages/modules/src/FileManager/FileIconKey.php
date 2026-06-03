<?php

declare(strict_types=1);

namespace Velm\Modules\FileManager;

/**
 * Coarse file-type keys for the library / picker icon map (PyVelm {@code file_icons}).
 */
final class FileIconKey
{
    /** @var array<string, string> */
    private const array EXACT = [
        'application/pdf' => 'pdf',
        'application/json' => 'json',
        'application/ld+json' => 'json',
        'application/msword' => 'doc',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'doc',
        'application/vnd.oasis.opendocument.text' => 'doc',
        'application/vnd.ms-excel' => 'xls',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'xls',
        'application/vnd.oasis.opendocument.spreadsheet' => 'xls',
        'text/csv' => 'xls',
        'application/vnd.ms-powerpoint' => 'ppt',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation' => 'ppt',
        'application/vnd.oasis.opendocument.presentation' => 'ppt',
        'application/zip' => 'zip',
        'application/x-zip-compressed' => 'zip',
        'application/x-tar' => 'zip',
        'application/x-7z-compressed' => 'zip',
        'application/x-rar-compressed' => 'zip',
        'application/vnd.rar' => 'zip',
        'application/gzip' => 'zip',
        'application/x-bzip2' => 'zip',
    ];

    /** @var array<string, string> */
    private const array EXT = [
        'pdf' => 'pdf',
        'json' => 'json',
        'doc' => 'doc', 'docx' => 'doc', 'odt' => 'doc', 'rtf' => 'doc',
        'xls' => 'xls', 'xlsx' => 'xls', 'ods' => 'xls', 'csv' => 'xls',
        'ppt' => 'ppt', 'pptx' => 'ppt', 'odp' => 'ppt',
        'zip' => 'zip', 'tar' => 'zip', 'gz' => 'zip', 'tgz' => 'zip',
        '7z' => 'zip', 'rar' => 'zip', 'bz2' => 'zip',
        'txt' => 'text', 'md' => 'text', 'log' => 'text',
        'mp3' => 'audio', 'wav' => 'audio', 'ogg' => 'audio', 'flac' => 'audio', 'm4a' => 'audio',
        'mp4' => 'video', 'mov' => 'video', 'webm' => 'video', 'mkv' => 'video', 'avi' => 'video',
    ];

    public static function resolve(?string $mimetype, ?string $filename = null): string
    {
        $mime = strtolower(explode(';', (string) $mimetype, 2)[0]);

        if (str_starts_with($mime, 'image/')) {
            return 'image';
        }

        if (isset(self::EXACT[$mime])) {
            return self::EXACT[$mime];
        }

        if (str_starts_with($mime, 'text/')) {
            return 'text';
        }

        if (str_starts_with($mime, 'audio/')) {
            return 'audio';
        }

        if (str_starts_with($mime, 'video/')) {
            return 'video';
        }

        $name = strtolower((string) $filename);

        if (str_contains($name, '.')) {
            $ext = substr($name, strrpos($name, '.') + 1);

            if (isset(self::EXT[$ext])) {
                return self::EXT[$ext];
            }
        }

        return 'file';
    }
}
