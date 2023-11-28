<?php

namespace Orbat;

use Intervention\Image\ImageManager;

class FileUtilities
{
    public static function sanitizeUpload(array $file, int $x, ?int $y = null): ?string
    {
        if (!in_array($file['type'], ['image/png', 'image/jpeg'])) {
            throw new \Exception("unexpected image format");
        }

        $manager = new ImageManager();
        $img = $manager->make($file['tmp_name']);
        $img->fit($x, $y, function ($constraint) {
            $constraint->upsize();
        });
        return base64_encode($img->encode("png"));
    }
}