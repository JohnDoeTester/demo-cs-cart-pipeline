<?php
/***************************************************************************
 *                                                                          *
 *   (c) 2004 Vladimir V. Kalynyak, Alexey V. Vinokurov, Ilya M. Shalnev    *
 *                                                                          *
 * This  is  commercial  software,  only  users  who have purchased a valid *
 * license  and  accept  to the terms of the  License Agreement can install *
 * and use this program.                                                    *
 *                                                                          *
 ****************************************************************************
 * PLEASE READ THE FULL TEXT  OF THE SOFTWARE  LICENSE   AGREEMENT  IN  THE *
 * "copyright.txt" FILE PROVIDED WITH THIS DISTRIBUTION PACKAGE.            *
 ****************************************************************************/

namespace Tygh\UpgradeCenter\Console;

use Phinx\Console\Command\Breakpoint;
use Phinx\Console\Command\Create;
use Phinx\Console\Command\Init;
use Phinx\Console\Command\ListAliases;
use Phinx\Console\Command\Rollback;
use Phinx\Console\Command\SeedCreate;
use Phinx\Console\Command\SeedRun;
use Phinx\Console\Command\Status;
use Phinx\Console\Command\Test;
use Phinx\Console\PhinxApplication;
use Tygh\UpgradeCenter\Console\Command\UpgradeMigrate;

/**
 * Phinx console application.
 *
 * @author Rob Morgan <robbym@gmail.com>
 */
class UpgradePhinxApplication extends PhinxApplication
{
    /**
     * Initialize the Phinx console application.
     */
    public function __construct()
    {
        parent::__construct('Phinx by CakePHP - https://phinx.org.');

        $this->addCommands([
            new Init(),
            new Create(),
            new UpgradeMigrate(),
            new Rollback(),
            new Status(),
            new Breakpoint(),
            new Test(),
            new SeedCreate(),
            new SeedRun(),
            new ListAliases(),
        ]);
    }
}
