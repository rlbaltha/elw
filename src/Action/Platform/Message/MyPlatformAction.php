<?php

declare(strict_types=1);

namespace App\Action\Platform\Message;

use OAT\Library\Lti1p3Core\Launch\Builder\OidcLaunchRequestBuilder;
use OAT\Library\Lti1p3Core\Link\ResourceLink\ResourceLink;
use OAT\Library\Lti1p3Core\Registration\RegistrationRepositoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class MyPlatformAction
{
    /** @var OidcLaunchRequestBuilder */
    private $builder;

    /** @var RegistrationRepositoryInterface */
    private $repository;

    public function __construct(OidcLaunchRequestBuilder $builder, RegistrationRepositoryInterface $repository)
    {
        $this->builder = $builder;
        $this->repository = $repository;
    }

    public function __invoke(Request $request): Response
    {
        $oidcLtiLaunchRequest = $this->builder->buildResourceLinkOidcLaunchRequest(
            new ResourceLink('identifier'),
            $this->repository->find('local'),
            'loginHint'
        );

        return new Response($oidcLtiLaunchRequest->toHtmlLink('click me'));
    }
}