<?php

namespace App\Models;

use SplFileInfo;
use Doctrine\Common\Collections\ArrayCollection;

class File
{
    // /** @var string $key */
    // private $key = '';

    /** @var string $name */
    public $name = '';

    /** @var string $type */
    public $type = '';

    /** @var string $extension */
    public $extension = '';

    /** @var int $size in bytes */
    public $size = 0;

    /** @var SplFileInfo $splFileInfo */
    public $splFileInfo;


    /**
     * @param array $opts
     */
    public function __construct(SplFileInfo $splFileInfo)
    {
        $this->splFileInfo = $splFileInfo;
        // $this->url = $splFileInfo->getPathname();
        $this->name = $splFileInfo->getFilename();
        $this->type = $splFileInfo->getType();
        $this->extension = $splFileInfo->getExtension();
        $this->size = $splFileInfo->getSize();
        
        $this->originalFilePath = '';
        $this->optimizedFilePath = '';
    }

    public function optimize(int $sizeThreshold): self
    {
        // $file = file_get_contents()
        return $this;
    }

    public function getUrl(): string
    {
        return $this->splFileInfo->getPathname();
    }
}
