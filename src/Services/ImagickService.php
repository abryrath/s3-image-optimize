<?php

namespace App\Services;

use Imagick;
use App\Models\File;
use Doctrine\Common\Collections\ArrayCollection;
use abryrath\s3AssetOptimization\services\S3Service;

class ImagickService
{
    const THRESHOLD_DEFAULT = 75*1024;

    const ORIGINAL_DIR = __DIR__ . '/../../original/';
    const OPTIMIZED_DIR = __DIR__ . '/../../optimized/';

    /** @var ArrayCollection $formats */
    public $formats;

    /** @var int $threshold don't optimize images under this threshold (default 75kB)*/
    public $threshold;
 
    public function __construct()
    {
        $this->formats = new ArrayCollection(['jpeg', 'jpg', 'png', 'gif']);
    }

    public function optimize(ArrayCollection $files)
    {
        if (!$this->threshold) {
            $this->threshold = self::THRESHOLD_DEFAULT;
        }

        $matchingFormats = $this->filterFormats($files);
        $matchingSize = $this->filterFileSize($matchingFormats);
        
        if ($matchingSize->count() < 1) {
            die('No files met the criteria');
        }

        $optimized = $matchingSize->map(
            /**
             * @param File $file
             * @return File
             */
            function ($file) {
                return $this->optimizeFile($file);
            }
        );

        return $matchingSize->count();
    }

    public function optimizeFile(File $file)
    {
        $originalFile = \file_get_contents($file->splFileInfo->getPathname());
        $originalLocation = \file_put_contents(self::ORIGINAL_DIR . $file->name, $originalFile);
        $outputFile = self::OPTIMIZED_DIR . $file->name;
        switch ($file->extension) {
            case 'jpg':
            case 'jpeg':
                $imagick = new Imagick($originalLocation);
                $imagick->setImageCompression(Imagick::COMPRESSION_JPEG);
                $imagick->setImageCompressionQuality(40);
                $imagick->stripImage();
                $imagick->writeImage($outputFile);
        }
        
        return $file;
    }
    

     /**
     * @param ArrayCollection<array-key,File> $files
     * @return ArrayCollection<array-key,File>
     */
    private function filterFormats(ArrayCollection $files): ArrayCollection
    {
        return $files->filter(
            /**
             * @param File $file
             * @return bool
             */
            function ($file) {
                return $this->formats->contains($file->extension);
            }
        );
    }

    private function filterFileSize(ArrayCollection $files): ArrayCollection
    {
        return $files->filter(
            /**
             * @param File $file
             * @return bool
             */
            function ($file) {
                return $file->size >= $this->threshold;
            }
        );
    }
}
