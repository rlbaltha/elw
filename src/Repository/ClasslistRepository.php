<?php

namespace App\Repository;

use App\Entity\Classlist;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\NonUniqueResultException;

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

    /**
    * @return Classlist[] Returns an array of Classlist objects
    */
    public function findByCourseid($courseid)
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.course = :val')
            ->setParameter('val', $courseid)
            ->getQuery()
            ->getResult()
        ;
    }


    public function findCourseUser($course, $user): ?Classlist
    {
        try {
            return $this->createQueryBuilder('c')
                ->andWhere('c.course = :course')
                ->andWhere('c.user = :user')
                ->setParameter('course', $course)
                ->setParameter('user', $user)
                ->getQuery()
                ->getOneOrNullResult();
        } catch (NonUniqueResultException $e) {
        }
    }

}
