<?php

namespace App\Repository;

use App\Entity\Rubric;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Rubric|null find($id, $lockMode = null, $lockVersion = null)
 * @method Rubric|null findOneBy(array $criteria, array $orderBy = null)
 * @method Rubric[]    findAll()
 * @method Rubric[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class RubricRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Rubric::class);
    }

    /**
     * @return Rubric[] Returns an array of Rubric objects
     */
    public function findByUser($user)
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.user = :val1 and r.level != :val4')
            ->orWhere('r.level = :val2')
            ->orWhere('r.level = :val3')
            ->setParameter('val1', $user)
            ->setParameter('val2', 0)
            ->setParameter('val3', 2)
            ->setParameter('val4', 3)
            ->orderBy('r.level')
            ->getQuery()
            ->getResult()
            ;
    }

    /**
     * @return Rubric[] Returns an array of Rubric objects
     */
    public function findDefaults()
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.level = :val1')
            ->setParameter('val1', 0)
            ->getQuery()
            ->getResult()
            ;
    }

    public function countRubricsByTerm($termid)
    {
        return $this->createQueryBuilder('r')
            ->join('r.projects', 'p')
            ->join('p.course', 'cr')
            ->join('cr.term', 't')
            ->andWhere('t.id = :termid')
            ->select('r.id, r.name,  count(r.id) as rubriccount')
            ->groupBy('r.id')
            ->setParameter('termid', $termid)
            ->orderBy('r.name', 'DESC')
            ->getQuery()
            ->getResult()
            ;
    }
}
