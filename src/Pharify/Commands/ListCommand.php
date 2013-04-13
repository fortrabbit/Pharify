<?php

/*
 * This file is part of the Pharify package.
 *
 * (c) Ulrich Kautz <uk@fortrabbit.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Pharify\Commands;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Finder\Finder;

/**
 * ListCommand displays the contents of a phar file
 *
 * @author Ulrich Kautz <uk@fortrabbit.com>
 */
class ListCommand extends Command
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('list')
            ->setDescription('List the contents of a PHAR file')
            ->addArgument('phar-file', InputArgument::REQUIRED, 'Path to the phar file')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $pharFile = $input->getArgument('phar-file');
        $finder = new Finder();
        $count  = 0;
        $output->writeln("Listing phar contents:");
        $strip  = strlen("phar://$pharFile/");
        foreach ($finder->in("phar://$pharFile")->files() as $file) {
            $output->writeln(" * ". substr($file, $strip));
            $count++;
        }
        $output->writeln("\nFound $count files in $pharFile");
    }
}
