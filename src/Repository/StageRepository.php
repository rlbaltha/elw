<?php

namespace App\Repository;

use App\Entity\Stage;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Stage|null find($id, $lockMode = null, $lockVersion = null)
 * @method Stage|null findOneBy(array $criteria, array $orderBy = null)
 * @method Stage[]    findAll()
 * @method Stage[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class StageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Stage::class);
    }

    /**
     * @return Stage[] Returns an array of Stage objects
     */
    public function findStagesByCourse($courseid)
    {
        return $this->createQueryBuilder('s')
            ->join('s.labelset','l')
            ->join('l.courses','c')
            ->andWhere('c.id = :val  ')
            ->setParameter('val', $courseid)
            ->orderBy('s.name', 'ASC')
            ->getQuery()
            ->getResult()
            ;
    }

    /**
     * @return Stage Returns a Stage objects
     */
    public function findOneByName($value): ?Stage
    {
        return $this->createQueryBuilder('l')
            ->andWhere('l.name = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
