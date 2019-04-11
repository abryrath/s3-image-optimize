<?php

namespace App\Commands;

use App\Models\File;
use App\Services\S3Service;
use App\Services\ImagickService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Output\OutputInterface;

class OptimizeCommand extends Command
{
    /** @var string $defaultName */
    protected static $defaultName = 'optimize';

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
            ->setDescription('Get a list of assets from the S3 Bucket')
            ->setHelp('This command lists all assets from the S3 Bucket')
            ->setDefinition(
                new InputDefinition([
                    new InputOption('region', 'r', InputOption::VALUE_OPTIONAL),
                    new InputOption('bucket', 'b', InputOption::VALUE_REQUIRED),
                    new InputOption('accessKey', 'a', InputOption::VALUE_REQUIRED),
                    new InputOption('secretAccessKey', 's', InputOption::VALUE_REQUIRED),
                    new InputOption('subfolder', 'd', InputOption::VALUE_OPTIONAL),
                ])
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {        
        
        $this->s3->parseInput($input);
        $files = $this->s3->getBucketContents();
        if (!$files) {
            return false;
        }
        $result = $this->imagick->optimize($files);

        $output->write(\json_encode($result, JSON_PRETTY_PRINT));
    }
}
