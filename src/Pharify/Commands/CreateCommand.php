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

use Symfony\Component\Console\Command\Command,
    Symfony\Component\Console\Input\InputArgument,
    Symfony\Component\Console\Input\InputInterface,
    Symfony\Component\Console\Input\InputOption,
    Symfony\Component\Console\Output\OutputInterface;


/**
 * Long Description
 *
 * @author Ulrich Kautz <ulrich.kautz@gmail.com>
 */

class CreateCommand extends Command
{

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
     * Init args
     */
    public function configure()
    {
        $this
            ->setName('create')
            ->setDescription('Create a new PHAR')
            ->addArgument('name', null, InputArgument::REQUIRED, 'Name of the phar output file, eg "foo" creates "foo.phar"')
            ->addOption('includes', null, InputOption::VALUE_OPTIONAL, 'Comma separated list of relative paths to files or directories to include. Defaults to current directory.')
            ->addOption('directory', null, InputOption::VALUE_OPTIONAL, 'Output folder. Defaults to current dir.')
            ->addOption('stub', null, InputOption::VALUE_OPTIONAL, 'Optional stub file. Falls back default stub.');
    }


    /**
     * @throws \Exception
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $this->pharName   = $input->getArgument('name');
        $this->pharFile   = $this->pharName. '.phar';
        $this->currentDir = getcwd();
        $this->outputDir  = $input->getArgument('directory') ?: $this->currentDir;
        $this->outputFile = $this->outputDir. '/'. $this->pharFile;
        $this->stubFile   = $input->hasArgument('stub') ? $input->getArgument('stub') : null;
        $this->includes   = $input->hasArgument('includes') ? preg_split('/\s*,\s*/', $input->getArgument('includes')) : [$this->currentDir];
        error_log("DIR: $this->outputDir");

        if (file_exists($this->outputFile)) {
            unlink($this->outputFile);
        }
        $this->phar       = new \Phar($this->outputFile, 0, $this->pharFile);


        foreach ($this->includes as $path) {
            $absPath = realpath($path);
            if (strpos($absPath, $this->currentDir) !== 0) {
                throw new \Exception("Could not find '$path' in current directory");
            }
            $this->addPharFiles($absPath);
        }

        if (!is_null($this->stubFile)) {
            $this->phar->setStub($this->phar->createDefaultStub($this->stubFile));
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
        $relPath = substr($path, strlen($this->currentDir)+ 1);
        if (is_file($path)) {
            $this->phar->addFromString($relPath, file_get_contents($path));
        } elseif (is_dir($path)) {
            if (($dh = opendir($path)) !== false) {
                while (($p = readdir($dh)) !== false) {
                    if ($p === '.' || $p === '..' || "$path/$p" == $this->outputFile || (is_dir("$path/$p") && in_array($p, array('.git', '.svn',
                        '.hg')))) {
                        continue;
                    }
                    error_log(" Add $relPath/$p");
                    $this->addPharFiles("$path/$p");
                }
                closedir($dh);
            }
        }
    }

}


/*
<?php __HALT_COMPILER();
*/