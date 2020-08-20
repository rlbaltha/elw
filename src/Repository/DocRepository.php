<?php

namespace App\Repository;

use App\Entity\Doc;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\QueryBuilder;
use PhpParser\Node\Scalar;

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
     */
    public function findMyDocs($course, $user): QueryBuilder
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.course = :val1')
            ->andWhere('d.user = :val2')
            ->andWhere('d.access != :val3')
            ->setParameter('val1', $course)
            ->setParameter('val2', $user)
            ->setParameter('val3', 'Journal')
            ->orderBy('d.updated', 'DESC');
    }

    /**
     * @return Doc[] Returns an array of Doc objects
     */
    public function findHiddenDocs($course, $user)
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.course = :val1')
            ->andWhere('d.user = :val2')
            ->andWhere('d.access = :val3')
            ->setParameter('val1', $course)
            ->setParameter('val2', $user)
            ->setParameter('val3', 'Hidden')
            ->orderBy('d.updated', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     */
    public function findSharedDocs($course, $role): QueryBuilder
    {
        if ($role === 'Instructor') {
            return $this->createQueryBuilder('d')
                ->andWhere('d.course = :val1')
                ->andWhere('d.access != :val3')
                ->setParameter('val1', $course)
                ->setParameter('val3', 'Journal')
                ->orderBy('d.updated', 'DESC');
        } else {
            return $this->createQueryBuilder('d')
                ->andWhere('d.course = :val1')
                ->andWhere('d.access = :val2')
                ->setParameter('val1', $course)
                ->setParameter('val2', 'Shared')
                ->orderBy('d.updated', 'DESC');
        }

    }

    /**
     */
    public function findByUser($course, $role, $user): QueryBuilder
    {
        if ($role === 'Instructor') {
            return $this->createQueryBuilder('d')
                ->andWhere('d.course = :val1')
                ->andWhere('d.access != :val2')
                ->andWhere('d.user = :val3')
                ->setParameter('val1', $course)
                ->setParameter('val2', 'Journal')
                ->setParameter('val3', $user)
                ->orderBy('d.updated', 'DESC');
        } else {
            return $this->createQueryBuilder('d')
                ->andWhere('d.course = :val1')
                ->andWhere('d.access = :val2')
                ->andWhere('d.user = :val3')
                ->setParameter('val1', $course)
                ->setParameter('val2', 'Shared')
                ->setParameter('val3', $user)
                ->orderBy('d.updated', 'DESC');
        }

    }

    /**
     * @return Doc[] Returns an array of Doc objects
     */
    public function findDocComments($course, $user)
    {
        return $this->createQueryBuilder('d')
            ->join('d.comments', 'c')
            ->andWhere('d.course = :val1')
            ->andWhere('d.access != :val3')
            ->andWhere('c.user = :val2')
            ->setParameter('val1', $course)
            ->setParameter('val2', $user)
            ->setParameter('val3', 'Journal')
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
     * @return count of docs with hidden comments
     */
    public function countHiddenComments($course): ?String
    {
        $dql = 'SELECT count(d.id) FROM App\Entity\Doc d JOIN d.comments c WHERE c.access = ?1 and d.course = ?2 ';
        $query = $this->getEntityManager()->createQuery($dql)->setParameter('1', 'Hidden')->setParameter('2', $course);
        return $query->getSingleScalarResult();
    }

    /**
     * @return count of docs with hidden reviews
     */
    public function countHiddenReviews($course): ?String
    {
        $dql = 'SELECT count(d.id) FROM App\Entity\Doc d WHERE d.access = ?1 and d.course = ?2 ';
        $query = $this->getEntityManager()->createQuery($dql)->setParameter('1', 'Hidden')->setParameter('2', $course);
        return $query->getSingleScalarResult();
    }


}
