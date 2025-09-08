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

namespace Splash\Local\Objects\ThirdParty;

use Splash\Local\Services\LanguagesManager as SLM;

// phpcs:disable PSR1.Files.SideEffects
if (!defined('_PS_VERSION_')) {
    exit;
}
// phpcs:enable PSR1.Files.SideEffects

/**
 * Access to ThirdParty Core Fields
 */
trait CoreTrait
{
    /**
     * Build Customers Core Fields using FieldFactory
     *
     * @return void
     */
    protected function buildCoreFields(): void
    {
        //====================================================================//
        // Email
        $this->fieldsFactory()->create(SPL_T_EMAIL)
            ->identifier('email')
            ->name(SLM::translate('Email address', 'AdminGlobal'))
            ->microData('http://schema.org/ContactPoint', 'email')
            ->association('firstname', 'lastname')
            ->isRequired()
            ->isPrimary()
            ->isListed()
        ;
    }

    /**
     * Read requested Field
     *
     * @param string $key       Input List Key
     * @param string $fieldName Field Identifier / Name
     *
     * @return void
     */
    protected function getCoreFields(string $key, string $fieldName): void
    {
        //====================================================================//
        // READ Field
        switch ($fieldName) {
            case 'email':
                $this->out[$fieldName] = $this->object->{$fieldName};
                unset($this->in[$key]);

                break;
        }
    }

    /**
     * Write Given Fields
     *
     * @param string $fieldName Field Identifier / Name
     * @param string $fieldData Field Data
     *
     * @return void
     */
    protected function setCoreFields(string $fieldName, string $fieldData): void
    {
        //====================================================================//
        // WRITE Fields
        switch ($fieldName) {
            case 'email':
                if ($this->object->{$fieldName} != $fieldData) {
                    $this->object->{$fieldName} = $fieldData;
                    $this->needUpdate();
                }
                unset($this->in[$fieldName]);

                break;
        }
    }
}
