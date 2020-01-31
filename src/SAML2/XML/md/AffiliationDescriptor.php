<?php

declare(strict_types=1);

namespace SAML2\XML\md;

use DOMElement;
use Exception;
use SAML2\Constants;
use SAML2\Utils;
use Webmozart\Assert\Assert;

/**
 * Class representing SAML 2 AffiliationDescriptor element.
 *
 * @package simplesamlphp/saml2
 */
final class AffiliationDescriptor extends AbstractMetadataDocument
{
    /**
     * The affiliationOwnerID.
     *
     * @var string
     */
    public $affiliationOwnerID;

    /**
     * The AffiliateMember(s).
     *
     * Array of entity ID strings.
     *
     * @var array
     */
    protected $AffiliateMembers = [];

    /**
     * KeyDescriptor elements.
     *
     * Array of \SAML2\XML\md\KeyDescriptor elements.
     *
     * @var \SAML2\XML\md\KeyDescriptor[]
     */
    protected $KeyDescriptors = [];


    /**
     * Generic constructor for SAML metadata documents.
     *
     * @param string $ownerID The ID of the owner of this affiliation.
     * @param array $members A non-empty array of members of this affiliation.
     * @param \SAML2\XML\md\KeyDescriptor[]|null $keyDescriptors An optional array of KeyDescriptors. Defaults to an empty array.
     * @param string|null $ID The ID for this document. Defaults to null.
     * @param int|null    $validUntil Unix time of validity for this document. Defaults to null.
     * @param string|null $cacheDuration Maximum time this document can be cached. Defaults to null.
     * @param \SAML2\XML\md\Extensions|null An array of extensions. Defaults to an empty array.
     *
     * @throws \Exception
     */
    public function __construct(
        string $ownerID,
        array $members,
        ?array $keyDescriptors = null,
        ?string $ID = null,
        ?int $validUntil = null,
        ?string $cacheDuration = null,
        ?Extensions $extensions = null
    ) {
        parent::__construct($ID, $validUntil, $cacheDuration, $extensions);
        $this->setAffiliationOwnerID($ownerID);
        $this->setAffiliateMembers($members);
        $this->setKeyDescriptors($keyDescriptors);
    }


    /**
     * Collect the value of the affiliationOwnerId-property
     *
     * @return string
     */
    public function getAffiliationOwnerID(): string
    {

        return $this->affiliationOwnerID;
    }


    /**
     * Set the value of the affiliationOwnerId-property
     *
     * @param string $affiliationOwnerId
     * @throws \InvalidArgumentException
     */
    protected function setAffiliationOwnerID(string $affiliationOwnerId): void
    {
        Assert::notEmpty($affiliationOwnerId, 'AffiliationOwnerID must not be empty.');
        $this->affiliationOwnerID = $affiliationOwnerId;
    }


    /**
     * Collect the value of the AffiliateMember-property
     *
     * @return array
     */
    public function getAffiliateMembers(): array
    {
        return $this->AffiliateMembers;
    }


    /**
     * Set the value of the AffiliateMember-property
     *
     * @param array $affiliateMembers
     */
    protected function setAffiliateMembers(array $affiliateMembers): void
    {
        Assert::notEmpty($affiliateMembers, 'List of affiliated members must not be empty.');
        Assert::allStringNotEmpty(
            $affiliateMembers,
            'Cannot specify an empty string as an affiliation member entityID.'
        );
        $this->AffiliateMembers = $affiliateMembers;
    }


    /**
     * Collect the value of the KeyDescriptor-property
     *
     * @return \SAML2\XML\md\KeyDescriptor[]
     */
    public function getKeyDescriptors(): array
    {
        return $this->KeyDescriptors;
    }


    /**
     * Set the value of the KeyDescriptor-property
     *
     * @param \SAML2\XML\md\KeyDescriptor[]|null $keyDescriptors
     */
    protected function setKeyDescriptors(?array $keyDescriptors): void
    {
        if ($keyDescriptors !== null) {
            Assert::allIsInstanceOf($keyDescriptors, KeyDescriptor::class);
            $this->KeyDescriptors = $keyDescriptors;
        }
    }


    /**
     * Initialize a AffiliationDescriptor.
     *
     * @param \DOMElement $xml The XML element we should load.
     * @return \SAML2\XML\md\AffiliationDescriptor
     * @throws \Exception
     */
    public static function fromXML(DOMElement $xml): object
    {
        if (!$xml->hasAttribute('affiliationOwnerID')) {
            throw new Exception('Missing affiliationOwnerID on AffiliationDescriptor.');
        }

        $owner = $xml->getAttribute('affiliationOwnerID');
        $members = Utils::extractStrings($xml, Constants::NS_MD, 'AffiliateMember');
        $keyDescriptors = [];

        /** @var \DOMElement $kd */
        foreach (Utils::xpQuery($xml, './saml_metadata:KeyDescriptor') as $kd) {
            $keyDescriptors[] = new KeyDescriptor($kd);
        }

        $validUntil = self::getAttribute($xml, 'validUntil', null);
        $orgs = Organization::getChildrenOfClass($xml);
        Assert::maxCount($orgs, 1, 'More than one Organization found in this descriptor');

        $extensions = Extensions::getChildrenOfClass($xml);
        Assert::maxCount($extensions, 1, 'Only one md:Extensions element is allowed.');

        $afd = new self(
            $owner,
            $members,
            $keyDescriptors,
            self::getAttribute($xml, 'ID', null),
            $validUntil !== null ? Utils::xsDateTimeToTimestamp($validUntil) : null,
            self::getAttribute($xml, 'cacheDuration', null),
            !empty($extensions) ? $extensions[0] : null
        );

        $afd->getSignatureFromXML($xml);
        return $afd;
    }


    /**
     * Add this AffiliationDescriptor to an EntityDescriptor.
     *
     * @param \DOMElement|null $parent The EntityDescriptor we should append this endpoint to.
     * @return \DOMElement
     */
    public function toXML(DOMElement $parent = null): DOMElement
    {
        $e = parent::toXML($parent);
        $e->setAttribute('affiliationOwnerID', $this->affiliationOwnerID);
        Utils::addStrings($e, Constants::NS_MD, 'md:AffiliateMember', false, $this->AffiliateMembers);

        foreach ($this->KeyDescriptors as $kd) {
            $kd->toXML($e);
        }

        $this->signElement($e, $e->firstChild);

        return $e;
    }
}
