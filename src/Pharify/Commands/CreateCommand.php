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
use Pimple;


/**
 * Long Description
 *
 * @author Ulrich Kautz <ulrich.kautz@gmail.com>
 */

class Create2Command extends Command
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
            ->addOption('wrap-stub', 'w', InputOption::VALUE_NONE, 'If set: wrap the stub file contents in the default stub. <info>Enable this, if you don\'t know how to write a correct stub!</info>');
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
        $container = new Pimple();
        $container['output'] = $output;
        $creator = new Creator($container);
        //$creator = new Creator();

        // set working dir
        $creator->setWorkingDir($input->getOption('directory') ?: getcwd());

        // set includes
        if ($includes = $input->getOption('includes')) {
            $creator->setIncludeRegex($includes);
        }

        // set stub file
        if ($stubFile = $input->getOption('stub')) {
            $creator->setStubFile($stubFile, $input->getOption('wrap-stub'));
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
