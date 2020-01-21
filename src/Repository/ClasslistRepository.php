<?php

namespace App\Repository;

use App\Entity\Classlist;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method Classlist|null find($id, $lockMode = null, $lockVersion = null)
 * @method Classlist|null findOneBy(array $criteria, array $orderBy = null)
 * @method Classlist[]    findAll()
 * @method Classlist[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ClasslistRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Classlist::class);
    }

    // /**
    //  * @return Classlist[] Returns an array of Classlist objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('c.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Classlist
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
