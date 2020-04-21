<?php

namespace App\Repository;

use App\Entity\Doc;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method Doc|null find($id, $lockMode = null, $lockVersion = null)
 * @method Doc|null findOneBy(array $criteria, array $orderBy = null)
 * @method Doc[]    findAll()
 * @method Doc[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class DocRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Doc::class);
    }

    /**
     * @return Doc[] Returns an array of Doc objects
     */
    public function findMyDocs($course, $user)
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.course = :val1')
            ->andWhere('d.user = :val2')
            ->andWhere('d.access != :val3')
            ->setParameter('val1', $course)
            ->setParameter('val2', $user)
            ->setParameter('val3', 'Journal')
            ->orderBy('d.updated', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Doc[] Returns an array of Doc objects
     */
    public function findJournal($course, $user)
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.course = :val1')
            ->andWhere('d.user = :val2')
            ->andWhere('d.access = :val3')
            ->setParameter('val1', $course)
            ->setParameter('val2', $user)
            ->setParameter('val3', 'Journal')
            ->orderBy('d.updated', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Doc[] Returns an array of Doc objects
     */
    public function findSharedDocs($course, $role)
    {
        if ($role === 'Instructor') {
            return $this->createQueryBuilder('d')
                ->andWhere('d.course = :val1')
                ->andWhere('d.access != :val3')
                ->setParameter('val1', $course)
                ->setParameter('val3', 'Journal')
                ->orderBy('d.updated', 'DESC')
                ->getQuery()
                ->getResult();
        } else {
            return $this->createQueryBuilder('d')
                ->andWhere('d.course = :val1')
                ->andWhere('d.access = :val2')
                ->setParameter('val1', $course)
                ->setParameter('val2', 'Shared')
                ->orderBy('d.updated', 'DESC')
                ->getQuery()
                ->getResult();
        }

    }


    /**
     * @return Doc Returns a Doc objects
     */
    public function findOneById($docid): ?Doc
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.id = :val')
            ->setParameter('val', $docid)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return Doc Returns a Doc objects
     */
    public function findLatest($user, $course): ?Doc
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.course = :val1')
            ->andWhere('d.user = :val2')
            ->andWhere('d.access = :val3')
            ->setParameter('val1', $course)
            ->setParameter('val2', $user)
            ->setParameter('val3', 'Journal')
            ->orderBy('d.updated', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

}
