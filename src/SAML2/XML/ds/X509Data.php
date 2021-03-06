<?php

declare(strict_types=1);

namespace SAML2\XML\ds;

use DOMElement;
use SAML2\XML\Chunk;
use SAML2\XML\ds\X509Certificate;
use Webmozart\Assert\Assert;

/**
 * Class representing a ds:X509Data element.
 *
 * @package SimpleSAMLphp
 */
final class X509Data extends AbstractDsElement
{
    /**
     * The various X509 data elements.
     *
     * Array with various elements describing this certificate.
     * Unknown elements will be represented by \SAML2\XML\Chunk.
     *
     * @var (\SAML2\XML\Chunk|\SAML2\XML\ds\X509Certificate)[]
     */
    protected $data = [];


    /**
     * Initialize a X509Data.
     *
     * @param (\SAML2\XML\Chunk|\SAML2\XML\ds\X509Certificate)[] $data
     */
    public function __construct(array $data)
    {
        $this->setData($data);
    }


    /**
     * Collect the value of the data-property
     *
     * @return array
     */
    public function getData(): array
    {
        return $this->data;
    }


    /**
     * Set the value of the data-property
     *
     * @param array $data
     * @return void
     */
    private function setData(array $data): void
    {
        Assert::allIsInstanceOfAny($data, [Chunk::class, X509Certificate::class]);

        $this->data = $data;
    }


    /**
     * Add the value to the data-property
     *
     * @param \SAML2\XML\Chunk|\SAML2\XML\ds\X509Certificate $data
     * @return void
     *
     * @throws \InvalidArgumentException if assertions are false
     */
    public function addData($data): void
    {
        Assert::isInstanceOfAny($data, [Chunk::class, X509Certificate::class]);

        $this->data[] = $data;
    }


    /**
     * Convert XML into a X509Data
     *
     * @param \DOMElement $xml The XML element we should load
     * @return self
     */
    public static function fromXML(DOMElement $xml): object
    {
        Assert::same($xml->localName, 'X509Data');
        Assert::same($xml->namespaceURI, X509Data::NS);

        $data = [];

        for ($n = $xml->firstChild; $n !== null; $n = $n->nextSibling) {
            if (!($n instanceof DOMElement)) {
                continue;
            }

            if ($n->namespaceURI !== self::NS) {
                $data[] = new Chunk($n);
                continue;
            }

            switch ($n->localName) {
                case 'X509Certificate':
                    $data[] = X509Certificate::fromXML($n);
                    break;
                default:
                    $data[] = new Chunk($n);
                    break;
            }
        }

        return new self($data);
    }


    /**
     * Convert this X509Data element to XML.
     *
     * @param \DOMElement|null $parent The element we should append this X509Data element to.
     * @return \DOMElement
     */
    public function toXML(DOMElement $parent = null): DOMElement
    {
        $e = $this->instantiateParentElement($parent);

        /** @var \SAML2\XML\Chunk|\SAML2\XML\ds\X509Certificate $n */
        foreach ($this->getData() as $n) {
            $n->toXML($e);
        }

        return $e;
    }
}
