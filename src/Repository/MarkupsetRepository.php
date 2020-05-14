<?php

namespace App\Repository;

use App\Entity\Markupset;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method Markupset|null find($id, $lockMode = null, $lockVersion = null)
 * @method Markupset|null findOneBy(array $criteria, array $orderBy = null)
 * @method Markupset[]    findAll()
 * @method Markupset[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MarkupsetRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Markupset::class);
    }

    // /**
    //  * @return Markupset[] Returns an array of Markupset objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('m.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /**
     * @return Markupset[] Returns an array of Markupset objects
     */
    public function findByUser($user)
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.user = :val')
            ->setParameter('val', $user)
            ->getQuery()
            ->getResult()
            ;
    }

    /*
    public function findOneBySomeField($value): ?Markupset
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
