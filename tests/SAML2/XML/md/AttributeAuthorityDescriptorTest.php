<?php

declare(strict_types=1);

namespace SAML2\XML\md;

use PHPUnit\Framework\TestCase;
use SAML2\Constants;
use SAML2\DOMDocumentFactory;
use SAML2\XML\saml\Attribute;
use SAML2\XML\saml\AttributeValue;

/**
 * Tests for the AttributeAuthorityDescriptor class.
 *
 * @package simplesamlphp/saml2
 */
final class AttributeAuthorityDescriptorTest extends TestCase
{
    protected $as;
    protected $aidrs;
    protected $document;


    protected function setUp(): void
    {
        $mdns = Constants::NS_MD;
        $samlns = Constants::NS_SAML;
        $this->document = DOMDocumentFactory::fromString(<<<XML
<md:AttributeAuthorityDescriptor protocolSupportEnumeration="urn:oasis:names:tc:SAML:2.0:protocol" xmlns:md="{$mdns}">
  <md:AttributeService Binding="urn:oasis:names:tc:SAML:2.0:bindings:SOAP" Location="https://IdentityProvider.com/SAML/AA/SOAP"/>
  <md:AssertionIDRequestService Binding="urn:oasis:names:tc:SAML:2.0:bindings:URI" Location="https://IdentityProvider.com/SAML/AA/URI"/>
  <md:NameIDFormat>urn:oasis:names:tc:SAML:1.1:nameid-format:X509SubjectName</md:NameIDFormat>
  <md:NameIDFormat>urn:oasis:names:tc:SAML:2.0:nameid-format:persistent</md:NameIDFormat>
  <md:NameIDFormat>urn:oasis:names:tc:SAML:2.0:nameid-format:transient</md:NameIDFormat>
  <md:AttributeProfile>profile1</md:AttributeProfile>
  <md:AttributeProfile>profile2</md:AttributeProfile>
  <saml:Attribute xmlns:saml="{$samlns}" Name="urn:oid:1.3.6.1.4.1.5923.1.1.1.6" NameFormat="urn:oasis:names:tc:SAML:2.0:attrname-format:uri" FriendlyName="eduPersonPrincipalName"></saml:Attribute>
  <saml:Attribute xmlns:saml="{$samlns}" Name="urn:oid:1.3.6.1.4.1.5923.1.1.1.1" NameFormat="urn:oasis:names:tc:SAML:2.0:attrname-format:uri" FriendlyName="eduPersonAffiliation">
    <saml:AttributeValue>member</saml:AttributeValue>
    <saml:AttributeValue>student</saml:AttributeValue>
    <saml:AttributeValue>faculty</saml:AttributeValue>
    <saml:AttributeValue>employee</saml:AttributeValue>
    <saml:AttributeValue>staff</saml:AttributeValue>
  </saml:Attribute>
</md:AttributeAuthorityDescriptor>
XML
        );

        $this->as = new AttributeService(
            "urn:oasis:names:tc:SAML:2.0:bindings:SOAP",
            "https://IdentityProvider.com/SAML/AA/SOAP"
        );
        $this->aidrs = new AssertionIDRequestService(
            "urn:oasis:names:tc:SAML:2.0:bindings:URI",
            "https://IdentityProvider.com/SAML/AA/URI"
        );
    }


    // test marshalling


    /**
     * Test creating an AttributeAuthorityDescriptor from scratch
     */
    public function testMarshalling(): void
    {
        $attr1 = new Attribute(
            "urn:oid:1.3.6.1.4.1.5923.1.1.1.6",
            "urn:oasis:names:tc:SAML:2.0:attrname-format:uri",
            "eduPersonPrincipalName"
        );

        $attr2 = new Attribute(
            "urn:oid:1.3.6.1.4.1.5923.1.1.1.1",
            "urn:oasis:names:tc:SAML:2.0:attrname-format:uri",
            'eduPersonAffiliation',
            [
                new AttributeValue('member'),
                new AttributeValue('student'),
                new AttributeValue('faculty'),
                new AttributeValue('employee'),
                new AttributeValue('staff'),
            ]
        );
        $aad = new AttributeAuthorityDescriptor(
            [$this->as],
            ["urn:oasis:names:tc:SAML:2.0:protocol"],
            [$this->aidrs],
            [
                'urn:oasis:names:tc:SAML:1.1:nameid-format:X509SubjectName',
                'urn:oasis:names:tc:SAML:2.0:nameid-format:persistent',
                'urn:oasis:names:tc:SAML:2.0:nameid-format:transient',
            ],
            [
                'profile1',
                'profile2',
            ],
            [$attr1, $attr2]
        );
        $this->assertEqualXMLStructure($this->document->documentElement, $aad->toXML());
    }

