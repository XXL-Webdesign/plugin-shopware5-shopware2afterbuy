<?php

namespace FatchipAfterbuy\Services\Helper;

use Fatchip\Afterbuy\ApiClient;
use FatchipAfterbuy\ValueObjects\Article;
use FatchipAfterbuy\ValueObjects\ProductPicture;
use FatchipAfterbuy\Components\Helper;

/**
 * Class ShopwareArticleHelper
 * @package FatchipAfterbuy\Services\Helper
 */
class AfterbuyProductsHelper extends ShopwareArticleHelper {

    public function buildAfterbuyImages(array $images) {
        $productPictures = [];

        for($i = 1; $i <= 12; $i++) {

            $index = $i - 1;

            if(!array_key_exists($index, $images) || is_null($images[$index])) {
                $imageUrl = '';
                $imageAltText = '';
            } else {
                $imageUrl = $images[$index]->getUrl();
                $imageAltText = $images[$index]->getAltText();
            }

            $productPictures[] = array(
                'ProductPicture' => array(
                    'Nr' => $i,
                    'Url' => $imageUrl,
                    'AltText' => $imageAltText
                )
            );
        }

        return $productPictures;
    }

    public function buildAfterbuyVariantOptions(Article $variant)
    {
        $variants = [];

        foreach ($variant->getVariants() as $group => $option) {
            $variants[] = array(
                'AddAttribute' => array(
                    'AttributName' => $group,
                    'AttibutValue' => $option,
                    'AttributTyp' => 1,
                    'AttributRequired' => 1
                )
            );
        }

        return $variants;
    }

    public function submitAfterbuyVariantProducts(array $data, ApiClient $api, $afterbuyProductIds = []) {
        foreach ($data as $value) {

            if (!$value->getVariantArticles()) {
                continue;
            }

            $products = array(
                'Products' => array()
            );

            foreach ($value->getVariantArticles() as $variant) {
                $variant = $this->buildAfterbuyVariant($variant, $value);
                $products['Products'][] = $variant;
            }

            $this->sendAfterbuyProducts($products, $api, $afterbuyProductIds);
            $baseProduct = $this->buildAfterbuyVariantBaseProduct($value, $afterbuyProductIds);
            $response = $this->sendAfterbuyProducts($baseProduct, $api);
        }

        return $afterbuyProductIds;
    }

    public function buildAfterbuyVariant(Article $variant, Article $value) {
        $variants = $this->buildAfterbuyVariantOptions($variant);
        $variantImages = $this->buildAfterbuyImages($variant->getProductPictures());

        $product = array(
            'Product' => array(
                'ProductIdent' => array(
                    'ProductInsert' => 1,
                    'Anr' => $variant->getVariantId()
                ),
                'Anr' => $variant->getVariantId(),
                'EAN' => $variant->getInternalIdentifier(),
                'Name' => $variant->getName(),
                'ManufacturerPartNumber' => $variant->getSupplierNumber(),
                'Description' => $variant->getDescription(),
                'ShortDescription' => $variant->getShortDescription(),
                'Quantity' => $variant->getStock(),
                'UnitOfQuantity' => 'Stk',
                'MinimumStock' => $variant->getStockMin(),
                'SellingPrice' => Helper::convertNumberToABString($variant->getPrice()),
                'TaxRate' => Helper::convertNumberToABString($variant->getTax()),
                'ProductBrand' => $value->getManufacturer(),
                'AddAttributes' => $variants,
                'ImageLargeURL' => $variant->getMainImageUrl(),
                'ImageSmallURL' => $variant->getMainImageThumbnailUrl(),
                'ProductPictures' => $variantImages
            )
        );

        return $product;
    }

    public function buildAfterbuyVariantBaseProduct(Article $value, array $afterbuyProductIds) {
        $variantArticles = $this->buildAfterbuyVariantAssignment($value, $afterbuyProductIds);
        $productImages = $this->buildAfterbuyImages($value->getProductPictures());

        // we have no unique base product identifier
        // Anr 1,{$articleId}
        // neccessary cuz articleIds and detailIds may collidate
        $products['Products'] = array(
            'Product' => array(
                'ProductIdent' => array(
                    'ProductInsert' => 1,
                    'Anr' => '1,' . $value->getMainArticleId(),
                    'BaseProductType' => 1
                ),
                'Anr' => '1,' . $value->getMainArticleId(),
                'EAN' => $value->getInternalIdentifier(),
                'Name' => $value->getName(),
                'Description' => $value->getDescription(),
                'ShortDescription' => $value->getShortDescription(),
                'UnitOfQuantity' => 'Stk',
                'TaxRate' => Helper::convertNumberToABString($value->getTax()),
                'ProductBrand' => $value->getManufacturer(),
                'AddBaseProducts' => $variantArticles,
                'ImageLargeURL' => $value->getMainImageUrl(),
                'ImageSmallURL' => $value->getMainImageThumbnailUrl(),
                'ProductPictures' => $productImages,
                'AddCatalogs' => $this->buildAfterbuyCatalogAssignment($value->getExternalCategoryIds()),
            )
        );

        return $products;
    }

