<?php


/*
 * This file is part of Pharify\Commands.
 *
 * (c) Ulrich Kautz <ulrich.kautz@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Pharify\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Pharify\Tools\Creator;


/**
 * Long Description
 *
 * @author Ulrich Kautz <ulrich.kautz@gmail.com>
 */

class CreateCommand extends Command
{
    const DEFAULT_INCLUDES = '\.(?:php)$';

    /**
     * @var string
     */
    protected $includes;

    /**
     * @var string
     */
    protected $workingDir;

    /**
     * @var \Phar
     */
    protected $phar;

    /**
     * @var bool
     */
    protected $verbose;


    /**
     * Init args
     */
    public function configure()
    {
        $this
            ->setName('create')
            ->setDescription('Create a new PHAR')
            ->addArgument('phar-name', InputArgument::REQUIRED, 'Name of the phar output file, eg "foo" creates "foo.phar"')
            ->addOption('directory', 'd', InputOption::VALUE_OPTIONAL, 'Working directory for sources. All paths has to be below. Defaults to current dir.')
            ->addOption('path', 'p', InputOption::VALUE_IS_ARRAY | InputOption::VALUE_OPTIONAL, 'Source directory or file to be included. If none given, all files and directories in working dir are used.')
            ->addOption('includes', 'i', InputOption::VALUE_OPTIONAL, 'Regular expression for including files. Default: '. self::DEFAULT_INCLUDES)
            ->addOption('output', 'o', InputOption::VALUE_OPTIONAL, 'Output folder. Defaults to current dir.')
            ->addOption('stub', 's', InputOption::VALUE_OPTIONAL, 'Optional stub file. Falls back default stub.')
            ->addOption('no-stub-wrap', 'w', InputOption::VALUE_NONE, 'Disable wrapping stub file in default stub. You know what you are doing?');
    }


    /**
     * @throws \Exception
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        if (ini_get('phar.readonly')) {
            throw new \Exception("Phar creation is disabled. Set 'phar.readonly' to 0 in php.ini");
        }

        // init creator
        $creator = new Creator();

        // generate & attach progress bar
        $progress = $this->getHelperSet()->get('progress');
        $creator->setProgressCallbacks(
            function ($amount) use (&$progress, &$output) {
                $progress->start($output, $amount);
            },
            function ($percent) use (&$progress) {
                $progress->advance();
            }
        );

        // set output
        $creator->setOutput($output);

        // set working dir
        $creator->setWorkingDir($input->getOption('directory') ?: getcwd());

        // set includes
        if ($includes = $input->getOption('includes')) {
            $creator->setIncludeRegex($includes);
        }

        // set stub file
        if ($stubFile = $input->getOption('stub')) {
            $creator->setStubFile($stubFile, $input->getOption('no-stub-wrap') ? false : true);
        }

        // add paths
        if ($paths = $input->getOption('path')) {
            foreach ((array)$paths as $path) {
                $creator->addIncludePath($path);
            }
        }

        // create the phar
        $pharName = $input->getArgument('phar-name');
        $creator->create($pharName, $input->getOption('output') ?: null);

        $output->writeln("Done");
    }


}
