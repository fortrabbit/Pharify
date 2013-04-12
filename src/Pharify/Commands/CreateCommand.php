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
     * Init args
     */
    public function init()
    {
        $this
            ->setName('create')
            ->setDescription('Create a new PHAR')
            ->addArgument('name', Argument::REQUIRED, null, 'Name of the phar output file, eg "foo" creates "foo.phar"')
            ->addArgument('directory', Argument::OPTIONAL, null, 'Output folder. Defaults to current dir.')
            ->addArgument('stub', Argument::OPTIONAL, null, 'Optional stub file.');
    }


    /**
     *
     */
    public function execute()
    {
        $name      = $this->getArgument('name');
        $outputDir = $this->hasArgument('directory') ? $this->getArgument('directory') : getcwd();
        print_r(['NAME' => $name, 'DIR' => $outputDir]);
    }
}