    /**
     * Test that creating an AttributeAuthorityDescriptor with no supported protocols fails.
     */
    public function testMarshallingWithoutSupportedProtocols(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage(
            'At least one protocol must be supported by this SAML2\XML\md\AttributeAuthorityDescriptor.'
        );
        new AttributeAuthorityDescriptor([$this->as], []);
    }


    /**
     * Test that creating an AttributeAuthorityDescriptor with an empty supported protocol fails.
     */
    public function testMarshallingWithEmptySupportedProtocols(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Cannot specify an empty string as a supported protocol.');
        new AttributeAuthorityDescriptor([$this->as], ['']);
    }


    /**
     * Test that creating an AttributeAuthorityDescriptor with no AttributeService fails.
     */
    public function testMarshallingWithoutAttributeServices(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('AttributeAuthorityDescriptor must contain at least one AttributeService.');
        new AttributeAuthorityDescriptor([], ['protocol1']);
    }


    /**
     * Test that creating an AttributeAuthorityDescriptor with an AttributeService of the wrong type fails.
     */
    public function testMarshallingWithWrongAttributeService(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('AttributeService is not an instance of EndpointType.');
        new AttributeAuthorityDescriptor(['string'], ['protocol1']);
    }


    /**
     * Test that creating an AttributeAuthorityDescriptor without optional parameters works.
     */
    public function testMarshallingWithoutOptionalParameters(): void
    {
        new AttributeAuthorityDescriptor([$this->as], ['x']);
        $this->assertTrue(true);
    }


    /**
     * Test that creating an AttributeAuthorityDescriptor with empty AssertionIDRequestService works.
     */
    public function testMarshallingWithEmptyAssertionIDRequestService(): void
    {
        new AttributeAuthorityDescriptor([$this->as], ['x'], []);
        $this->assertTrue(true);
    }


    /**
     * Test that creating an AttributeAuthorityDescriptor with wrong AssertionIDRequestService fails.
     */
    public function testMarshallingWithWrongAssertionIDRequestService(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected an instance of SAML2\XML\md\AssertionIDRequestService. Got: string');
        new AttributeAuthorityDescriptor([$this->as], ['x'], ['x']);
    }


    /**
     * Test that creating an AttributeAuthorityDescriptor with an empty NameIDFormat fails.
     */
    public function testMarshallingWithEmptyNameIDFormat(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('NameIDFormat cannot be an empty string.');
        new AttributeAuthorityDescriptor([$this->as], ['x'], [$this->aidrs], ['']);
    }


    /**
     * Test that creating an AttributeAuthorityDescriptor with an empty AttributeProfile fails.
     */
    public function testMarshallingWithEmptyAttributeProfile(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('AttributeProfile cannot be an empty string.');
        new AttributeAuthorityDescriptor([$this->as], ['x'], [$this->aidrs], ['x'], ['']);
    }


    /**
     * Test that creating an AttributeAuthorityDescriptor with wrong Attribute fails.
     */
    public function testMarshallingWithWrongAttribute(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected an instance of SAML2\XML\saml\Attribute. Got: string');
        new AttributeAuthorityDescriptor([$this->as], ['x'], [$this->aidrs], ['x'], ['x'], ['x']);
    }


    // test unmarshalling


