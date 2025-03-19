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

namespace Splash\Local\Configurators;

use Splash\Core\SplashCore as Splash;

// phpcs:disable PSR1.Files.SideEffects
if (!defined('_PS_VERSION_')) {
    exit;
}
// phpcs:enable PSR1.Files.SideEffects

class MarketplaceVendorConfigurator extends StockOnlyConfigurator
{
    /**
     * {@inheritdoc}
     */
    public static function getName(): string
    {
        return "Marketplace Client: @Products, Only Skus & Stocks are Writable, All the rest is disabled.";
    }

    /**
     * {@inheritdoc}
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function overrideDescription(string $objectType, array $description): array
    {
        Splash::log()->trace();
        //====================================================================//
        // Products => Allow Sync
        if ("Product" == $objectType) {
            return array_replace_recursive($description, array(
                "allow_push_created" => 0,
                "allow_push_updated" => 1,
                "allow_push_deleted" => 0,
                "enable_push_created" => 0,
                "enable_push_updated" => 1,
                "enable_push_deleted" => 0,
            ));
        }

        //====================================================================//
        // Other Types => NO Sync
        return array_replace_recursive($description, array(
            "allow_push_created" => 0,
            "allow_push_updated" => 0,
            "allow_push_deleted" => 0,
            "enable_push_created" => 0,
            "enable_push_updated" => 0,
            "enable_push_deleted" => 0,
            "enable_pull_created" => 0,
            "enable_pull_updated" => 0,
            "enable_pull_deleted" => 0,
        ));
    }
}
