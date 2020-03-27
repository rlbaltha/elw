<?php

namespace Elw\LTIBundle\Util;

use Symfony\Component\HttpFoundation\Session\SessionInterface;
use IMSGlobal\LTI\Cache as LTICache;

/**
 * Class Cache
 */
class Cache extends LTICache {
    private $session = null;

    /**
     * Cache constructor.
     * @param SessionInterface $session
     */
    public function __construct(SessionInterface $session)
    {
        $this->session = $session;
    }

    /**
     * @param $key
     * @return mixed
     */
    public function get_launch_data($key) {
        return $this->session->get($key, false);
    }

    /**
     * @param $key
     * @param $jwt_body
     * @return $this|LTICache
     */
    public function cache_launch_data($key, $jwt_body) {
        $this->session->set($key, $jwt_body);
        return $this;
    }

    /**
     * @param $nonce
     * @return $this|LTICache
     */
    public function cache_nonce($nonce) {
        $this->session->set('nonce_'.$nonce, true);
        return $this;
    }

    /**
     * @param $nonce
     * @return bool
     */
    public function check_nonce($nonce) {
        if ($this->session->get('nonce_'.$nonce, null) === null) {
            return false;
        }

        $this->session->set('nonce_'.$nonce, null);

        return true;
    }
}

