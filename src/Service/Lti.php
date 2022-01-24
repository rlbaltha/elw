<?php

namespace App\Service;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\ClientException;
use OAT\Library\Lti1p3Core\Security\Jwt\Builder\Builder;
use OAT\Library\Lti1p3Core\Security\Jwt\Builder\BuilderInterface;
use OAT\Library\Lti1p3Core\Exception\LtiException;
use OAT\Library\Lti1p3Core\Exception\LtiExceptionInterface;
use OAT\Library\Lti1p3Core\Message\Payload\MessagePayloadInterface;
use OAT\Library\Lti1p3Core\Registration\RegistrationInterface;
use OAT\Library\Lti1p3Core\Registration\RegistrationRepositoryInterface;
use OAT\Library\Lti1p3Core\Security\OAuth2\Grant\ClientAssertionCredentialsGrant;
use Psr\Cache\CacheException;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Throwable;
use RuntimeException;

class Lti
{
    private const CACHE_PREFIX = 'lti1p3-service-client-token';
    private const GRANT_TYPE = 'client_credentials';

    /** @var CacheItemPoolInterface|null */
    private $cache;

    /** @var ClientInterface */
    private $client;

    /** @var BuilderInterface */
    private $builder;

    /** @var RegistrationRepositoryInterface */
    private RegistrationRepositoryInterface $repository;

    /** @var RequestStack */
    private RequestStack $requestStack;

    public function __construct(
        ?CacheItemPoolInterface $cache = null,
        ?ClientInterface $client = null,
        ?BuilderInterface $builder = null,
        RegistrationRepositoryInterface $repository,
        RequestStack $requestStack
    )
    {
        $this->cache = $cache;
        $this->client = $client ?? new Client();
        $this->builder = $builder ?? new Builder();
        $this->repository = $repository;
        $this->requestStack = $requestStack;
    }


    public function getLtiResult(String $uri) {
        $registration = $this->requestStack->getSession()->get('lti_registration');
        $method = 'GET';
        $scopes = ['https://purl.imsglobal.org/spec/lti-ags/scope/result.readonly'];
        $accept_header = 'application/vnd.ims.lis.v2.resultcontainer+json';

        $registration = $this->repository->find($registration);
        $access_token = $this->getAccessToken($registration, $scopes);
        $options = $this->getHeaderOptions($access_token, $accept_header);

        try {
            $response = $this->client->request($method, $uri, $options);
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
    public function getAccessToken(
        RegistrationInterface $registration,
        array $scopes,
        bool $forceRefresh = false
    ): string {
        try {
            $cacheKey = sprintf(
                '%s-%s-%s',
                self::CACHE_PREFIX,
                $registration->getIdentifier(),
                sha1(implode('', $scopes))
            );

            if ($this->cache) {
                $item = $this->cache->getItem($cacheKey);

                if ($item->isHit()) {
                    if (!$forceRefresh) {
                        return $item->get();
                    }

                    $this->cache->deleteItem($cacheKey);
                }
            }

            $response = $this->client->request('POST', $registration->getPlatform()->getOAuth2AccessTokenUrl(), [
                'form_params' => [
                    'grant_type' => static::GRANT_TYPE,
                    'client_assertion_type' => ClientAssertionCredentialsGrant::CLIENT_ASSERTION_TYPE,
                    'client_assertion' => $this->generateCredentials($registration),
                    'scope' => implode(' ', $scopes)
                ]
            ]);

            if (!in_array($response->getStatusCode(), [200, 201])) {
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

            if ($this->cache) {
                $item = $this->cache->getItem($cacheKey);

                $this->cache->save(
                    $item->set($accessToken)->expiresAfter($expiresIn)
                );
            }

            return $accessToken;

        } catch (LtiExceptionInterface $exception) {
            throw $exception;
        }catch (Throwable|CacheException $exception) {
            throw new LtiException(
                sprintf('Cannot get access token: %s', $exception->getMessage()),
                $exception->getCode(),
                $exception
            );
        }
    }
//    /**
//     * @throws LtiExceptionInterface
//     */
//    public function getAccessToken(RegistrationInterface $registration, $scope): string
//    {
//        $response = $this->guzzle->request('POST', $registration->getPlatform()->getOAuth2AccessTokenUrl(), [
//            'form_params' => [
//                'grant_type' => 'client_credentials',
//                'client_assertion_type' => 'urn:ietf:params:oauth:client-assertion-type:jwt-bearer',
//                'client_assertion' => $this->generateCredentials($registration),
//                'scope' => $scope
//            ]
//        ]);
//
//        if ($response->getStatusCode() !== 200) {
//            throw new RuntimeException('invalid response http status code');
//        }
//
//        $responseData = json_decode($response->getBody()->__toString(), true);
//
//        if (JSON_ERROR_NONE !== json_last_error()) {
//            throw new RuntimeException(sprintf('json_decode error: %s', json_last_error_msg()));
//        }
//
//        $accessToken = $responseData['access_token'] ?? '';
//        $expiresIn = $responseData['expires_in'] ?? '';
//
//        if (empty($accessToken) || empty($expiresIn)) {
//            throw new RuntimeException('invalid response body');
//        }
//
//        return $accessToken;
//    }

//    /**
//     * @throws LtiExceptionInterface
//     */
//    public function generateCredentials(RegistrationInterface $registration): string
//    {
//        try {
//            if (null === $registration->getToolKeyChain()) {
//                throw new LtiException('Tool key chain is not configured');
//            }
//
//            $now = Carbon::now();
//            return $this->builder
//                ->withHeader(MessagePayloadInterface::HEADER_KID, $registration->getToolKeyChain()->getIdentifier())
//                ->identifiedBy(sprintf('%s-%s', $registration->getIdentifier(), $now->getTimestamp()))
//                ->issuedBy($registration->getClientId())
//                ->relatedTo($registration->getClientId())
//                ->permittedFor($registration->getTool()->getAudience())
//                ->issuedAt($now->getTimestamp())
//                ->expiresAt($now->addSeconds(MessagePayloadInterface::TTL)->getTimestamp())
//                ->getToken($this->signer, $registration->getToolKeyChain()->getPrivateKey())
//                ->__toString();
//
//        } catch (Throwable $exception) {
//            throw new LtiException(
//                sprintf('Cannot generate credentials: %s', $exception->getMessage()),
//                $exception->getCode(),
//                $exception
//            );
//        }
//
//    }

    /**
     * @throws LtiExceptionInterface
     */
    private function generateCredentials(RegistrationInterface $registration): string
    {
        try {
            $toolKeyChain = $registration->getToolKeyChain();

            if (null === $toolKeyChain) {
                throw new LtiException('Tool key chain is not configured');
            }

            $token = $this->builder->build(
                [
                    MessagePayloadInterface::HEADER_KID => $toolKeyChain->getIdentifier()
                ],
                [
                    MessagePayloadInterface::CLAIM_ISS => $registration->getTool()->getAudience(),
                    MessagePayloadInterface::CLAIM_SUB => $registration->getClientId(),
                    MessagePayloadInterface::CLAIM_AUD => [
                        $registration->getPlatform()->getAudience(),
                        $registration->getPlatform()->getOAuth2AccessTokenUrl(),
                    ]
                ],
                $toolKeyChain->getPrivateKey()
            );

            return $token->toString();

        } catch (Throwable $exception) {
            throw new LtiException(
                sprintf('Cannot generate credentials: %s', $exception->getMessage()),
                $exception->getCode(),
                $exception
            );
        }
    }
}