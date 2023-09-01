<?php

/*
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
 */

namespace Splash\Local\Objects\Core;

use Configuration;
use Splash\Models\AbstractConfigurator;

trait ConfiguratorAwareTrait
{
    /**
     * {@inheritdoc}
     */
    public function description(): array
    {
        $description = parent::description();
        //====================================================================//
        // If Expert Mode is Disabled => Skipp
        if (empty(Configuration::get('SPLASH_WS_EXPERT'))) {
            return $description;
        }
        //====================================================================//
        // If Requested Configurator Exists => Apply
        $configuratorClass = (string) Configuration::get('SPLASH_CONFIGURATOR');
        if (!class_exists($configuratorClass) || !is_subclass_of($configuratorClass, AbstractConfigurator::class)) {
            return $description;
        }

        //====================================================================//
        // Apply Overrides & Return Object Description Array
        return (new $configuratorClass())->overrideDescription(static::getType(), $description);
    }
    /**
     * Build Fields using FieldFactory
     *
     * @return void
     */
    protected function buildConfiguratorFields(): void
    {
        //====================================================================//
        // If Expert Mode is Disabled => Skipp
        if (empty(Configuration::get('SPLASH_WS_EXPERT'))) {
            return;
        }
        //====================================================================//
        // If Requested Configurator Exists => Apply
        $configurator = (string) Configuration::get('SPLASH_CONFIGURATOR');
        if (class_exists($configurator) && is_subclass_of($configurator, AbstractConfigurator::class)) {
            $this->fieldsFactory()->registerConfigurator($this->getType(), new $configurator());
        }
    }
}
