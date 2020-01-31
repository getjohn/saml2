<?php

declare(strict_types=1);

namespace SAML2\XML\md;

use DOMElement;
use SAML2\Constants;
use SAML2\Utils;
use Webmozart\Assert\Assert;

/**
 * Class representing SAML 2 metadata AuthnAuthorityDescriptor.
 *
 * @package simplesamlphp/saml2
 */
final class AuthnAuthorityDescriptor extends AbstractRoleDescriptor
{
    /**
     * List of AuthnQueryService endpoints.
     *
     * @var \SAML2\XML\md\AbstractEndpointType[]
     */
    protected $AuthnQueryServices = [];

    /**
     * List of AssertionIDRequestService endpoints.
     *
     * @var \SAML2\XML\md\AbstractEndpointType[]
     */
    protected $AssertionIDRequestServices = [];

    /**
     * List of supported NameID formats.
     *
     * Array of strings.
     *
     * @var string[]
     */
    protected $NameIDFormats = [];


    /**
     * AuthnAuthorityDescriptor constructor.
     *
     * @param array $authnQueryServices
     * @param array $protocolSupportEnumeration
     * @param array|null $assertionIDRequestServices
     * @param array|null $nameIDFormats
     * @param string|null $ID
     * @param int|null $validUntil
     * @param string|null $cacheDuration
     * @param \SAML2\XML\md\Extensions|null $extensions
     * @param string|null $errorURL
     * @param array|null $keyDescriptors
     * @param \SAML2\XML\md\Organization|null $organization
     * @param array|null $contacts
     */
    public function __construct(
        array $authnQueryServices,
        array $protocolSupportEnumeration,
        ?array $assertionIDRequestServices = null,
        ?array $nameIDFormats = null,
        ?string $ID = null,
        ?int $validUntil = null,
        ?string $cacheDuration = null,
        ?Extensions $extensions = null,
        ?string $errorURL = null,
        ?array $keyDescriptors = [],
        ?Organization $organization = null,
        ?array $contacts = []
    ) {
        parent::__construct(
            $protocolSupportEnumeration,
            $ID,
            $validUntil,
            $cacheDuration,
            $extensions,
            $errorURL,
            $keyDescriptors,
            $organization,
            $contacts
        );
        $this->setAuthnQueryServices($authnQueryServices);
        $this->setAssertionIDRequestService($assertionIDRequestServices);
        $this->setNameIDFormat($nameIDFormats);
    }


    /**
     * Initialize an IDPSSODescriptor from an existing XML document.
     *
     * @param \DOMElement|null $xml The XML element we should load.
     * @return self
     * @throws \Exception
     */
    public static function fromXML(DOMElement $xml = null): object
    {
        $authnQueryServices = [];
        /** @var DOMElement $ep */
        foreach (Utils::xpQuery($xml, './saml_metadata:AuthnQueryService') as $ep) {
            $authnQueryServices[] = AuthnQueryService::fromXML($ep);
        }

        $assertionIDRequestServices = [];
        /** @var DOMElement $ep */
        foreach (Utils::xpQuery($xml, './saml_metadata:AssertionIDRequestService') as $ep) {
            $assertionIDRequestServices[] = AssertionIDRequestService::fromXML($ep);
        }

        $nameIDFormats = Utils::extractStrings($xml, Constants::NS_MD, 'NameIDFormat');

        $validUntil = self::getAttribute($xml, 'validUntil', null);

        $orgs = Organization::getChildrenOfClass($xml);
        Assert::maxCount($orgs, 1, 'More than one Organization found in this descriptor');

        $extensions = Extensions::getChildrenOfClass($xml);
        Assert::maxCount($extensions, 1, 'Only one md:Extensions element is allowed.');

        return new self(
            $authnQueryServices,
            preg_split('/[\s]+/', trim(self::getAttribute($xml, 'protocolSupportEnumeration'))),
            $assertionIDRequestServices,
            $nameIDFormats,
            self::getAttribute($xml, 'ID', null),
            $validUntil !== null ? Utils::xsDateTimeToTimestamp($validUntil) : null,
            self::getAttribute($xml, 'cacheDuration', null),
            !empty($extensions) ? $extensions[0] : null,
            self::getAttribute($xml, 'errorURL', null),
            KeyDescriptor::getChildrenOfClass($xml),
            !empty($orgs) ? $orgs[0] : null,
            ContactPerson::getChildrenOfClass($xml)
        );
    }


    /**
     * Collect the AuthnQueryService endpoints
     *
     * @return \SAML2\XML\md\AbstractEndpointType[]
     */
    public function getAuthnQueryServices(): array
    {
        return $this->AuthnQueryServices;
    }


    /**
     * Set the AuthnQueryService endpoints
     *
     * @param \SAML2\XML\md\AbstractEndpointType[] $authnQueryServices
     */
    protected function setAuthnQueryServices(array $authnQueryServices): void
    {
        Assert::minCount($authnQueryServices, 1, 'Missing at least one AuthnQueryService in AuthnAuthorityDescriptor.');
        Assert::allIsInstanceOf(
            $authnQueryServices,
            AbstractEndpointType::class,
            'AuthnQueryService must be an instance of EndpointType'
        );
        $this->AuthnQueryServices = $authnQueryServices;
    }


    /**
     * Collect the AssertionIDRequestService endpoints
     *
     * @return \SAML2\XML\md\AbstractEndpointType[]
     */
    public function getAssertionIDRequestServices(): array
    {
        return $this->AssertionIDRequestServices;
    }


    /**
     * Set the AssertionIDRequestService endpoints
     *
     * @param \SAML2\XML\md\AbstractEndpointType[] $assertionIDRequestServices
     */
    protected function setAssertionIDRequestService(?array $assertionIDRequestServices): void
    {
        if ($assertionIDRequestServices === null) {
            return;
        }
        Assert::allIsInstanceOf(
            $assertionIDRequestServices,
            AbstractEndpointType::class,
            'AssertionIDRequestServices must be an instance of EndpointType'
        );
        $this->AssertionIDRequestServices = $assertionIDRequestServices;
    }


    /**
     * Collect the values of the NameIDFormat
     *
     * @return string[]
     */
    public function getNameIDFormats(): array
    {
        return $this->NameIDFormats;
    }


    /**
     * Set the values of the NameIDFormat
     *
     * @param string[] $nameIDFormats
     */
    protected function setNameIDFormat(?array $nameIDFormats): void
    {
        if ($nameIDFormats === null) {
            return;
        }
        Assert::allStringNotEmpty($nameIDFormats, 'NameIDFormat cannot be an empty string.');
        $this->NameIDFormats = $nameIDFormats;
    }


    /**
     * Add this IDPSSODescriptor to an EntityDescriptor.
     *
     * @param \DOMElement $parent The EntityDescriptor we should append this AuthnAuthorityDescriptor to.
     *
     * @return \DOMElement
     * @throws \Exception
     */
    public function toXML(DOMElement $parent = null): DOMElement
    {
        $e = parent::toXML($parent);

        foreach ($this->AuthnQueryServices as $ep) {
            $ep->toXML($e);
        }

        foreach ($this->AssertionIDRequestServices as $ep) {
            $ep->toXML($e);
        }

        Utils::addStrings($e, Constants::NS_MD, 'md:NameIDFormat', false, $this->NameIDFormats);

        return $e;
    }
}