    public function buildAfterbuyVariantAssignment(Article $value, array $afterbuyProductIds) {

        $variantArticles = [];

        foreach($value->getVariantArticles() as $variant) {
            if(array_key_exists($variant->getVariantId(), $afterbuyProductIds)) {
                $variant->setExternalIdentifier($afterbuyProductIds[$variant->getVariantId()]);
            }

            if(!$variant->getExternalIdentifier()) {
                continue;
            }

            $variantArticles[] = array(
                'AddBaseProduct' => array(
                    'ProductID' => $variant->getExternalIdentifier(),
                    'ProductLabel' => $variant->getName(),
                    'ProductQuantity' => $variant->getStock()
                )
            );
        }

        return $variantArticles;
    }

    public function buildAfterbuyCatalogAssignment($ids) {
        $catalogs = [];

        foreach($ids as $value) {
            $catalogs[] = array(
                    'CatalogID' => $value
            );
        }

        $assignment = array(
            'UpdateAction' => 2,
            'AddCatalog' => $catalogs
        );

        return $assignment;
    }

    public function submitAfterbuySimpleProducts(array $data, ApiClient $api, $afterbuyProductIds = []) {
        $products = array(
            'Products' => array(
            )
        );

        foreach($data as $value) {
            /**
             * @var \FatchipAfterbuy\ValueObjects\Article $value
             */

            if($value->getVariantArticles()) {
                continue;
            }

            $product = $this->buildAfterbuySimpleProduct($value);

            $products['Products'][] = $product;
        }

        $this->sendAfterbuyProducts($products, $api, $afterbuyProductIds);

        return $afterbuyProductIds;
    }

    public function sendAfterbuyProducts(array $products, ApiClient $api, &$afterbuyProductIds = []) {

        if(count($products['Products'])) {
            $response = $api->updateShopProducts($products);

            if(array_key_exists('Result', $response) && array_key_exists('NewProducts', $response["Result"])) {

                if (array_key_exists('ProductID', $response["Result"]["NewProducts"]["NewProduct"])) {
                    $internalArticleNumber = $response["Result"]["NewProducts"]["NewProduct"]["Anr"];
                    $afterbuyProductIds[$internalArticleNumber] = $response["Result"]["NewProducts"]["NewProduct"]["ProductID"];
                } elseif (is_array($response["Result"]["NewProducts"]["NewProduct"][0])) {

                    foreach ($response["Result"]["NewProducts"]["NewProduct"] as $newProduct) {
                        $internalArticleNumber = $newProduct["Anr"];
                        $afterbuyProductIds[$internalArticleNumber] = $newProduct["ProductID"];
                    }
                }
            }
        }
    }

    public function buildAfterbuySimpleProduct(Article $value) {
        $product = array(
            'Product' => array(
                'ProductIdent' => array(
                    'ProductInsert' => 1,
                    'Anr' => $value->getVariantId()
                ),
                'EAN' => $value->getInternalIdentifier(),
                'Anr' => (string) $value->getVariantId(),
                'Name' => $value->getName(),
                'ManufacturerPartNumber' => $value->getSupplierNumber(),
                'Description' => $value->getDescription(),
                'ShortDescription' => $value->getShortDescription(),
                'Quantity' => $value->getStock(),
                'UnitOfQuantity' => 'Stk',
                'MinimumStock' => $value->getStockMin(),
                'SellingPrice' => Helper::convertNumberToABString($value->getPrice()),
                'TaxRate' => Helper::convertNumberToABString($value->getTax()),
                'ProductBrand' => $value->getManufacturer(),
                'ImageLargeURL' => $value->getMainImageUrl(),
                'ImageSmallURL' => $value->getMainImageThumbnailUrl(),
                'AddCatalogs' => $this->buildAfterbuyCatalogAssignment($value->getExternalCategoryIds()),
            )
        );

        $product['Product']['ProductPictures'] = $this->buildAfterbuyImages($value->getProductPictures());

        return $product;
    }

}