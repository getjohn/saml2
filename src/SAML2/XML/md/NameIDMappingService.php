<?php

declare(strict_types=1);

namespace SAML2\XML\md;

use Webmozart\Assert\Assert;

/**
 * Class representing an md:NameIDMappingService element.
 *
 * @package simplesamlphp/saml2
 */
final class NameIDMappingService extends AbstractEndpointType
{
    /**
     * NameIDMappingService constructor.
     *
     * This is an endpoint with one restriction: it cannot contain a ResponseLocation.
     *
     * @param string $binding
     * @param string $location
     * @param string|null $unused
     * @param array|null $attributes
     */
    public function __construct(
        string $binding,
        string $location,
        ?string $unused = null,
        ?array $attributes = null
    ) {
        Assert::null(
            $unused,
            'The \'ResponseLocation\' attribute must be omitted for md:NameIDMappingService.'
        );
        parent::__construct($binding, $location, null, $attributes);
    }
}
