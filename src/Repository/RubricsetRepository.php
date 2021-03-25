<?php

namespace App\Repository;

use App\Entity\Rubricset;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Rubricset|null find($id, $lockMode = null, $lockVersion = null)
 * @method Rubricset|null findOneBy(array $criteria, array $orderBy = null)
 * @method Rubricset[]    findAll()
 * @method Rubricset[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class RubricsetRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Rubricset::class);
    }

    /**
     * @return Rubricset[] Returns an array of Rubricset objects
     */
    public function findByUser($user)
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.user = :val1')
            ->orWhere('r.level = :val2')
            ->orWhere('r.level = :val3')
            ->setParameter('val1', $user)
            ->setParameter('val2', 0)
            ->setParameter('val3', 2)
            ->orderBy('r.level')
            ->getQuery()
            ->getResult()
            ;
    }

    // /**
    //  * @return Rubricset[] Returns an array of Rubricset objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('r.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Rubricset
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
