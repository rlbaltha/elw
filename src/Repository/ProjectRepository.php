<?php

namespace App\Repository;

use App\Entity\Project;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Project|null find($id, $lockMode = null, $lockVersion = null)
 * @method Project|null findOneBy(array $criteria, array $orderBy = null)
 * @method Project[]    findAll()
 * @method Project[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ProjectRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Project::class);
    }

    /**
    * @return Project[] Returns an array of Project objects
    */
    public function findProjectsByCourse($courseid)
    {
        return $this->createQueryBuilder('p')
            ->join('p.labelset','l')
            ->join('l.courses','c')
            ->andWhere('c.id = :val  ')
            ->setParameter('val', $courseid)
            ->orderBy('p.name', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }


    /**
     * @return Project Returns a Project objects
     */
    public function findOneByName($value): ?Project
    {
        return $this->createQueryBuilder('l')
            ->andWhere('l.name = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
