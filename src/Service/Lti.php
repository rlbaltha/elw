<?php
namespace App\Service;

use OAT\Library\Lti1p3Core\Exception\LtiException;
use OAT\Library\Lti1p3Core\Service\Client\LtiServiceClientInterface;
use OAT\Bundle\Lti1p3Bundle\Repository\RegistrationRepository;
use Symfony\Component\HttpFoundation\RequestStack;
use Psr\Http\Message\ResponseInterface;

// Service wrapper to inject the appropriate registration
class Lti
{
    private LtiServiceClientInterface $serviceClient;
    private RegistrationRepository $registrationRepository;
    private RequestStack $requestStack;

    public function __construct(LtiServiceClientInterface $serviceClient, RegistrationRepository $registrationRepository, RequestStack $requestStack)
    {
        $this->serviceClient = $serviceClient;
        $this->registrationRepository = $registrationRepository;
        $this->requestStack = $requestStack;
    }

    public function request(
        string $method,
        string $uri,
        array $options = [],
        array $scopes = []
    ): ResponseInterface {
        $request = $this->requestStack->getCurrentRequest();
        $session = $request->getSession();

        $registration = $this->registrationRepository->find($session->get('lti_registration_access'));

        return $this->serviceClient->request($registration, $method, $uri, $options, $scopes);
    }

    //for getting Lti results in docs and journal used in LtiController ags_score_new
    public function getLtiResult(String $uri) {
        $method = 'GET';
        $scopes = ['https://purl.imsglobal.org/spec/lti-ags/scope/result.readonly'];
        $accept_header = 'application/vnd.ims.lis.v2.resultcontainer+json';
        $options = [
            'headers' => ['Accept' => $accept_header]
        ];
        try {
            $response = $this->request($method, $uri, $options, $scopes);
            $data = json_decode($response->getBody()->__toString(), true);
        } catch (LtiException $e) {
            $data = 'Grade Column was not found.';
        }
        return $data;
    }
}