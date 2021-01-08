<?php

namespace App\Repository;

use App\Entity\Markupset;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

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
    public function findDefault()
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.level = :val')
            ->setParameter('val', 0)
            ->getQuery()
            ->getResult()
            ;
    }

    /**
     * @return Markupset[] Returns an array of Markupset objects
     */
    public function findByUser($user)
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.user = :val1')
            ->orWhere('m.level = :val2')
            ->orWhere('m.level = :val3')
            ->setParameter('val1', $user)
            ->setParameter('val2', 0)
            ->setParameter('val3', 2)
            ->orderBy('m.level')
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
