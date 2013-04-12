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
    protected $pharName;

    /**
     * @var string
     */
    protected $pharFile;

    /**
     * @var string
     */
    protected $outputDir;

    /**
     * @var string
     */
    protected $outputFile;

    /**
     * @var string
     */
    protected $stubFile;

    /**
     * @var array
     */
    protected $paths;

    /**
     * @var string
     */
    protected $includes;

    /**
     * @var string
     */
    protected $currentDir;

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
            ->addOption('path', 'p', InputOption::VALUE_IS_ARRAY | InputOption::VALUE_OPTIONAL, 'Directory or file to be included. Defaults to current dir.')
            ->addOption('includes', 'i', InputOption::VALUE_OPTIONAL, 'Regular expression for including files. Default: '. self::DEFAULT_INCLUDES)
            ->addOption('output', 'o', InputOption::VALUE_OPTIONAL, 'Output folder. Defaults to current dir.')
            ->addOption('stub', 's', InputOption::VALUE_OPTIONAL, 'Optional stub file. Falls back default stub.')
            ->addOption('wrap-stup', 'w', InputOption::VALUE_NONE, 'If set: wrap the stub file contents in the default stub.');
    }


    /**
     * @throws \Exception
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {

        // init
        $this->pharName    = $input->getArgument('phar-name');
        $this->pharFile    = $this->pharName. '.phar';
        $this->currentDir  = getcwd();
        $this->outputDir   = $input->getOption('output') ?: $this->currentDir;
        $this->outputFile  = $this->outputDir. '/'. $this->pharFile;
        $this->stubFile    = $input->getOption('stub') ?: null;
        $this->paths       = $input->getOption('path') ? (array)$input->getOption('path') : array($this->currentDir);
        $this->includes    = $input->getOption('includes') ?: self::DEFAULT_INCLUDES;
        $this->verbose     = $input->getOption('verbose');
        $this->output      = &$output;

        // cleanup old
        if (file_exists($this->outputFile)) {
            unlink($this->outputFile);
        }

        // setup phar
        $this->phar = new \Phar($this->outputFile, 0, $this->pharFile);


        // iterate paths
        foreach ($this->paths as $path) {
            $absPath = realpath($path);
            if (strpos($absPath, $this->currentDir) !== 0) {
                throw new \Exception("Could not find '$path' in current directory");
            }
            $this->addPharFiles($absPath);
        }

        if (!is_null($this->stubFile)) {
            error_log("USING STUB FILE");
            if ($input->getOption('wrap-stup')) {
                $this->phar->setStub($this->phar->createDefaultStub($this->stubFile));
            } else {
                $this->phar->setStub(file_get_contents($this->stubFile));
            }
        } else {
            $this->phar->setStub("#!/usr/bin/env php\n<?php\nPhar::mapPhar('". $this->pharFile. "');\n__HALT_COMPILER();");
        }
        $this->phar->stopBuffering();

        chmod($this->outputFile, 0755);
    }

    /**
     * The long description
     *
     * @param string  $dir   Directory to look in
     */
    protected function addPharFiles($path)
    {
        if (is_file($path)) {
            $relPath = substr($path, strlen($this->currentDir)+ 1);
            if (preg_match('/'. $this->includes. '/', $path)) {
                #error_log("ADD $path as $relPath");
                $this->phar->addFile($path, $relPath);
            } elseif ($this->verbose) {
                error_log("Ignore: $path");
            }
        } elseif (is_dir($path)) {
            $finder = new Finder();
            foreach ($finder->in($path)->files()->name('/'. $this->includes. '/') as $p) {
                $relPath = substr($p, strlen($this->currentDir)+ 1);
                #error_log("ADD FINDER $p as $relPath");
                $this->phar->addFile($p, $relPath);
            }
        }
    }

}


/*
<?php __HALT_COMPILER();
*/