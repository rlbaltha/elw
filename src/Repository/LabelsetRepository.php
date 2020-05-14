<?php

namespace App\Repository;

use App\Entity\Labelset;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

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
    public function findByUser($user)
    {
        return $this->createQueryBuilder('l')
            ->andWhere('l.user = :val')
            ->setParameter('val', $user)
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
