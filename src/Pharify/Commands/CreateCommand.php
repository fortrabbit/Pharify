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
use Symfony\Component\Finder\Finder;


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
        
        // init class vars
        $this->workingDir  = realpath($input->getOption('directory') ?: getcwd());
        $this->includes    = $input->getOption('includes') ?: self::DEFAULT_INCLUDES;
        $this->verbose     = OutputInterface::VERBOSITY_VERBOSE === $output->getVerbosity();
        $this->output      = &$output;

        // init local vars
        $pharName    = $input->getArgument('phar-name');
        $pharFile    = $pharName. '.phar';
        $outputDir   = $input->getOption('output') ?: $this->workingDir;
        $outputFile  = $outputDir. '/'. $pharFile;
        $stubFile    = $input->getOption('stub') ?: null;
        $paths       = $input->getOption('path') ? (array)$input->getOption('path') : array($this->workingDir);

        // cleanup old
        if (file_exists($outputFile)) {
            unlink($outputFile);
        }
        
        $this->output->writeln("Creating $outputFile");

        // setup phar
        $this->phar = new \Phar($outputFile, 0, $pharFile);

        // iterate paths
        foreach ($paths as $path) {
            $absPath = $path;
            if (strpos($absPath, '/') !== 0) {
                $absPath = $this->workingDir. '/'. $path;
            }
            $absPath = realpath($absPath);
            if (strpos($absPath, $this->workingDir) !== 0) {
                throw new \Exception("Could not find '$path' in '$this->workingDir'");
            }
            $this->addPharFiles($absPath);
        }

        if (!is_null($stubFile)) {
            $stubContents = file_get_contents($stubFile);
            $doWrap       = $input->getOption('wrap-stub');
            if (!$doWrap && !preg_match('/__HALT_COMPILER/', $stubContents)) {
                throw new \Exception("Stub file does not look like a correct formatted stub file. Use the --wrap-stub option");
            }
            if ($doWrap) {
                if ($this->verbose) {
                    $this->output->writeln("Using wrapped stub file $stubFile");
                }
                $this->phar->setStub($this->phar->createDefaultStub($stubFile));
            } else {
                if ($this->verbose) {
                    $this->output->writeln("Using stub file $stubFile");
                }
                $this->phar->setStub($stubContents);
            }
        } else {
            if ($this->verbose) {
                $this->output->writeln("Using default stub");
            }
            $this->phar->setStub("#!/usr/bin/env php\n<?php\nPhar::mapPhar('". $pharFile. "');\n__HALT_COMPILER();");
        }
        $this->phar->stopBuffering();

        chmod($outputFile, 0755);
        
        $this->output->writeln("Done");
    }

    /**
     * Adds directories and files to phar
     *
     * @param string  $path   Directory or file to add
     */
    protected function addPharFiles($path)
    {
        $stripLength = strlen($this->workingDir)+ 1;
        if (is_file($path)) {
            $relPath = substr($path, $stripLength);
            if (preg_match('/'. $this->includes. '/', $path)) {
                if ($this->verbose) {
                    $this->output->writeln("<info>+ Add: $relPath</info>");
                }
                $this->phar->addFile($path, $relPath);
            } elseif ($this->verbose) {
                $this->output->writeln("<info>- Ignore: $realpath</info>");
            }
        } elseif (is_dir($path)) {
            $finder = new Finder();
            foreach ($finder->in($path)->files()->name('/'. $this->includes. '/') as $p) {
                $relPath = substr($p, $stripLength);
                if ($this->verbose) {
                    $this->output->writeln("<info>+ Add: $relPath</info>");
                }
                $this->phar->addFile($p, $relPath);
            }
        }
    }

}


/*
<?php __HALT_COMPILER();
*/