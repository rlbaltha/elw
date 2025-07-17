<?php

namespace App\Repository;

use App\Entity\Course;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use FontLib\Table\Type\name;

/**
 * @method Course|null find($id, $lockMode = null, $lockVersion = null)
 * @method Course|null findOneBy(array $criteria, array $orderBy = null)
 * @method Course[]    findAll()
 * @method Course[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CourseRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Course::class);
    }

    // /**
    //  * @return Course[] Returns an array of Course objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('c.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /**
     * @return Course[] Returns an array of Course objects
     */
    public function findCourses($name, $user)
    {
        $namesearch = '%' . $name . '%';
        return $this->createQueryBuilder('c')
            ->join('c.classlists', 'cl')
            ->join('c.term', 'ct')
            ->andWhere('cl.user = :val1')
            ->setParameter('val1', $user)
            ->andWhere('c.name LIKE :val2 or ct.semester LIKE :val2 or ct.year LIKE :val2')
            ->setParameter('val2', $namesearch)
            ->addOrderBy('c.id', 'DESC')
            ->getQuery()
            ->getResult()
            ;
    }

    /**
     * @return Course[] Returns an array of Course objects
     */
    public function findAdminCourses($name)
    {
        $namesearch = '%' . $name . '%';
        $role = 'instructor';
        return $this->createQueryBuilder('c')
            ->join('c.term', 'ct')
            ->join('c.classlists', 'cl')
            ->join('cl.user', 'cu')
            ->andWhere('c.name LIKE :val or ct.semester LIKE :val or ct.year LIKE :val or (cl.role = :val2 and cu.lastname LIKE :val)')
            ->setParameter('val', $namesearch)
            ->setParameter('val2', $role)
            ->addOrderBy('c.id', 'DESC')
            ->getQuery()
            ->getResult()
            ;
    }

    /**
    * @return Course[] Returns an array of Course objects
    */
    public function findByUser($user)
    {
        return $this->createQueryBuilder('c')
            ->join('c.classlists', 'cl')
            ->andWhere('cl.user = :val')
            ->setParameter('val', $user)
            ->getQuery()
            ->getResult()
            ;
    }

    /**
     * @return Course[] Returns an array of Course objects
     */
    public function findByUserAndTerm($user, $status)
    {
        if ($status == 'default') {
            return $this->createQueryBuilder('c')
                ->join('c.classlists', 'cl')
                ->join('c.term', 't')
                ->andWhere('cl.user = :val1')
                ->andWhere('t.status = :val2')
                ->setParameter('val1', $user)
                ->setParameter('val2', 'default')
                ->getQuery()
                ->getResult();
        }
        else {
            return $this->createQueryBuilder('c')
                ->join('c.classlists', 'cl')
                ->join('c.term', 't')
                ->andWhere('cl.user = :val1')
                ->andWhere('t.status != :val2')
                ->setParameter('val1', $user)
                ->setParameter('val2', 'default')
                ->getQuery()
                ->getResult();
        }

    }


//    deprecated, replaced by default find
    public function findOneByCourseid($courseid): ?Course
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.id = :val')
            ->setParameter('val', $courseid)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    public function findOneByLtiId($lti_id): ?Course
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.lti_id = :val')
            ->setParameter('val', $lti_id)
            ->getQuery()
            ->getOneOrNullResult()
            ;
    }

    public function countByTerm()
    {
        return $this->createQueryBuilder('c')
            ->join('c.term', 't')
            ->select('t.id, t.year, t.semester, count(t.id) as termcount')
            ->groupBy('t.id')
            ->orderBy('t.id', 'DESC')
            ->getQuery()
            ->getResult();


    }

    /**
     * countByCourseTypeAndTerm
     */
    public function countByCoursetype($term)
    {
        return $this->getEntityManager()
            ->createQuery("SELECT SUBSTRING(REPLACE(REPLACE(REPLACE(c.name, ' ',''), 'English','ENGL'), 'XLSAS_',''),1,8) as coursetype, COUNT(c.id) as coursecount
            FROM App\Entity\Course c JOIN App\Entity\Term t
            WHERE c.term=t.id and t.id = ?1
            GROUP BY coursetype ORDER BY coursetype ASC
            ")
            ->setParameter('1', $term)
            ->getResult();
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
