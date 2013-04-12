<?php


/*
 * This file is part of Pharify.
 *
 * (c) Ulrich Kautz <ulrich.kautz@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Pharify;

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputDefinition;

/**
 * Long Description
 *
 * @author Ulrich Kautz <ulrich.kautz@gmail.com>
 */

class Console extends Application
{
    const VERSION = '0.1.0';

    /**
     * Creates new pharify console, inits included commands
     */
    public function __construct()
    {
        parent::__construct();
        $this->initCommands();
    }

    /**
     * Loads all included commands
     */
    protected function initCommands()
    {
        if (($dh = opendir(__DIR__. '/Commands')) !== false) {
            while (($path = readdir($dh)) !== false) {
                if ($path === '.' || $path === '..') {
                    continue;
                }
                $commandClass = '\\Pharify\\Commands\\'. preg_replace('/\.php$/', '', $path);
                $this->add(new $commandClass);
            }
            closedir($dh);
        }
        print_r(['C' => $this->commands]);
    }

    /**
     * Gets the default input definition.
     *
     * @return InputDefinition An InputDefinition instance
     */
    protected function getDefaultInputDefinition()
    {
        return new InputDefinition(array(
            new InputArgument('command', InputArgument::REQUIRED, 'The command to execute'),

            new InputOption('--help',           '-h', InputOption::VALUE_NONE, 'Display this help message.'),
            #new InputOption('--quiet',          '-q', InputOption::VALUE_NONE, 'Do not output any message.'),
            new InputOption('--verbose',        '-v', InputOption::VALUE_NONE, 'Increase verbosity of messages.'),
            new InputOption('--version',        '-V', InputOption::VALUE_NONE, 'Display this application version.'),
            #new InputOption('--ansi',           '',   InputOption::VALUE_NONE, 'Force ANSI output.'),
            #new InputOption('--no-ansi',        '',   InputOption::VALUE_NONE, 'Disable ANSI output.'),
            new InputOption('--no-interaction', '-n', InputOption::VALUE_NONE, 'Do not ask any interactive question.'),
        ));
    }

}
