<?php

namespace Elw\LTIBundle\Util;

use Elw\LTIBundle\Util\LTIConnectAbstract;
use \IMSGlobal\LTI;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Elw\LTIBundle\Exceptions\LTIException;
use Symfony\Component\HttpFoundation\Exception;

/**
 * Class Database
 * @package Elw\LTIBundle\Util
 */
class Database implements LTI\Database {
    private $auth_login_url = '';
    private $auth_token_url = '';
    private $key_set_url = '';
    private $client_id = '';
    private $kid = '';
    private $issuer = '';
    private $private_key = '';

    /**
     * Database constructor.
     * @param $iss
     * @param LTIConnectAbstract $connect
     * @throws LTIException
     */
    public function __construct($iss, LTIConnectAbstract $connect)
    {
        $connect_data = $connect->getDataIssuer($iss);

        $must_keys_in_connect_data = array(
            'auth_login_url',
            'auth_token_url',
            'key_set_url',
            'client_id',
            'kid',
            'private_key'
        );

        foreach($must_keys_in_connect_data as $key) {
            if(!isset($connect_data[$key]) || empty($connect_data[$key])) {
                throw new LTIException("Connect data needs this field and can not be empty ".$key);
            }
        }

        $this->auth_login_url = $connect_data['auth_login_url'];
        $this->auth_token_url = $connect_data['auth_token_url'];
        $this->key_set_url = $connect_data['key_set_url'];
        $this->client_id = $connect_data['client_id'];
        $this->kid = $connect_data['kid'];
        $this->private_key = $connect_data['private_key'];
        $this->issuer = $iss;
    }

    /**
     * @param $iss
     * @return LTI\LTI_Registration
     */
    public function find_registration_by_issuer($iss) {
        return LTI\LTI_Registration::new()
            ->set_auth_login_url($this->auth_login_url)
            ->set_auth_token_url($this->auth_token_url)
            ->set_client_id($this->client_id)
            ->set_key_set_url($this->key_set_url)
            ->set_kid($this->kid)
            ->set_issuer($this->issuer)
            ->set_tool_private_key($this->private_key);
    }

    /**
     * @param $iss
     * @param $deployment_id
     * @return LTI\LTI_Deployment
     */
    public function find_deployment($iss, $deployment_id) {
        return LTI\LTI_Deployment::new()
            ->set_deployment_id($deployment_id);
    }

}


