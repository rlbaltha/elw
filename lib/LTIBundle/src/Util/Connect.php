<?php


namespace Elw\LTIBundle\Util;


use Elw\LTIBundle\Model\LTIUser;

class Connect extends LTIConnectAbstract
{
    /**
     * Get data issuer from issuer (iss LTI1.3 parameter)
     * @param $issuer
     * @return mixed
     */
    public function getDataIssuer($issuer) {
        $data = [
            "issuer" => "https://ugatest2.view.usg.edu",
            "auth_login_url"=> "https://ugatest2.view.usg.edu/d2l/lti/authenticate",
            "auth_token_url" => "https://auth.brightspace.com/core/connect/token",
            "key_set_url"=> "https://ugatest2.view.usg.edu/d2l/.well-known/jwks",
            "client_id"=> "90fd07d4-0a1d-449d-82e6-2fb566eabf33",
            "kid"=> "a6a9101066ac9cd65dd1f4975abff764cbcb7ca3",
            "auth_server"=> "https://ugatest2.view.usg.edu",
            "private_key"=> "/../SSL/private.key"
        ];
        return $data;
    }

    /**
     * Login user in system.
     * @param LTIUser $user
     * @param $launch_type
     * @param $launch
     * @param null $activity_id
     * @return mixed
     */
    public function loginUser(LTIUser $user, $launch_type, $launch, $activity_id = null) {

    }

}