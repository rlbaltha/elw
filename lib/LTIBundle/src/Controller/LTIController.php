<?php

namespace Elw\LTIBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use IMSGlobal\LTI;
use Elw\LTIBundle\Util\Database;
use Elw\LTIBundle\Util\LaunchType;
use Elw\LTIBundle\Util\Cache;
use Elw\LTIBundle\Util\Cookie;
use Elw\LTIBundle\Util\User;
use Elw\LTIBundle\Exceptions\LTIException;



class LTIController extends AbstractController
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
     * @Route("/token", name="lti_token", methods={"GET"})
     */
    public function token()
    {
        die('LTI token');
    }


    /**
     * @Route("/launch", name="lti_launch", methods={"GET", "POST"}, defaults={"activity_id": ""})
     */
    public function launch($activity_id, Request $request, SessionInterface $session)
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
        $connect_class = $this->getImplementedLTIClass();

        // get custom field: activity_id
        if (isset($data['https://purl.imsglobal.org/spec/lti/claim/custom']) &&
            isset($data['https://purl.imsglobal.org/spec/lti/claim/custom']['activity_id'])) {
            $activity_id = $data['https://purl.imsglobal.org/spec/lti/claim/custom']['activity_id'];
        }

        return $connect_class->loginUser($user, $type_launch, $data, $activity_id);

    }

    /**
     * @Route("/lti_login", name="lti_login", methods={"POST", "GET"})
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
//        $implemented_lti_class = $this->getImplementedLTIClass();
//        return new Database($issuer, $implemented_lti_class);
        return new Database($issuer);
    }


    private function getImplementedLTIClass() {
        $connect_class = $this->getParameter('lti_class');

        if (empty($connect_class)) {
            throw new LTIException("Connect class is not defined in config packages");
        }

        return new $connect_class($this->getDoctrine()->getManager());
    }

}
