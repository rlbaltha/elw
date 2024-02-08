<?php

namespace App\Service;

use OAT\Library\Lti1p3Core\Message\Payload\MessagePayloadInterface;
use OAT\Library\Lti1p3Core\Security\Jwt\Builder\BuilderInterface;
use OAT\Library\Lti1p3Core\Security\Jwt\TokenInterface;
use OAT\Library\Lti1p3Core\Security\Key\KeyInterface;

/**
 * Decorates the LTI Core 6.0 Builder to modify the "aud" claim.
 */
class LtiModifiedBuilder implements BuilderInterface
{
    public function __construct(private BuilderInterface $builder)
    {
    }

    /**
     * In LTI Core 6.0, the "aud" claim is an array of strings, but the platform
     * expects a single string. This method takes the first element of the array
     * and uses it as the value of the "aud" claim.
     */
    public function build(array $headers, array $claims, KeyInterface $key): TokenInterface
    {
        $claims[MessagePayloadInterface::CLAIM_AUD] = $claims[MessagePayloadInterface::CLAIM_AUD][0];

        return $this->builder->build($headers, $claims, $key);
    }
}