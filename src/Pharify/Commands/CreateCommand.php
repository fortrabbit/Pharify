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

use Jet\Console\Command\AbstractCommand,
    Jet\Console\Command\Argument;

/**
 * Long Description
 *
 * @author Ulrich Kautz <ulrich.kautz@gmail.com>
 */

class CreateCommand extends AbstractCommand
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
    public function init()
    {
        $this
            ->setName('create')
            ->setDescription('Create a new PHAR')
            ->addArgument('name', Argument::REQUIRED, null, 'Name of the phar output file, eg "foo" creates "foo.phar"')
            ->addArgument('includes', Argument::OPTIONAL, null, 'Comma separated list of relative paths to files or directories to include. Defaults to current directory.')
            ->addArgument('directory', Argument::OPTIONAL, null, 'Output folder. Defaults to current dir.')
            ->addArgument('stub', Argument::OPTIONAL, null, 'Optional stub file. Falls back default stub.');
    }


    /**
     * @throws \Exception
     */
    public function execute()
    {
        $this->pharName   = $this->getArgument('name');
        $this->pharFile   = $this->pharName. '.phar';
        $this->currentDir = getcwd();
        $this->outputDir  = $this->hasArgument('directory') ? $this->getArgument('directory') : $this->currentDir;
        $this->stubFile   = $this->hasArgument('stub') ? $this->getArgument('stub') : null;
        $this->includes   = $this->hasArgument('includes') ? preg_split('/\s*,\s*/', $this->getArgument('includes')) : [$this->currentDir];
        $this->phar       = new \Phar($this->outputDir. '/'. $this->pharFile, 0, $this->pharFile);

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
            $this->phar->addFromString($relPath, file_get_contents($path));
        } elseif (is_dir($path)) {
            if (($dh = opendir($path)) !== false) {
                while (($p = readdir($dh)) !== false) {
                    if ($p === '.' || $p === '..' || "$path/$p" == $this->outputDir. '/'. $this->pharFile) {
                        continue;
                    }
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