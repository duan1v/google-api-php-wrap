<?php

namespace Dywily\Gaw\Entity;

/**
 * Class DAttachment
 * @package App\Http\Entity\Gmail
 */
class DAttachment
{
    const IMG_EXT = ['jpg', 'jpeg', 'png', 'gif', 'bmp'];
    public string $filePath;
    public string $originName;
    public string $gid;
    /**
     * @var string content id
     */
    public string $cid;
    public string $mime;
    public string $name;
    public string $path;

    public function getInfo()
    {

    }
}
