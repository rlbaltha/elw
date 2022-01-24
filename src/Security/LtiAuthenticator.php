<?php


namespace App\Security;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;


class LtiAuthenticator extends AbstractAuthenticator
{

    public function supports(Request $request): ?bool
    {

        return false;

    }

    public function authenticate(Request $request): Passport
    {
        $username = $request->request->get('username', '');


        $passport = new SelfValidatingPassport(new UserBadge($username), []);

        return $passport;
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        // on success, let the request continue
        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        return null;
    }


}




//namespace App\Security;
//
//use App\Entity\User;
//use Doctrine\ORM\EntityManagerInterface;
//use Symfony\Component\HttpFoundation\RedirectResponse;
//use Symfony\Component\HttpFoundation\Request;
//use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
//use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
//use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
//use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
//use Symfony\Component\Security\Core\Exception\InvalidCsrfTokenException;
//use Symfony\Component\Security\Core\Security;
//use Symfony\Component\Security\Core\User\UserInterface;
//use Symfony\Component\Security\Core\User\UserProviderInterface;
//use Symfony\Component\Security\Csrf\CsrfToken;
//use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
//use Symfony\Component\Security\Guard\Authenticator\AbstractFormLoginAuthenticator;
//use Symfony\Component\Security\Guard\PasswordAuthenticatedInterface;
//use Symfony\Component\Security\Http\Util\TargetPathTrait;
//
//class LtiAuthenticator extends AbstractFormLoginAuthenticator implements PasswordAuthenticatedInterface
//{
//    use TargetPathTrait;
//
//    private $entityManager;
//    private $urlGenerator;
//    private $csrfTokenManager;
//    private $passwordEncoder;
//    private $security;
//
//    public function __construct(EntityManagerInterface $entityManager, UrlGeneratorInterface $urlGenerator, CsrfTokenManagerInterface $csrfTokenManager, UserPasswordEncoderInterface $passwordEncoder, Security $security)
//    {
//        $this->entityManager = $entityManager;
//        $this->urlGenerator = $urlGenerator;
//        $this->csrfTokenManager = $csrfTokenManager;
//        $this->passwordEncoder = $passwordEncoder;
//        $this->security = $security;
//    }
//
//    public function supports(Request $request)
//    {
////        $token = $this->security->getToken();
////        if ($token instanceof LtiMessageToken) {
////        return false;
////        }
//        return false;
//    }
//
//    public function getCredentials(Request $request)
//    {
//        return $this->security->getToken();;
//    }
//
//    public function getUser($credentials, UserProviderInterface $userProvider)
//    {
//
//        $token = new CsrfToken('authenticate', $credentials['csrf_token']);
//        if (!$this->csrfTokenManager->isTokenValid($token)) {
//            throw new InvalidCsrfTokenException();
//        }
//
//        $user = $this->entityManager->getRepository(User::class)->findOneBy(['username' => $credentials['username']]);
//
//        if (!$user) {
//            // fail authentication with a custom error
//            throw new CustomUserMessageAuthenticationException('Username could not be found.');
//        }
//
//        return $user;
//    }
//
//    public function checkCredentials($credentials, UserInterface $user)
//    {
////        return $this->passwordEncoder->isPasswordValid($user, $credentials['password']);
//        return true;
//    }
//
//    /**
//     * Used to upgrade (rehash) the user's password automatically over time.
//     */
//    public function getPassword($credentials): ?string
//    {
//        return $credentials['password'];
//    }
//
//    public function onAuthenticationSuccess(Request $request, TokenInterface $token, $providerKey)
//    {
//        if ($targetPath = $this->getTargetPath($request->getSession(), $providerKey)) {
//            return new RedirectResponse($targetPath);
//        }
//
//        return new RedirectResponse($this->urlGenerator->generate('course_index'));
//    }
//
//    protected function getLoginUrl()
//    {
//        return $this->urlGenerator->generate('app_login');
//    }
//}
