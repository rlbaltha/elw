<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Config\Definition\Exception\Exception;
use IMSGlobal\LTI;
use LTI\Util\Database;
use LTI\Util\LaunchType;
use LTI\Util\Cache;
use LTI\Util\Cookie;
use LTI\Util\User;


class LtiController extends AbstractController
{
    /**
     * @Route("/lti", name="lti")
     */
    public function index()
    {
        return $this->render('lti/index.html.twig', [
            'controller_name' => 'LtiController',
        ]);
    }

    /**
     * @Route("/launch", name="lti_launch", methods={"GET", "POST"})
     */
    public function launch($activity_id = null, Request $request, SessionInterface $session)
    {
        if ($request->get('error') != '') {
            die("Problem detected: [".$request->get('error')."] ".$request->get('error_description'));
        }

        try {
            $launch = LTI\LTI_Message_Launch::new($this->getDatabase($request->get('iss')), new Cache($session), new Cookie($session))
                ->validate();
        } catch (LTI\LTI_Exception $err) {
            die('LTI_Exception: '.$err->getMessage().'. Is your browser blocking third-party cookies?');
        }

        // assign launch type 'RESOURCE' by default
        $type_launch = LaunchType::RESOURCE;

        if ($launch->is_deep_link_launch()) {
            $type_launch = LaunchType::DEEP;
        }

        $data = $launch->get_launch_data();
        $data['launch_id'] = $launch->get_launch_id();

        $user = User::create_from_launcher($data);
        $connect_class = $this->getImplementedSymfonyLTIClass();

        // get custom field: activity_id
        if (isset($data['https://purl.imsglobal.org/spec/lti/claim/custom']) &&
            isset($data['https://purl.imsglobal.org/spec/lti/claim/custom']['activity_id'])) {
            $activity_id = $data['https://purl.imsglobal.org/spec/lti/claim/custom']['activity_id'];
        }

        return $connect_class->loginUser($user, $type_launch, $data, $activity_id);
    }

    /**
     * @Route("/login", name="lti_login", methods={"POST", "GET"})
     */
    public function login(Request $request, SessionInterface $session)
    {
        $url =  $this->generateUrl('lti_launch', array(), UrlGeneratorInterface::ABSOLUTE_URL);

        LTI\LTI_OIDC_Login::new($this->getDatabase($request->get('iss')), new Cache($session), new Cookie($session))
            ->do_oidc_login_redirect($url)
            ->do_redirect();
    }

    /**
     * @Route("/jwks", name="lti_jwks", methods={"GET"})
     */
    public function jwks(Request $request) {
        $router = $this->get('router');
        $issuer = $request->get('iss');
        $data = LTI\JWKS_Endpoint::from_issuer($this->getDatabase($issuer), $iss)
            ->get_public_jwks();

        $response = new JsonResponse();
        $response->setContent(json_encode($data));
        return $response;
    }

    private function getDatabase($issuer) {
        $implemented_symfony_lti_class = $this->getImplementedSymfonyLTIClass();
        return new Database($issuer, $implemented_symfony_lti_class);
    }

    private function getImplementedSymfonyLTIClass() {
        $connect_class = $this->getParameter('symfony_lti_class');

        if (empty($connect_class)) {
            throw new Exception("Connect class is not defined in config packages");
        }

        return new $connect_class($this->getDoctrine()->getManager());
    }

}
