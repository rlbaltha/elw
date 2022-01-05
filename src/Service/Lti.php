<?php

namespace App\Service;

use Carbon\Carbon;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\ClientException;
use Lcobucci\JWT\Builder;
use Lcobucci\JWT\Signer;
use OAT\Library\Lti1p3Core\Exception\LtiException;
use OAT\Library\Lti1p3Core\Exception\LtiExceptionInterface;
use OAT\Library\Lti1p3Core\Message\Payload\MessagePayloadInterface;
use OAT\Library\Lti1p3Core\Registration\RegistrationInterface;
use OAT\Library\Lti1p3Core\Registration\RegistrationRepositoryInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Core\Security;
use Throwable;
use RuntimeException;

class Lti
{
    /** @var Security */
    private $security;

    /** @var RegistrationRepositoryInterface */
    private $repository;

    /** @var ClientInterface */
    private $guzzle;

    /** @var Builder */
    private $builder;

    /** @var Signer */
    private $signer;

    /** @var SessionInterface */
    private $session;


    public function __construct(
        Security $security,
        RegistrationRepositoryInterface $repository,
        ClientInterface $guzzle,
        Builder $builder,
        Signer $signer,
        SessionInterface $session

    )
    {
        $this->security = $security;
        $this->repository = $repository;
        $this->guzzle = $guzzle;
        $this->builder = $builder;
        $this->signer = $signer;
        $this->session = $session;
    }

    public function getLtiResult(String $uri) {
        $registration = $this->session->get('lti_registration');
        $method = 'GET';
        $scope = 'https://purl.imsglobal.org/spec/lti-ags/scope/result.readonly';
        $accept_header = 'application/vnd.ims.lis.v2.resultcontainer+json';

        $registration = $this->repository->find($registration);
        $access_token = $this->getAccessToken($registration, $scope);
        $options = $this->getHeaderOptions($access_token, $accept_header);

        try {
            $response = $this->guzzle->request($method, $uri, $options);
            $data = json_decode($response->getBody()->__toString(), true);
        } catch (ClientException $e) {
            $data = 'Grade Column was not found.';
        }
        return $data;
    }

    public function getHeaderOptions($access_token, $accept_header) {
        $options = [
            'headers' => ['Authorization' => sprintf('Bearer %s', $access_token), 'Accept' => $accept_header]
        ];
        return $options;
    }

    /**
     * @throws LtiExceptionInterface
     */
    public function getAccessToken(RegistrationInterface $registration, $scope): string
    {
        $response = $this->guzzle->request('POST', $registration->getPlatform()->getOAuth2AccessTokenUrl(), [
            'form_params' => [
                'grant_type' => 'client_credentials',
                'client_assertion_type' => 'urn:ietf:params:oauth:client-assertion-type:jwt-bearer',
                'client_assertion' => $this->generateCredentials($registration),
                'scope' => $scope
            ]
        ]);

        if ($response->getStatusCode() !== 200) {
            throw new RuntimeException('invalid response http status code');
        }

        $responseData = json_decode($response->getBody()->__toString(), true);

        if (JSON_ERROR_NONE !== json_last_error()) {
            throw new RuntimeException(sprintf('json_decode error: %s', json_last_error_msg()));
        }

        $accessToken = $responseData['access_token'] ?? '';
        $expiresIn = $responseData['expires_in'] ?? '';

        if (empty($accessToken) || empty($expiresIn)) {
            throw new RuntimeException('invalid response body');
        }

        return $accessToken;
    }

    /**
     * @throws LtiExceptionInterface
     */
    public function generateCredentials(RegistrationInterface $registration): string
    {
        try {
            if (null === $registration->getToolKeyChain()) {
                throw new LtiException('Tool key chain is not configured');
            }

            $now = Carbon::now();
            return $this->builder
                ->withHeader(MessagePayloadInterface::HEADER_KID, $registration->getToolKeyChain()->getIdentifier())
                ->identifiedBy(sprintf('%s-%s', $registration->getIdentifier(), $now->getTimestamp()))
                ->issuedBy($registration->getClientId())
                ->relatedTo($registration->getClientId())
                ->permittedFor($registration->getTool()->getAudience())
                ->issuedAt($now->getTimestamp())
                ->expiresAt($now->addSeconds(MessagePayloadInterface::TTL)->getTimestamp())
                ->getToken($this->signer, $registration->getToolKeyChain()->getPrivateKey())
                ->__toString();

        } catch (Throwable $exception) {
            throw new LtiException(
                sprintf('Cannot generate credentials: %s', $exception->getMessage()),
                $exception->getCode(),
                $exception
            );
        }

    }
}