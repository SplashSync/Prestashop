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

use Splash\Local\Services\CustomerGroupsManager as GroupsManager;
use Splash\Local\Services\LanguagesManager as SLM;
use Splash\Models\Helpers\InlineHelper;

// phpcs:disable PSR1.Files.SideEffects
if (!defined('_PS_VERSION_')) {
    exit;
}
// phpcs:enable PSR1.Files.SideEffects

trait GroupsTrait
{
    /**
     * Build Fields using FieldFactory
     */
    protected function buildGroupsFields(): void
    {
        //====================================================================//
        // Customer Groups
        $this->fieldsFactory()->create(SPL_T_INLINE)
            ->identifier('groups')
            ->name(SLM::translate('Groups', 'AdminGlobal'))
            ->group('Meta')
            ->microData('http://schema.org/Organization', 'category')
            ->addChoices(GroupsManager::getAllGroupNames())
            ->isReadOnly()
        ;
        //====================================================================//
        // Customer Group
        $this->fieldsFactory()->create(SPL_T_VARCHAR)
            ->identifier('group')
            ->name(SLM::translate('Group', 'AdminGlobal'))
            ->group('Meta')
            ->microData('http://schema.org/Organization', 'category')
            ->addChoices(GroupsManager::getAllGroupNames())
        ;
    }

    /**
     * Read the requested fields
     *
     * @param string $key       Input List Key
     * @param string $fieldName Field Identifier / Name
     *
     * @return void
     */
    protected function getGroupsFields(string $key, string $fieldName): void
    {
        //====================================================================//
        // READ Fields
        switch ($fieldName) {
            case 'groups':
                $this->out[$fieldName] = InlineHelper::fromArray(
                    GroupsManager::getGroupNames($this->object)
                );
                GroupsManager::getAllGroupNames();

                break;
            case 'group':
                $group = GroupsManager::getGroup($this->object->id_default_group);
                $this->out[$fieldName] = null;
                if ($group && is_string($group->name)) {
                    $this->out[$fieldName] = strtolower($group->name);
                }

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
     * @return void
     */
    protected function setGroupsFields(string $fieldName, ?string $fieldData): void
    {
        //====================================================================//
        // WRITE Fields
        switch ($fieldName) {
            case 'group':
                unset($this->in[$fieldName]);
                $newGroup = GroupsManager::getGroupByName($fieldData);
                //====================================================================//
                // Nothing to Update
                if (!$newGroup || ($newGroup->id == $this->object->id_default_group)) {
                    break;
                }
                //====================================================================//
                // Update Groups List
                /** @var int[] $groups */
                $groups = $this->object->getGroups();
                $this->object->updateGroup(array_unique(array_merge(
                    $groups,
                    array($newGroup->id)
                )));
                //====================================================================//
                // Update Default Group
                $this->object->id_default_group = (int) $newGroup->id;
                $this->needUpdate();

                break;
        }
    }
}
