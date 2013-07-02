<?php

namespace Claroline\CoreBundle\Manager;

use Claroline\CoreBundle\Entity\Role;
use Claroline\CoreBundle\Entity\User;
use Claroline\CoreBundle\Entity\AbstractRoleSubject;
use Claroline\CoreBundle\Entity\Workspace\AbstractWorkspace;
use Claroline\CoreBundle\Repository\RoleRepository;
use Claroline\CoreBundle\Database\Writer;
use Doctrine\Common\Collections\ArrayCollection;
use JMS\DiExtraBundle\Annotation as DI;

/**
 * @DI\Service("claroline.manager.role_manager")
 */
class RoleManager
{
    private $roleRepo;
    private $writer;
    private $roleRepo;

    /**
     * Constructor.
     *
     * @DI\InjectParams({
     *     "writer"   = @DI\Inject("claroline.database.writer"),
     *     "roleRepo" = @DI\Inject("role_repository")
     * })
     */
    public function __construct(Writer $writer, RoleRepository $roleRepo)
    {
        $this->writer = $writer;
        $this->roleRepo = $roleRepo;
    }

    public function createWorkspaceRole($name, $translationKey, AbstractWorkspace $workspace, $isReadOnly = false)
    {
        $role = new Role();
        $role->setName($name);
        $role->setTranslationKey($translationKey);
        $role->setReadOnly($isReadOnly);
        $role->setType(Role::WS_ROLE);
        $role->setWorkspace($workspace);

        $this->writer->create($role);

        return $role;
    }

    public function createBaseRole($name, $translationKey, $isReadOnly = true)
    {
        $role = new Role();
        $role->setName($name);
        $role->setTranslationKey($translationKey);
        $role->setReadOnly($isReadOnly);
        $role->setType(Role::BASE_ROLE);

        $this->writer->create($role);

        return $role;
    }

    public function createCustomRole($name, $translationKey, $isReadOnly = false)
    {
        $role = new Role();
        $role->setName($name);
        $role->setTranslationKey($translationKey);
        $role->setReadOnly($isReadOnly);
        $role->setType(Role::CUSTOM_ROLE);

        $this->writer->create($role);

        return $role;
    }

    public function setRoleToRoleSubject(AbstractRoleSubject $ars, $roleName)
    {
        $role = $this->roleRepo->findOneBy(array('name' => $roleName));

        if (!is_null($role)) {
            $ars->addRole($role);
            $this->writer->update($ars);
        }
    }

    public function associateRole(AbstractRoleSubject $ars, Role $role)
    {
        $ars->addRole($role);

        $this->writer->update($ars);
    }

    public function dissociateRole(AbstractRoleSubject $ars, Role $role)
    {
        $ars->removeRole($role);

        $this->writer->update($ars);
    }

    public function associateRoles(AbstractRoleSubject $ars, ArrayCollection $roles)
    {
        foreach ($roles as $role) {
            $ars->addRole($role);
        }
        $this->writer->update($ars);
    }

    public function resetRoles(User $user)
    {
        $userRole = $this->roleRepo->findOneByName('ROLE_USER');
        $roles = $this->roleRepo->findPlatformRoles($user);

        foreach ($roles as $role) {
            if ($role !== $userRole) {
                $user->removeRole($role);
            }
        }
        $this->writer->update($user);
    }

    public function initWorkspaceBaseRole(array $roles, AbstractWorkspace $workspace)
    {
        $entityRoles = array();

        foreach ($roles as $name => $translation) {
            $role = $this->createWorkspaceRole(
                "{$name}_{$workspace->getId()}",
                $translation,
                $workspace,
                false
            );
            $entityRoles[$name] = $role;
        }

        return $entityRoles;
    }

    public function findWorkspaceRoles(AbstractWorkspace $workspace)
    {
        return array_merge(
            $this->roleRepo->findByWorkspace($workspace),
            $this->roleRepo->findBy(array('name' => 'ROLE_ANONYMOUS'))
        );
    }
}