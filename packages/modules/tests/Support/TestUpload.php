<?php

declare(strict_types=1);

namespace Velm\Modules\Tests\Support;

use Illuminate\Http\UploadedFile;

final class TestUpload
{
    public static function file(string $name, string $content, string $mime = 'text/plain'): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'velm_upload_');
        file_put_contents($path, $content);

        return new UploadedFile($path, $name, $mime, null, true);
    }
}
