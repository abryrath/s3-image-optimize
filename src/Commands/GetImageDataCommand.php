<?php

namespace App\Commands;

use App\Services\S3Service;
use App\Services\ImagickService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Output\OutputInterface;

class GetImageDataCommand extends Command
{
    protected static $defaultName = 'image-data';

    /** @var S3Service $s3 */
    protected $s3;

    /** @var ImagickService $imagick */
    protected $imagick;

    public function __construct(S3Service $s3, ImagickService $imagick)
    {
        $this->s3 = $s3;
        $this->imagick = $imagick;

        parent::__construct();
    }

    /**
     * @return void
     */
    protected function configure()
    {
        $this
            ->setDescription('Optimize S3 image assets to under a given threshold')
            // ->setHelp('This command lists all assets from the S3 Bucket')
            ->setDefinition(
                new InputDefinition([
                    new InputOption('region', 'r', InputOption::VALUE_OPTIONAL),
                    new InputOption('bucket', 'b', InputOption::VALUE_REQUIRED),
                    new InputOption('accessKey', 'a', InputOption::VALUE_REQUIRED),
                    new InputOption('secretAccessKey', 's', InputOption::VALUE_REQUIRED),
                    new InputOption('subfolder', 'd', InputOption::VALUE_OPTIONAL),
                    new InputOption('outputFile', 'o', InputOption::VALUE_REQUIRED),
                ])
            );
    }

    /**
     * @return null|int
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->s3->parseInput($input);
        $this->imagick->setOutput($output);

        /**
         * Get all of the files in the bucket/subfolder
         * @var ArrayCollection $files
         */
        $this->s3->writeImageData($this->imagick);
        
        return 0;
    }
}
