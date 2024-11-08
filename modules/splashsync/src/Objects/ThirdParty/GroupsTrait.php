<?php

namespace Splash\Local\Objects\ThirdParty;

use Splash\Local\Services\CustomerGroupsManager as GroupsManager;
use Splash\Models\Helpers\InlineHelper;

trait GroupsTrait
{
    /**
     * Build Fields using FieldFactory
     */
    protected function buildGroupsFields(): void
    {
        //====================================================================//
        // Firstname
        $this->fieldsFactory()->create(SPL_T_INLINE)
            ->identifier("groups")
            ->name("Groups")
            ->group("Meta")
            ->microData("http://schema.org/Organization", "category")
            ->addChoices(GroupsManager::getAllGroupNames())
            ->isReadOnly()
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
            default:
                return;
        }
        unset($this->in[$key]);
    }
}
