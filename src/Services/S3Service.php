<?php

namespace App\Services;

use App\Models\File;
use Aws\S3\S3Client;
use Doctrine\Common\Collections\ArrayCollection;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use Symfony\Component\Console\Input\InputInterface;

class S3Service
{
    /** @var null|S3Client $client */
    protected $client;

    /** @var string $bucket */
    public $bucket = '';

    /** @var string $subFolder */
    public $subFolder = '';

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
}
