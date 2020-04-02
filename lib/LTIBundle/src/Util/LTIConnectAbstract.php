<?php

namespace Elw\LTIBundle\Util;

use Doctrine\ORM\EntityManager;
use Elw\LTIBundle\Model\LTIUser;

/**
 * Class LTIConnectAbstract
 */
abstract class LTIConnectAbstract {
//    protected $em;
//
//    /**
//     * LTIConnectAbstract constructor.
//     * @param EntityManager $entityManager
//     */
//    public function __construct(EntityManager $entityManager)
//    {
//        $this->em = $entityManager;
//    }

    /**
     * Get data issuer from issuer (iss LTI1.3 parameter)
     * @param $issuer
     * @return mixed
     */
    abstract public function getDataIssuer($issuer);

    /**
     * Login user in system.
     * @param LTIUser $user
     * @param $launch_type
     * @param $launch
     * @param null $activity_id
     * @return mixed
     */
    abstract public function loginUser(LTIUser $user, $launch_type, $launch, $activity_id = null);
}

