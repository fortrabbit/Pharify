<?php


/*
 * This file is part of Pharify\Tools.
 *
 * (c) Ulrich Kautz <ulrich.kautz@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Pharify\Tools;

use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;

/**
 * Phar create logic
 *
 * @author Ulrich Kautz <ulrich.kautz@gmail.com>
 */

class Creator
{
    const DEFAULT_INCLUDES = '\.(?:php)$';

    /**
     * @var \Symfony\Component\Console\Output\OutputInterface
     */
    protected $output;

    /**
     * @var \Phar
     */
    protected $phar;

    /**
     * @var string
     */
    protected $workingDir;

    /**
     * @var string
     */
    protected $includeRegex;

    /**
     * @var array
     */
    protected $includePaths;

    /**
     * @var string
     */
    protected $stubFile;

    /**
     * @var bool
     */
    protected $stubWrap;

    /**
     * @var \Closure
     */
    protected $progressInitCallback;

    /**
     * @var \Closure
     */
    protected $progressAdvanceCallback;


    /**
     * Constructor for Pharify\Tools\Creator
     *
     * @param \Pimple  $container   Container for output (optional)
     *
     * @return bool
     *
     * @throws \Exception
     */
    public function __construct()
    {
        $this->includePaths = array();
        $this->includeRegex = self::DEFAULT_INCLUDES;
        $this->stubWrap     = true;
    }

    /**
     * Set callback for progress
     *
     * @param mixed $callback The callback or null
     */
    public function setProgressCallbacks($initCallback, $advanceCallback)
    {
        $this->progressInitCallback    = $initCallback;
        $this->progressAdvanceCallback = $advanceCallback;
    }


    /**
     * Set work directory
     *
     * @param string  $workingDir  The directory
     */
    public function setWorkingDir($workingDir)
    {
        $this->workingDir = realpath($workingDir);
    }

    /**
     * Set includes reqex
     *
     * @param string  $regex  The regex for includes
     */
    public function setIncludeRegex($regex)
    {
        $this->includeRegex = $regex;
    }

    /**
     * Set stub file (otherwise a default is used)
     *
     * @param string  $stubFile  The stub file
     * @param bool    $doWrap    Whether to wrap the stub file or not
     */
    public function setStubFile($stubFile, $doWrap = true)
    {
        $this->stubFile = $stubFile;
        $this->stubWrap = $doWrap;
    }

    /**
     * Adds include paths relative to work dir for input
     *
     * @param string  $workingDir  The directory
     */
    public function addIncludePath($path)
    {
        $absPath = realpath($this->workingDir. '/'. $path);
        if (strpos($absPath, $this->workingDir) !== 0) {
            throw new \Exception("$path is not in $this->workingPath");
        }
        $this->includePaths[] = $absPath;
    }

    /**
     * Set output interface
     *
     * @param Symfony\Component\Console\Output\OutputInterface  $output  The output interface
     */
    public function setOutput(OutputInterface $output)
    {
        $this->output = $output;
    }



    /**
     * Output message
     *
     * @param string  $msg  Message for output
     */
    public function output($msg, $verbose = false)
    {
        if (!is_null($this->output) && (!$verbose || OutputInterface::VERBOSITY_VERBOSE === $this->output->getVerbosity())) {
            $this->output->writeln($msg);
        }
    }

    /**
     * Create the phar file. Returns abs path to it.
     *
     * @param string  $pharName  Name of the phar
     *
     * @return string
     *
     * @throws \Exception
     */
    public function create($pharName, $outputDir = null)
    {
        // determine output
        if (is_null($outputDir)) {
            $outputDir = $this->workingDir;
        }
        $pharFile = "$pharName.phar";
        $pharPath = realpath($outputDir). "/$pharFile";
        $this->output("Creating phar file in $pharPath");

        // cleanup existing phar file
        if (file_exists($pharPath)) {
            unlink($pharPath);
        }

        // init phar
        $phar = new \Phar($pharPath, 0, $pharFile);

        // add paths to phar
        $stripLength = strlen($this->workingDir)+ 1;
        if (empty($this->includePaths)) {
            $this->includePaths = array($this->workingDir);
        }

        // determine files
        $fileList = array();
        foreach ($this->includePaths as $path) {
            if (is_file($path)) {
                $relPath = substr($path, $stripLength);
                if (preg_match('/'. $this->includes. '/', $path)) {
                    $this->output("<info>+ Add: $relPath</info>", true);
                    //$phar->addFile($path, $relPath);
                    $fileList []= [$path, $relPath];
                } else {
                    $this->output("<info>- Ignore: $relPath</info>", true);
                }
            } elseif (is_dir($path)) {
                $finder = new Finder();
                foreach ($finder->in($path)->files()->name('/'. $this->includeRegex. '/') as $p) {
                    $relPath = substr($p, $stripLength);
                    $this->output("<info>+ Add: $relPath</info>", true);
                    //$phar->addFile($p, $relPath);
                    $fileList []= [$p, $relPath];
                }
            }
        }

        // add to phar
        $countFiles = count($fileList);
        $progress   = 0;
        if ($initCallback = $this->progressInitCallback) {
            $initCallback($countFiles);
        }
        $advanceCallback = $this->progressAdvanceCallback;
        foreach ($fileList as $num => $ref) {
            list($path, $relPath) = $ref;
            $percent = floor($num * 100/$countFiles);
            if ($advanceCallback) {
                $progress = $percent;
                $advanceCallback($percent);
            }
            $phar->addFile($path, $relPath);
        }

        // add stub file
        if (!is_null($this->stubFile)) {
            $stubContents = file_get_contents($this->stubFile);
            if (!$this->stubWrap && !preg_match('/__HALT_COMPILER/', $stubContents)) {
                throw new \Exception("Stub file does not look like a correct formatted stub file. You need to wrap it!");
            }
            if ($this->stubWrap) {
                $this->output("Using wrapped stub file $this->stubFile", true);
                $phar->setStub($phar->createDefaultStub($this->stubFile));
            } else {
                $this->output("Using stub file $this->stubFile", true);
                $phar->setStub($stubContents);
            }
        } else {
            $this->output("Using default stub", true);
            $phar->setStub("#!/usr/bin/env php\n<?php\nPhar::mapPhar('". $pharFile. "');\n__HALT_COMPILER();");
        }

        // stop everything
        $phar->stopBuffering();

        // set executable
        chmod($pharPath, 0755);

        return $pharPath;
    }


}
