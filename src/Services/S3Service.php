<?php

namespace App\Services;

use SplFileInfo;
use App\Models\File;
use Aws\S3\S3Client;
use League\Csv\Writer;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Console\Input\InputInterface;

class S3Service
{
    /** @var null|S3Client $client */
    protected $client;

    /** @var string $bucket */
    public $bucket = '';

    /** @var string $subFolder */
    public $subFolder = '';

    /** @var string $outputFile */
    public $outputFile;

    /**
     * Convert the input options into a useable format
     * @return array S3Client configuration options array
     */
    public function parseInput(InputInterface $input): array
    {
        /** @var array $opts */
        $opts = [
            'version' => 'latest',
        ];

        /** @var string $region*/
        $region = $input->getOption('region');
        if ($region) {
            $opts['region'] = $region;
        }

        /** @var string $bucket*/
        $bucket = $input->getOption('bucket');
        if ($bucket) {
            $this->bucket = $bucket;
        }

        /** @var string $subfolder */
        $subFolder = $input->getOption('subfolder');
        if ($subFolder && \is_string($subFolder)) {
            $this->subFolder = $subFolder;
        }

        /** @var string $accessKey */
        $accessKey = $input->getOption('accessKey');
        /** @var string $secretAccessKey */
        $secretAccessKey = $input->getOption('secretAccessKey');
        if ($accessKey && $secretAccessKey) {
            $opts['credentials'] = [
                'key' => $accessKey,
                'secret' => $secretAccessKey,
            ];
        }

        /** @var string $outputFile */
        $outputFile = $input->getOption('outputFile');
        if ($outputFile) {
            $this->outputFile = $outputFile;
        }

        $this->client = new S3Client($opts);
        $this->client->registerStreamWrapper();

        return $opts;
    }

    /**
     * @return false|ArrayCollection<array-key,File>
     */
    public function getBucketContents()
    {
        if (!$this->bucket) {
            return false;
        }
        $dir = "s3://{$this->bucket}/";
        if ($this->subFolder) {
            $dir .= $this->subFolder . "/";
        }

        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
        $files = new ArrayCollection([]);

        /** @var SplFileInfo $file */
        foreach ($iterator as $file) {
            $file = new File($file);

            $files->add($file);
        }

        return $files;
    }

    /**
     * Replace the files on S3 with the optimized versions
     * @return void
     */
    public function replaceFiles(ArrayCollection $files)
    {
        foreach ($files as $file) {
            $contents = \file_get_contents($file->outputPath);
            \file_put_contents($file->splFileInfo->getPathname(), $contents);
        }
    }

    public function writeImageData(ImagickService $imagick)
    {
        $files = $this->getBucketContents();
        $images = $imagick->filterFormats($files);
        $header = ['filename', 'path', 'size'];
        $records = [];
        foreach ($images as $image) {
            $records[] = [
                $image->name,
                $image->splFileInfo->getPathname(),
                self::formatBytes($image->size, 4),
            ];
        }

        $writer = Writer::createFromString('');
        $writer->insertOne($header);
        $writer->insertAll($records);
        $body = $writer->getContent();

        return file_put_contents($this->outputFile, $body);


        var_dump($records);
    }

    /**
     * @param int $size
     * @param int $precision
     * @return string
     */
    public static function formatBytes($size, $precision = 2)
    {
        $base = log($size, 1024);
        $suffixes = array('', 'K', 'M', 'G', 'T');   

        return round(pow(1024, $base - floor($base)), $precision) .' '. $suffixes[floor($base)];
    }
}
