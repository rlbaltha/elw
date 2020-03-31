<?php


namespace App\Service;


use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Security;

class Permissions
{

    public function __construct(EntityManagerInterface $em, Security $security) {
        $this->em = $em;
        $this->security = $security;
    }


    /**
     *@return the role of the current user in the course
     */
    public function getCourseRole($courseid) {
        $course = $this->em->getRepository('App:Course')->findOneByCourseid($courseid);
        $username = $this->security->getUser()->getUsername();
        $user = $this->em->getRepository('App:User')->findOneByUsername($username);
        $classlist =  $this->em->getRepository('App:Classlist')->findCourseUser($course, $user);
        if(!$classlist){
            return null;
        }
        else{
            $role = $classlist->getRole();
            return $role;
        }
    }

    /*
     * checks if the current user is in the array of allowed roles
     * if not, throws an access denied exception
     * if so, returns true
     */
    public function restrictAccessTo($courseid, $allowed){
        //grab our current user's role in our current course
        $currentUserRole = $this->getCourseRole($courseid);
        //test if user role is not in the array of allowed roles
        if(!in_array($currentUserRole, $allowed)){
            throw new AccessDeniedException();
        } else {
            return true;
        }
    }
}