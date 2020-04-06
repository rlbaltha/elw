<?php
// src/AppBundle/Security/User/CasProvider.php
namespace App\Service;

use PRayno\CasAuthBundle\Security\User\CasUserProvider;
use PRayno\CasAuthBundle\Security\User\CasUser;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;

class ElwUserProvider extends CasUserProvider implements UserProviderInterface
{
    /**
     * Provides the authenticated user a ROLE_USER
     * @param $username
     * @return CasUser
     * @throws UsernameNotFoundException
     */
    public function loadUserByUsername($username)
    {
        if ($username) {
            $password = '...';
            $salt = "";
            $roles = ["ROLE_USER"];

            return new CasUser($username, $password, $salt, $roles);
        }

        throw new UsernameNotFoundException(
            sprintf('Username "%s" does not exist.', $username)
        );
    }

    /**
     * @param UserInterface $user
     * @return CasUser
     * @throws UnsupportedUserException
     * @throws UsernameNotFoundException
     */
    public function refreshUser(UserInterface $user)
    {
        if (!$user instanceof CasUser) {
            throw new UnsupportedUserException(
                sprintf('Instances of "%s" are not supported.', get_class($user))
            );
        }

        return $this->loadUserByUsername($user->getUsername());
    }

    /**
     * @param $class
     * @return bool
     */
    public function supportsClass($class)
    {
        return $class === 'PRayno\CasAuthBundle\Security\User\CasUser';
    }
}