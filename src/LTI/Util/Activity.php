<?php

namespace App\LTI\Util;


use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Config\Definition\Exception\Exception;
use \IMSGlobal\LTI;

/**
 * Class Activty
 */
class Activity {
    public static function get_members($launch_data, SessionInterface $session, $em, $parameter_symfony_lti_class) {
        $cache = new Cache($session);
        $cookie = new Cookie($session);

        $cache->cache_launch_data($launch_data['launch_id'], $launch_data);

        $connect_class = new $parameter_symfony_lti_class($em);
        $database = new Database($launch_data['iss'], $connect_class);

        $launch = LTI\LTI_Message_Launch::from_cache($launch_data['launch_id'], $database, $cache, $cookie);
        if (!$launch->has_nrps()) {
            throw new Exception("Don't have membership!");
        }
        $members = $launch->get_nrps()->get_members();

        return $members;
    }
}

