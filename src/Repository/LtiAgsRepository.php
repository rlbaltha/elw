<?php

namespace App\Repository;

use App\Entity\LtiAgs;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method LtiAgs|null find($id, $lockMode = null, $lockVersion = null)
 * @method LtiAgs|null findOneBy(array $criteria, array $orderBy = null)
 * @method LtiAgs[]    findAll()
 * @method LtiAgs[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class LtiAgsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LtiAgs::class);
    }

    // /**
    //  * @return LtiAgs[] Returns an array of LtiAgs objects
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


    public function findOneByAgsid($value): ?LtiAgs
    {
        return $this->createQueryBuilder('l')
            ->andWhere('l.id = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

}
