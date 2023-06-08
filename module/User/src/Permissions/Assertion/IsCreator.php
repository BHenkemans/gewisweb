<?php

declare(strict_types=1);

namespace User\Permissions\Assertion;

use Decision\Model\Member;
use Laminas\Permissions\Acl\Acl;
use Laminas\Permissions\Acl\Assertion\AssertionInterface;
use Laminas\Permissions\Acl\Resource\ResourceInterface;
use Laminas\Permissions\Acl\Role\RoleInterface;
use User\Model\User;
use User\Permissions\Resource\CreatorResourceInterface;

/**
 * Assertion to check if the user has created some entity.
 */
class IsCreator implements AssertionInterface
{
    /**
     * Returns true if and only if the assertion conditions are met.
     *
     * This method is passed the ACL, Role, Resource, and privilege to which the authorization query applies. If the
     * $role, $resource, or $privilege parameters are null, it means that the query applies to all Roles, Resources, or
     * privileges, respectively.
     *
     * @param string|null $privilege
     */
    public function assert(
        Acl $acl,
        ?RoleInterface $role = null,
        ?ResourceInterface $resource = null,
        $privilege = null,
    ): bool {
        if (
            !$role instanceof User
            || !$resource instanceof CreatorResourceInterface
        ) {
            return false;
        }

        $creator = $resource->getResourceCreator();

        if (!$creator instanceof Member) {
            return false;
        }

        return $role->getLidnr() === $creator->getLidnr();
    }
}
