<?php

namespace App\Repository;

use App\Entity\Labelset;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Labelset|null find($id, $lockMode = null, $lockVersion = null)
 * @method Labelset|null findOneBy(array $criteria, array $orderBy = null)
 * @method Labelset[]    findAll()
 * @method Labelset[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class LabelsetRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Labelset::class);
    }

    // /**
    //  * @return Labelset[] Returns an array of Labelset objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('l')
            ->andWhere('l.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('l.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /**
     * @return Labelset[] Returns an array of Labelset objects
     */
    public function findDefault()
    {
        return $this->createQueryBuilder('l')
            ->andWhere('l.level = :val')
            ->setParameter('val', 0)
            ->getQuery()
            ->getResult()
            ;
    }

    /**
     * @return Labelset[] Returns an array of Labelset objects
     */
    public function findByUser($user)
    {
        return $this->createQueryBuilder('l')
            ->andWhere('l.user = :val1')
            ->orWhere('l.level = :val2')
            ->setParameter('val1', $user)
            ->setParameter('val2', 0)
            ->getQuery()
            ->getResult()
            ;
    }

    /*
    public function findOneBySomeField($value): ?Labelset
    {
        return $this->createQueryBuilder('l')
            ->andWhere('l.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
