<?php

namespace Elw\LTIBundle\Util;

use Symfony\Component\HttpFoundation\Session\Session;
use IMSGlobal\LTI\Cookie as LTICookie;

/**
 * Class Cookie
 */
class Cookie extends LTICookie{
    private $session = null;

    /**
     * Cookie constructor.
     * @param Session $session
     */
    public function __construct(Session $session)
    {
        $this->session = $session;
    }

    /**
     * @param $name
     * @return bool|mixed
     */
    public function get_cookie($name) {
        return $this->session->get($name, false);
    }

    /**
     * @param $name
     * @param $value
     * @param int $exp
     * @return $this|LTICookie
     */
    public function set_cookie($name, $value, $exp = 3600, $options = []) {
        $this->session->set($name, $value);
        return $this;
    }
}

