<?php

namespace App\Commands;

use App\Models\File;
use App\Services\S3Service;
use App\Services\ImagickService;
use Symfony\Component\Console\Command\Command;
use Doctrine\Common\Collections\ArrayCollection;
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
        $files = $this->s3->getBucketContents();
        if (!$files) {
            return 1;
        }

        /**
         * Get a collection of valid, optimized files
         * @var ArrayCollection $optimized
         */
        $optimized = $this->imagick->optimize($files);
        $output->writeln('Successfully optimized ' . $optimized->count() . ' files. Preparing to upload to S3');
        
        /**
         * Replace the files on S3
         * @var int $result
         */
        $result = $this->s3->replaceFiles($optimized);
        $output->writeln('Successfully replaced ' . $result . ' files on S3');
        $output->writeln('Complete');

        return 0;
    }
}
