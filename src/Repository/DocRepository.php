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
            ->setParameter('val1', $course)
            ->setParameter('val2', $user)
            ->orderBy('d.updated', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Doc[] Returns an array of Doc objects
     */
    public function findDocsByLabel($course, $label)
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.course = :val1')
            ->andWhere(':val2 MEMBER OF d.labels')
            ->setParameter('val1', $course)
            ->setParameter('val2', $label)
            ->orderBy('d.updated', 'ASC')
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

}
