<?php

namespace FatchipAfterbuy\ValueObjects;

use Doctrine\Common\Collections\ArrayCollection;
use FatchipAfterbuy\ValueObjects\Address as AddressAlias;

class Article extends AbstractValueObject {

    /**
     * we cannot define external identifier types, we have to handle those as strings
     *
     * @var string $externalIdentifier
     */
    public $externalIdentifier;

    /**
     * integer works with category ids, articles use strings (ordernumber)
     *
     * @var int $internalIdentifier
     */
    public $internalIdentifier;

    /**
     * @var int
     */
    public $stock;

    /**
     * @var string
     */
    public $name;

    /**
     * @var float
     */
    public $price;

    /**
     * @var string
     */
    public $manufacturer;

    /**
     * @var string
     */
    public $ean;

    public $stockMin;

    public $pseudoPrice;

    public $tax;

    public $variants = [];

    public $mainArticleId;

    /**
     * @return mixed
     */
    public function getMainArticleId()
    {
        return $this->mainArticleId;
    }

    /**
     * @param mixed $mainArticleId
     */
    public function setMainArticleId($mainArticleId): void
    {
        $this->mainArticleId = $mainArticleId;
    }

    /**
     * @return array
     */
    public function getVariants(): array
    {
        return $this->variants;
    }

    /**
     * @param array $variants
     */
    public function setVariants(array $variants): void
    {
        $this->variants = $variants;
    }

    /**
     * @return mixed
     */
    public function getPseudoPrice()
    {
        return $this->pseudoPrice;
    }

    /**
     * @param mixed $pseudoPrice
     */
    public function setPseudoPrice($pseudoPrice): void
    {
        $this->pseudoPrice = $pseudoPrice;
    }

    /**
     * @return mixed
     */
    public function getTax()
    {
        return $this->tax;
    }

    /**
     * @param mixed $tax
     */
    public function setTax($tax): void
    {
        $this->tax = $tax;
    }

    /**
     * @return mixed
     */
    public function getStockMin()
    {
        return $this->stockMin;
    }

    /**
     * @param mixed $stockMin
     */
    public function setStockMin($stockMin): void
    {
        $this->stockMin = $stockMin;
    }



    /**
     * @return string
     */
    public function getExternalIdentifier(): string
    {
        return $this->externalIdentifier;
    }

    /**
     * @param string $externalIdentifier
     */
    public function setExternalIdentifier(string $externalIdentifier): void
    {
        $this->externalIdentifier = $externalIdentifier;
    }

    /**
     * @return int
     */
    public function getInternalIdentifier(): int
    {
        return $this->internalIdentifier;
    }

    /**
     * @param int $internalIdentifier
     */
    public function setInternalIdentifier(int $internalIdentifier): void
    {
        $this->internalIdentifier = $internalIdentifier;
    }

    /**
     * @return int
     */
    public function getStock(): int
    {
        return $this->stock;
    }

    /**
     * @param int $stock
     */
    public function setStock(int $stock): void
    {
        $this->stock = $stock;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * @return float
     */
    public function getPrice(): float
    {
        return $this->price;
    }

    /**
     * @param float $price
     */
    public function setPrice(float $price): void
    {
        $this->price = $price;
    }

    /**
     * @return string
     */
    public function getManufacturer(): string
    {
        return $this->manufacturer;
    }

    /**
     * @param string $manufacturer
     */
    public function setManufacturer(string $manufacturer): void
    {
        $this->manufacturer = $manufacturer;
    }

    /**
     * @return string
     */
    public function getEan(): string
    {
        return $this->ean;
    }

    /**
     * @param string $ean
     */
    public function setEan(string $ean): void
    {
        $this->ean = $ean;
    }




}