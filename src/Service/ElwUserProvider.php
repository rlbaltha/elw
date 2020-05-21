<?php

namespace App\Service;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use PRayno\CasAuthBundle\Security\User\CasUserProvider;
use PRayno\CasAuthBundle\Security\User\CasUser;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;

class ElwUserProvider extends CasUserProvider implements UserProviderInterface
{

    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager) {
        $this->entityManager = $entityManager;
    }

    /**
     * Provides the authenticated user a ROLE_USER
     * @param $username
     * @return User
     * @throws UsernameNotFoundException
     */
    public function loadUserByUsername($username)
    {
        if ($username) {
            $password = '...';
            $salt = "";
            $length = strlen($username);
            $init = substr($username, 0, 1);
            $user = $this->entityManager->getRepository('App:User')->findOneByUsername($username);
//          if user exits or is loginas else create new user
            if ($user || ($init=='_' && $length > 20)) {
                return $user;
            }
            $user = New User();
            $user->setUsername($username);
            $user->setRoles(["ROLE_USER"]);
            $user->setLastname('Please Update');
            $user->setFirstname('Please Update');
            $this->entityManager->persist($user);
            $this->entityManager->flush();
            return $user;

        }



    }

    /**
     * @param UserInterface $user
     * @return User
     * @throws UnsupportedUserException
     * @throws UsernameNotFoundException
     */
    public function refreshUser(UserInterface $user)
    {
        if (!$user instanceof User) {
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
        return $class === 'App\Entity\User';
    }
}