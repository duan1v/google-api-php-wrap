<?php


namespace Dywily\Gaw;


use Dywily\Gaw\Entity\DAttachment;

class FileManager
{

    public static string $path     = '';
    public static int    $fileSize = 0;

    public static function getFile($path): string
    {
        $path = __DIR__ . '/../examples/gmail/' . $path;
        $file = file_get_contents($path);
        static::$path = $path;
        static::$fileSize = strlen($file);

        return $file;
    }

    public static function getFileSize($path): int
    {
        $path = __DIR__ . '/../examples/gmail/' . $path;
        if (static::$fileSize && static::$path == $path) {
            return static::$fileSize;
        }
        static::$path = $path;
        static::$fileSize = strlen(file_get_contents($path));
        return static::$fileSize;
    }

    public static function getAttachmentWithId($attachmentIds): array
    {
        $path = '../upload/';
        $as = [];
        foreach ($attachmentIds as $attachmentId) {
            $at = new DAttachment();
            $at->originName = $attachmentId;
            $at->path = $path . $attachmentId;
            $as[] = $at;
        }

        return $as;
    }

    public static function getMsgPath(DAttachment $a): string
    {
        return 'http://localhost:8068/src/cache' . $a->filePath.$a->name;
    }

}
