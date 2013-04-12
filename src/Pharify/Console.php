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

/**
 * Long Description
 *
 * @author Ulrich Kautz <ulrich.kautz@gmail.com>
 */

class Console extends \Jet\Console\Console
{
    const VERSION = '0.1.0';

    /**
     * Creates new pharify console, inits included commands
     */
    public function __construct()
    {
        parent::__construct('pharify', self::VERSION);
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
                $this->addCommand(new $commandClass);
            }
            closedir($dh);
        }
    }

}
