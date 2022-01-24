<?php


namespace App\Twig;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class CourseExtension extends AbstractExtension

{

    private $router;

    public function __construct(RequestStack $requestStack, UrlGeneratorInterface $router) {
        $this->requestStack = $requestStack;
        $this->router = $router;
    }

    public function getName() {
        return 'course';
    }

    public function getFunctions()
    {
        return array(
            new TwigFunction('course_path', array($this, 'course_path'))
        );
    }

    public function course_path(String $route, array $params=array(), $absolute=false) {

        $request = $this->requestStack->getCurrentRequest();
        $courseid = $request->attributes->get('courseid');
        if (!$courseid) {
            throw new \Exception('No course id in url.');
        }
        $params['courseid'] = $courseid;
        return $this->router->generate($route, $params, $absolute);
    }

}