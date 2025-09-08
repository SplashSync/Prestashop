<?php
/**
 *  This file is part of SplashSync Project.
 *
 *  Copyright (C) Splash Sync  <www.splashsync.com>
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 *
 * @author Splash Sync
 * @copyright Splash Sync SAS
 * @license MIT
 */

namespace Splash\Local\Command\Discounts;

use Splash\Local\Services\DiscountsManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

// phpcs:disable PSR1.Files.SideEffects
if (!defined('_PS_VERSION_')) {
    exit;
}
// phpcs:enable PSR1.Files.SideEffects

/**
 * Install & Check Order Discount Details Collector
 */
class Install extends Command
{
    /**
     * @inerhitDoc
     */
    protected function configure(): void
    {
        $this
            ->setName('splash:discount-collector:install')
            ->setDescription('Check install of Advanced Discounts Collector')
            ->addOption(
                'create',
                null,
                InputOption::VALUE_NONE,
                'Create Storage Table?'
            )
        ;
    }

    /**
     * @inerhitDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        //====================================================================//
        // Check if Parser Table is Configured
        $this->displayResult(
            $output,
            DiscountsManager::hasStorageTable(),
            'Storage Table Exists'
        );
        //====================================================================//
        // Check if Parser Table needs to be created
        if (!DiscountsManager::hasStorageTable() && $input->getOption('create')) {
            $this->displayResult(
                $output,
                DiscountsManager::createStorageTable(),
                'Storage Table Created'
            );
        }
        //====================================================================//
        // Check if Parser is Enabled
        $this->displayResult(
            $output,
            DiscountsManager::isFeatureActive(),
            'Discount Parser is Enabled'
        );
        if (!DiscountsManager::isFeatureActive()) {
            return 1;
        }

        return 1;
    }

    /**
     * @inerhitDoc
     */
    protected function displayResult(OutputInterface $output, ?bool $result, string $message): void
    {
        if ($result) {
            $output->writeln(sprintf('[<info> OK </info>] %s', $message));
        } elseif (is_null($result)) {
            $output->writeln(sprintf('[<comment>WARN</comment>] %s', $message));
        } else {
            $output->writeln(sprintf('[ <error>KO</error> ] %s', $message));
        }
    }
}
