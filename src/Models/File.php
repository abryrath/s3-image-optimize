<?php

namespace App\Models;

use SplFileInfo;
use Doctrine\Common\Collections\ArrayCollection;

class File
{
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

    /** @var null|string $outputPath */
    public $outputPath;

    /** @var bool $skip */
    public $skip = false;

    /**
     * @param array $opts
     */
    public function __construct(SplFileInfo $splFileInfo)
    {
        $this->splFileInfo = $splFileInfo;
        $this->name = $splFileInfo->getFilename();
        $this->extension = $splFileInfo->getExtension();
        $this->size = $splFileInfo->getSize();
    }

    public function getUrl(): string
    {
        return $this->splFileInfo->getPathname();
    }
}
