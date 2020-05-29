<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method User|null find($id, $lockMode = null, $lockVersion = null)
 * @method User|null findOneBy(array $criteria, array $orderBy = null)
 * @method User[]    findAll()
 * @method User[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }


    /**
     * @return User[] Returns an array of User objects
     */
    public function findUsers()
    {
        return $this->createQueryBuilder('u')
            ->orderBy('u.lastname', 'DESC')
            ->orderBy('u.firstname', 'DESC')
            ->getQuery()
            ->getResult()
            ;
    }


    /**
     * @return User[] Returns an array of User objects
     */
    public function findByLastname($name)
    {
        $namesearch = '%' . $name . '%';
        return $this->createQueryBuilder('u')
            ->andWhere('u.lastname LIKE :val')
            ->setParameter('val', $namesearch)
            ->orderBy('u.lastname', 'ASC')
            ->orderBy('u.firstname', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }



    public function findOneByUsername($value): ?User
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.username = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findOneById($value): ?User
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.id = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult();
    }

}
