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
 *
 * @copyright Splash Sync SAS
 *
 * @license MIT
 */

namespace Splash\Local\Command\Discounts;

use Configuration;
use Currency;
use Splash\Client\Splash;
use Splash\Local\Services\DiscountsManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

// phpcs:disable PSR1.Files.SideEffects
if (!defined('_PS_VERSION_')) {
    exit;
}
// phpcs:enable PSR1.Files.SideEffects

/**
 * Check Order Discounts Details
 */
class Check extends Command
{
    /**
     * @inerhitDoc
     */
    protected function configure()
    {
        $this
            ->setName('splash:discount-collector:check')
            ->setDescription("Check Order cache on Advanced Discounts Collector")
            ->addArgument(
                'orderId',
                InputArgument::REQUIRED,
                'Order ID to Check ?'
            )
            ->addOption(
                'flush',
                null,
                InputOption::VALUE_NONE,
                'Clear Order discounts details cache'
            )
        ;
    }

    /**
     * @inerhitDoc
     *
     * @throws \PrestaShopDatabaseException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        //====================================================================//
        // Splash Module Class Includes
        require_once(_PS_ROOT_DIR_.'/modules/splashsync/splashsync.php');
        //====================================================================//
        // Check if Parser is Enabled
        $this->displayResult(
            $output,
            DiscountsManager::isFeatureActive(),
            "Discount Parser is Enabled"
        );
        if (!DiscountsManager::isFeatureActive()) {
            return 1;
        }
        //====================================================================//
        // Load Default Currency
        $currency = new Currency((int) Configuration::get('PS_CURRENCY_DEFAULT'));
        //====================================================================//
        // Safety Check
        $orderId = $input->getArgument("orderId");
        if (!$orderId || !is_string($orderId)) {
            $this->displayResult($output, false, "No Order ID provided");

            return 0;
        }
        //====================================================================//
        // Check if Order has Cache
        $this->displayResult(
            $output,
            DiscountsManager::hasOrderDiscountsDetails($orderId, $currency),
            sprintf("Order %s has Discount Details", $orderId)
        );
        //====================================================================//
        // If Clear Details Requested
        if ($input->getOption('flush')) {
            $this->displayResult(
                $output,
                DiscountsManager::deleteOrderDiscountsDetails($orderId),
                sprintf("Order %s Discount Details now Deleted", $orderId)
            );
        }

        return 1;
    }

    /**
     * @inerhitDoc
     */
    protected function displayResult(OutputInterface $output, ?bool $result, string $message): ?bool
    {
        if ($result) {
            $output->writeln(sprintf('[<info> OK </info>] %s', $message));
        } elseif (is_null($result)) {
            $output->writeln(sprintf('[<comment>WARN</comment>] %s', $message));
        } else {
            $output->writeln(sprintf('[ <error>KO</error> ] %s', $message));
        }

        if (!empty(Splash::log()->err)) {
            $output->writeln(Splash::log()->getConsoleLog());
        }

        return $result;
    }
}
