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

use Translate;

// phpcs:disable PSR1.Files.SideEffects
if (!defined('_PS_VERSION_')) {
    exit;
}
// phpcs:enable PSR1.Files.SideEffects

/**
 * Access to Third Party Meta Fields
 */
trait MetaTrait
{
    /**
     * Build Customers Unused Fields using FieldFactory
     *
     * @return void
     */
    private function buildMetaFields(): void
    {
        //====================================================================//
        // Active
        $this->fieldsFactory()->create(SPL_T_BOOL)
            ->identifier('active')
            ->name(Translate::getAdminTranslation('Enabled', 'AdminCustomers'))
            ->microData('http://schema.org/Organization', 'active')
            ->isListed()
        ;
        //====================================================================//
        // Newsletter
        $this->fieldsFactory()->create(SPL_T_BOOL)
            ->identifier('newsletter')
            ->name(Translate::getAdminTranslation('Newsletter', 'AdminCustomers'))
            ->group(Translate::getAdminTranslation('Meta', 'AdminThemes'))
            ->microData('http://schema.org/Organization', 'newsletter')
        ;
        //====================================================================//
        // Advertising
        $this->fieldsFactory()->create(SPL_T_BOOL)
            ->identifier('optin')
            ->name(Translate::getAdminTranslation('Opt-in', 'AdminCustomers'))
            ->group(Translate::getAdminTranslation('Meta', 'AdminThemes'))
            ->microData('http://schema.org/Organization', 'advertising')
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
    private function getMetaFields(string $key, string $fieldName): void
    {
        //====================================================================//
        // READ Fields
        switch ($fieldName) {
            case 'active':
            case 'newsletter':
            case 'passwd':
            case 'optin':
                $this->out[$fieldName] = $this->object->{$fieldName};

                break;
            default:
                return;
        }

        unset($this->in[$key]);
    }

    /**
     * Write Given Fields
     *
     * @param string $fieldName Field Identifier / Name
     * @param mixed  $fieldData Field Data
     *
     * @return void
     */
    private function setMetaFields(string $fieldName, $fieldData): void
    {
        //====================================================================//
        // WRITE Fields
        switch ($fieldName) {
            case 'active':
            case 'newsletter':
            case 'optin':
                if ($this->object->{$fieldName} != $fieldData) {
                    $this->object->{$fieldName} = (bool) $fieldData;
                    $this->needUpdate();
                }

                break;
            default:
                return;
        }
        unset($this->in[$fieldName]);
    }
}
