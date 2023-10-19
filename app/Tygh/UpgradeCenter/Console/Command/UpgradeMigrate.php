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

namespace Tygh\UpgradeCenter\Console\Command;

use DateTime;
use Phinx\Console\Command\Migrate;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UpgradeMigrate extends Migrate
{
    /**
     * @var string
     */
    protected static $defaultName = 'migrate';

    /**
     * Migrate the database.
     *
     * @param \Symfony\Component\Console\Input\InputInterface   $input  Input
     * @param \Symfony\Component\Console\Output\OutputInterface $output Output
     *
     * @return int integer 0 on success, or an error code.
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->bootstrap($input, $output);

        $version = $input->getOption('target');
        $environment = $input->getOption('environment');
        $date = $input->getOption('date');
        $fake = (bool) $input->getOption('fake');

        if ($environment === null) {
            $environment = $this->getConfig()->getDefaultEnvironment();
            $output->writeln('<comment>warning</comment> no environment specified, defaulting to: ' . $environment);
        } else {
            $output->writeln('<info>using environment</info> ' . $environment);
        }

        if (!$this->getConfig()->hasEnvironment($environment)) {
            $output->writeln(sprintf('<error>The environment "%s" does not exist</error>', $environment));

            return self::CODE_ERROR;
        }

        $envOptions = $this->getConfig()->getEnvironment($environment);
        if (isset($envOptions['adapter'])) {
            $output->writeln('<info>using adapter</info> ' . $envOptions['adapter']);
        }

        if (isset($envOptions['wrapper'])) {
            $output->writeln('<info>using wrapper</info> ' . $envOptions['wrapper']);
        }

        if (isset($envOptions['name'])) {
            $output->writeln('<info>using database</info> ' . $envOptions['name']);
        } else {
            $output->writeln('<error>Could not determine database name! Please specify a database name in your config file.</error>');

            return self::CODE_ERROR;
        }

        if (isset($envOptions['table_prefix'])) {
            $output->writeln('<info>using table prefix</info> ' . $envOptions['table_prefix']);
        }
        if (isset($envOptions['table_suffix'])) {
            $output->writeln('<info>using table suffix</info> ' . $envOptions['table_suffix']);
        }

        $versionOrder = $this->getConfig()->getVersionOrder();
        $output->writeln('<info>ordering by</info> ' . $versionOrder . ' time');

        if ($fake) {
            $output->writeln('<comment>warning</comment> performing fake migrations');
        }

        // run the migrations
        $start = microtime(true);
        if ($date !== null) {
            $this->getManager()->migrateToDateTime($environment, new DateTime($date), $fake);
        } else {
            if ($version) {
                $version = (int)$version;
            }
            $this->getManager()->migrate($environment, $version, $fake);
        }
        $end = microtime(true);

        $output->writeln('');
        $output->writeln('<comment>All Done. Took ' . sprintf('%.4fs', $end - $start) . '</comment>');

        return self::CODE_SUCCESS;
    }
}