    /**
     * Test creating an AttributeAuthorityDescriptor from XML
     */
    public function testUnmarshalling(): void
    {
        $aad = AttributeAuthorityDescriptor::fromXML($this->document->documentElement);
        /** @var AttributeService[] $as */
        $as = $aad->getAttributeServices();
        $this->assertCount(1, $as, "Wrong number of AttributeService elements.");
        $this->assertInstanceOf(AttributeService::class, $as[0]);
        $this->assertEquals('urn:oasis:names:tc:SAML:2.0:bindings:SOAP', $as[0]->getBinding());
        $this->assertEquals('https://IdentityProvider.com/SAML/AA/SOAP', $as[0]->getLocation());
        /** @var AssertionIDRequestService[] $aidrs */
        $aidrs = $aad->getAssertionIDRequestServices();
        $this->assertCount(1, $aidrs, "Wrong number of AssertionIDRequestService elements.");
        $this->assertInstanceOf(AssertionIDRequestService::class, $aidrs[0]);
        $this->assertEquals('urn:oasis:names:tc:SAML:2.0:bindings:URI', $aidrs[0]->getBinding());
        $this->assertEquals('https://IdentityProvider.com/SAML/AA/URI', $aidrs[0]->getLocation());
        $this->assertEquals(
            [
                'urn:oasis:names:tc:SAML:1.1:nameid-format:X509SubjectName',
                'urn:oasis:names:tc:SAML:2.0:nameid-format:persistent',
                'urn:oasis:names:tc:SAML:2.0:nameid-format:transient',
            ],
            $aad->getNameIDFormats()
        );
        $attrs = $aad->getAttributes();
        $this->assertCount(2, $attrs, "Wrong number of attributes.");
        foreach ($attrs as $attr) {
            $this->assertInstanceOf(Attribute::class, $attr);
        }
    }


    /**
     * Test that creating an AttributeAuthorityDescriptor without any optional element works.
     */
    public function testUnmarshallingWithoutOptionalElements(): void
    {
        $mdns = Constants::NS_MD;
        $document = DOMDocumentFactory::fromString(<<<XML
<md:AttributeAuthorityDescriptor protocolSupportEnumeration="urn:oasis:names:tc:SAML:2.0:protocol" xmlns:md="{$mdns}">
  <md:AttributeService Binding="urn:oasis:names:tc:SAML:2.0:bindings:SOAP" Location="https://IdentityProvider.com/SAML/AA/SOAP"/>
</md:AttributeAuthorityDescriptor>
XML
        );
        AttributeAuthorityDescriptor::fromXML($document->documentElement);
        $this->assertTrue(true);
    }


    /**
     * Test that creating an AttributeAuthorityDescriptor with an empty NameIDFormat fails.
     */
    public function testUnmarshallingWithEmptyNameIDFormat(): void
    {
        $mdns = Constants::NS_MD;
        $document = DOMDocumentFactory::fromString(<<<XML
<md:AttributeAuthorityDescriptor protocolSupportEnumeration="urn:oasis:names:tc:SAML:2.0:protocol" xmlns:md="{$mdns}">
  <md:AttributeService Binding="urn:oasis:names:tc:SAML:2.0:bindings:SOAP" Location="https://IdentityProvider.com/SAML/AA/SOAP"/>
  <md:NameIDFormat></md:NameIDFormat>
</md:AttributeAuthorityDescriptor>
XML
        );
        $this->expectExceptionMessage('NameIDFormat cannot be an empty string.');
        AttributeAuthorityDescriptor::fromXML($document->documentElement);
    }


    /**
     * Test that creating an AttributeAuthorityDescriptor with an empty AttributeProfile fails.
     */
    public function testUnmarshallingWithEmptyAttributeProfile(): void
    {
        $mdns = Constants::NS_MD;
        $document = DOMDocumentFactory::fromString(<<<XML
<md:AttributeAuthorityDescriptor protocolSupportEnumeration="urn:oasis:names:tc:SAML:2.0:protocol" xmlns:md="{$mdns}">
  <md:AttributeService Binding="urn:oasis:names:tc:SAML:2.0:bindings:SOAP" Location="https://IdentityProvider.com/SAML/AA/SOAP"/>
  <md:AttributeProfile></md:AttributeProfile>
</md:AttributeAuthorityDescriptor>
XML
        );
        $this->expectExceptionMessage('AttributeProfile cannot be an empty string.');
        AttributeAuthorityDescriptor::fromXML($document->documentElement);
    }


    /**
     * Test serialization and unserialization of unknown role descriptors.
     */
    public function testSerialization(): void
    {
        $descriptor = AttributeAuthorityDescriptor::fromXML($this->document->documentElement);
        $this->assertEquals(
            $this->document->saveXML($this->document->documentElement),
            strval(unserialize(serialize($descriptor)))
        );
    }
}
