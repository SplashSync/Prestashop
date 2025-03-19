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

namespace Splash\Local\Services;

use Group;
use Splash\Local\Services\LanguagesManager as SLM;

// phpcs:disable PSR1.Files.SideEffects
if (!defined('_PS_VERSION_')) {
    exit;
}
// phpcs:enable PSR1.Files.SideEffects

/**
 * Manage access to Customers Groups
 */
class CustomerGroupsManager
{
    /**
     * Get All Groups Names
     *
     * @return array<string, string>
     */
    public static function getAllGroupNames(): array
    {
        static $groups = array();

        if (!isset($groups)) {
            foreach (Group::getGroups(SLM::getDefaultLangId()) as $definition) {
                $name = $definition['name'] ?? $definition['id_group'];
                $groups[strtolower($name)] = $name;
            }
        }

        return $groups;
    }

    /**
     * Get Customer's Groups Names
     *
     * @return string[]
     */
    public static function getGroupNames(\Customer $customer): array
    {
        $groupNames = array();
        foreach ($customer->getGroups() as $groupId) {
            $group = new Group($groupId, SLM::getDefaultLangId());
            if ($group->id && $group->name) {
                $groupNames[] = $group->name;
            }
        }

        return $groupNames;
    }
}
