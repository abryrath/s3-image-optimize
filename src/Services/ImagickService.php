<?php

namespace App\Services;

use Imagick;
use App\Models\File;
use Doctrine\Common\Collections\ArrayCollection;
use abryrath\s3AssetOptimization\services\S3Service;
use Symfony\Component\Console\Output\OutputInterface;

class ImagickService
{
    const THRESHOLD_DEFAULT = 75*1024;

    const ORIGINAL_DIR = __DIR__ . '/../../original/';
    const OPTIMIZED_DIR = __DIR__ . '/../../optimized/';

    /** @var ArrayCollection $formats */
    public $formats;

    /** @var int $threshold don't optimize images under this threshold (default 75kB)*/
    public $threshold;
 
    /** @var null|OutputInterface $output */
    public $output;

    public function __construct()
    {
        // $this->output = $output;
        $this->formats = new ArrayCollection(['jpeg', 'jpg', 'png', 'gif']);
        if (!\is_dir(self::ORIGINAL_DIR)) {
            \mkdir(self::ORIGINAL_DIR);
        }
        if (!\is_dir(self::OPTIMIZED_DIR)) {
            \mkdir(self::OPTIMIZED_DIR);
        }
    }

    /**
     * Set the output buffer interface
     * @return void
     */
    public function setOutput(OutputInterface $output)
    {
        $this->output = $output;
    }

    /**
     * @param ArrayCollection $files
     * @return ArrayCollection
     */
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

        $valid = $optimized->filter(
            /**
             * @param File $file
             * @return bool
             */
            function ($file) {
                return !$file->skip;
            }
        );

        return $valid;
    }

    /**
     * @param File $file input file
     * @param int $compressionQuality
     * @param int $x new width
     * @param int $y new height
     * @return File
     */
    public function optimizeFile(File $file, $compressionQuality = 40, $x = null, $y = null)
    {
        try {
            $originalFile = \file_get_contents($file->splFileInfo->getPathname());
            $originalLocation = self::ORIGINAL_DIR . $file->name;
            $saveResult = \file_put_contents($originalLocation, $originalFile);
            if (!$saveResult) {
                throw new \Exception('could not save file');
            }

            $outputFile = self::OPTIMIZED_DIR . $file->name;
            $imagickObj = new Imagick($originalLocation);
            $imagick = clone $imagickObj;

            if (!$x) {
                if ($this->output) {
                    $this->output->writeln($file->splFileInfo->getPathname());
                }
                $x = $imagick->getImageWidth();
            }
            if (!$y) {
                $y = $imagick->getImageHeight();
            }
            
            switch ($file->extension) {
                case 'jpg':
                case 'jpeg':
                    $imagick->setImageCompression(Imagick::COMPRESSION_JPEG);
                    break;
                default:
                    //
            }
            $imagick->setImageCompressionQuality($compressionQuality);
            $imagick->stripImage();
            if ($x && $y) {
                $imagick->resizeImage($x, $y, Imagick::FILTER_BOX, 1.0);
            }
            $imagick->writeImage($outputFile);
            $size = \filesize($outputFile);

            if ($this->output) {
                $this->output->writeln("image size: $size [threshold: $this->threshold]");
            }
            
            /**
             * If the reduced size is still larger than the threshold, use recursion until it
             * is small enough
             */
            if ($size > $this->threshold) {
                return $this->optimizeFile(
                    $file,
                    $compressionQuality,
                    \intval(\ceil($x * 0.9)),
                    \intval(\ceil($y * 0.9))
                );
            }

            if ($this->output) {
                $this->output->writeln('=====');
                $file->outputPath = $outputFile;
            }

            $file->skip = false;
        } catch (\Exception $e) {
            //
            $file->skip = true;
            if ($this->output) {
                $this->output->writeln('Error on file: ' . $file->name);
                $this->output->writeln('=====');
            }
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
