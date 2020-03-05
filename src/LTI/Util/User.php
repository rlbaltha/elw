<?php

namespace App\LTI\Util;


use Symfony\Component\HttpFoundation\Session\Session;
use App\LTI\Model\LTIUser;
use IMSGlobal\LTI;
use IMSGlobal\LTI\LTI_Exception;

/**
 * Class User
 */
class User {
    const ROLE_LTI_LEARNER = 'ROLE_LTI_LEARNER';
    const ROLE_LTI_INSTRUCTOR = 'ROLE_LTI_INSTRUCTOR';
    const ROLE_LTI_ADMINISTRATOR = 'ROLE_LTI_ADMINISTRATOR';

    /**
     * This function return LTIUser from launcher LTI 1.3 data/request
     * @param $data_launcher
     * @return LTIUser
     */
    public static function create_from_launcher($data_launcher){
        $lti_user = new LTIUser();

        if (isset($data_launcher['iss'])) {
            $lti_user->setIssuer($data_launcher['iss']);
        } else {
            throw new \RuntimeException("iss field is required.");
        }

        if (isset($data_launcher['sub'])) {
            $lti_user->setSub($data_launcher['sub']);
        } else {
            throw new \RuntimeException("sub field is required.");
        }

        if (isset($data_launcher['name'])) {
            $lti_user->setName($data_launcher['name']);
        }

        if (isset($data_launcher['family_name'])) {
            $lti_user->setFamilyName($data_launcher['family_name']);
        }

        if (isset($data_launcher['given_name'])) {
            $lti_user->setGivenName($data_launcher['given_name']);
        }

        if (isset($data_launcher['email'])) {
            $lti_user->setEmail($data_launcher['email']);
        }

        if (isset($data_launcher['picture'])) {
            $lti_user->setUserImage($data_launcher['picture']);
        }

        self::process_roles($lti_user, $data_launcher);

        return $lti_user;
    }

    /**
     * Process roles from launcher data
     * @param LTIUser $lti_user
     * @param $data_launcher
     */
    private function process_roles(LTIUser $lti_user, $data_launcher) {
        $roles = array();
        if (isset($data_launcher['https://purl.imsglobal.org/spec/lti/claim/roles'])) {
            foreach($data_launcher['https://purl.imsglobal.org/spec/lti/claim/roles'] as $r) {
                if (strpos(strtolower($r), 'learner') !== false) {
                    $roles[] = self::ROLE_LTI_LEARNER;
                } else if (strpos(strtolower($r), 'instructor') !== false) {
                    $roles[] = self::ROLE_LTI_INSTRUCTOR;
                } else if (strpos(strtolower($r), 'administrator') !== false) {
                    $roles[] = self::ROLE_LTI_ADMINISTRATOR;
                }

            }
        }

        $lti_user->setRoles(array_unique($roles));
    }

//    public static function send_score($score, $launch_data, Session $session, $em, $parameter_symfony_lti_class) {
//        $cache = new Cache($session);
//        $cookie = new Cookie($session);
//
//        $cache->cache_launch_data($launch_data['launch_id'], $launch_data);
//
//        $connect_class = new $parameter_symfony_lti_class($em);
//        $database = new Database($launch_data['iss'], $connect_class);
//
//        $launch = LTI\LTI_Message_Launch::from_cache($launch_data['launch_id'], $database, $cache, $cookie);
//        if (!$launch->has_ags()) {
//            throw new Exception("Don't have grades!");
//        }
//        $grades = $launch->get_ags();
//
//        $score = LTI\LTI_Grade::new()
//            ->set_score_given($score)
//            ->set_score_maximum(100)
//            ->set_timestamp(date(\DateTime::ISO8601))
//            ->set_activity_progress('Completed')
//            ->set_grading_progress('FullyGraded')
//            ->set_user_id($launch->get_launch_data()['sub']);
//
//        $result = $grades->put_grade($score);
//        foreach($result['headers'] as $h) {
//            if ($h == 'HTTP/1.1 200 OK') {
//                return true;
//            }
//        }
//
//        return false;
//    }
}

