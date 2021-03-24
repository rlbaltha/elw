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

    /**
     * @return Stage[] Returns an array of Stage objects
     */
    public function findByUser($user)
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.user = :val1')
            ->orWhere('s.level = :val2')
            ->orWhere('s.level = :val3')
            ->setParameter('val1', $user)
            ->setParameter('val2', 0)
            ->setParameter('val3', 2)
            ->orderBy('s.level')
            ->getQuery()
            ->getResult()
            ;
    }
}
