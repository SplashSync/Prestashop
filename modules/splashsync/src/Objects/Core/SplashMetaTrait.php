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

namespace Splash\Local\Objects\Core;

use PrestaShopDatabaseException;
use Splash\Local\Services\MultiShopManager as MSM;
use Translate;

// phpcs:disable PSR1.Files.SideEffects
if (!defined('_PS_VERSION_')) {
    exit;
}
// phpcs:enable PSR1.Files.SideEffects

/**
 * Access to Objects Splash Meta Fields
 */
trait SplashMetaTrait
{
    /**
     * @var null|string
     */
    protected ?string $NewSplashId = null;

    /**
     * Build Fields using FieldFactory
     *
     * @return void
     */
    protected function buildSplashMetaFields()
    {
        //====================================================================//
        // Splash Unique Object Id
        $this->fieldsFactory()->create(SPL_T_VARCHAR)
            ->identifier('splash_id')
            ->name('Splash Id')
            ->group(Translate::getAdminTranslation('Meta', 'AdminThemes'))
            ->addOption('shop', MSM::MODE_ALL)
            ->microData('http://splashync.com/schemas', 'ObjectId')
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
    protected function getSplashMetaFields(string $key, string $fieldName): void
    {
        //====================================================================//
        // READ Fields
        switch ($fieldName) {
            case 'splash_id':
                $this->out[$fieldName] = self::getSplashId(self::$name, (int) $this->object->id);

                break;
            default:
                return;
        }

        unset($this->in[$key]);
    }

    /**
     * Write Given Fields
     *
     * @param string      $fieldName Field Identifier / Name
     * @param null|string $fieldData Field Data
     *
     * @throws PrestaShopDatabaseException
     *
     * @return void
     */
    protected function setSplashMetaFields(string $fieldName, ?string $fieldData): void
    {
        //====================================================================//
        // WRITE Fields
        switch ($fieldName) {
            case 'splash_id':
                if ($this->object->id) {
                    self::setSplashId(self::$name, $this->object->id, $fieldData);
                } else {
                    $this->NewSplashId = $fieldData;
                }

                break;
            default:
                return;
        }
        unset($this->in[$fieldName]);
    }
}
