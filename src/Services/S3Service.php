<?php

namespace App\Services;

use SplFileInfo;
use App\Models\File;
use Aws\S3\S3Client;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Console\Input\InputInterface;

class S3Service
{
    /** @var S3Client $client */
    protected $client;

    /** @var string $bucket */
    public $bucket = '';

    /** @var string $subFolder */
    public $subFolder = '';


    public function __construct()
    {
        
        // $opts = $this->parseInput($input);
    }

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

    public function listBuckets()
    {
        return $this->client->listBuckets();
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
        
        $limit = 3;
        $count = 0;

        /** @var SplFileInfo $file */
        foreach ($iterator as $file) {
            if ($count++ > $limit) {
                break;
            }
            
            $file = new File($file);

            $files->add($file);
        }

        return $files;
    }
}
