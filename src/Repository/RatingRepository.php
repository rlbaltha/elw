<?php

namespace App\Repository;

use App\Entity\Rating;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Rating|null find($id, $lockMode = null, $lockVersion = null)
 * @method Rating|null findOneBy(array $criteria, array $orderBy = null)
 * @method Rating[]    findAll()
 * @method Rating[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class RatingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Rating::class);
    }

    /**
    * @return Rating[] Returns an array of Rating objects
    */
    public function findAjax($docid, $rubricid)
    {
        return $this->createQueryBuilder('r')
            ->join('r.doc','d')
            ->join('r.rubric','ru')
            ->andWhere('d.id = :val1')
            ->andWhere('ru.id = :val2')
            ->setParameter('val1', $docid)
            ->setParameter('val2', $rubricid)
            ->orderBy('r.id', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }


    public function countRatingsByRubricByTerm($termid, $rubricid)
    {
        return $this->createQueryBuilder('r')
            ->leftJoin('r.rubric', 'ru')
            ->leftJoin('r.doc', 'd')
            ->leftJoin('d.course', 'cr')
            ->leftJoin('cr.term', 't')
            ->andWhere('t.id = :termid')
            ->andWhere('ru.id = :rubricid')
            ->select('r.scale,  count(r.scale) as ratingscount')
            ->groupBy('r.scale')
            ->setParameter('termid', $termid)
            ->setParameter('rubricid', $rubricid)
            ->orderBy('r.scale', 'DESC')
            ->getQuery()
            ->getResult()
            ;
    }
}
