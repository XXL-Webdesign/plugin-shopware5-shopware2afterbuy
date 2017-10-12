<?php

namespace Shopware\FatchipShopware2Afterbuy\Components;

use Doctrine\DBAL\Connection;

/**
 * Class CronJob
 *
 * @package Shopware\FatchipShopware2Afterbuy\Components
 */
class CronJob
{
    public function exportMainArticles2Afterbuy()
    {
        // Main Articles have no configurator_set_id defined
        $client = Shopware()->Container()->get('fatchip_shopware2afterbuy_api_client');

        // Get all Articles where after Attribute is set

        $builder = Shopware()->Models()->createQueryBuilder();

        $builder->select([
            'article',
            'mainDetail',
            'tax',
            'attribute',
            'supplier',
            'categories',
            'similar',
            'accessories',
            'accessoryDetail',
            'similarDetail',
            'images',
            'links',
            'downloads',
            'linkAttribute',
            'customerGroups',
            'imageAttribute',
            'downloadAttribute',
            'propertyValues',
            'imageMapping',
            'mappingRule',
            'ruleOption',
        ])
            ->from('Shopware\Models\Article\Article', 'article')
            ->leftJoin('article.mainDetail', 'mainDetail')
            ->leftJoin('article.categories', 'categories', null, null, 'categories.id')
            ->leftJoin('article.similar', 'similar')
            ->leftJoin('article.related', 'accessories')
            ->leftJoin('accessories.mainDetail', 'accessoryDetail')
            ->leftJoin('similar.mainDetail', 'similarDetail')
            ->leftJoin('article.images', 'images')
            ->leftJoin('article.links', 'links')
            ->leftJoin('article.downloads', 'downloads')
            ->leftJoin('article.tax', 'tax')
            ->leftJoin('mainDetail.attribute', 'attribute')
            ->leftJoin('article.supplier', 'supplier')
            ->leftJoin('links.attribute', 'linkAttribute')
            ->leftJoin('article.customerGroups', 'customerGroups')
            ->leftJoin('images.attribute', 'imageAttribute')
            ->leftJoin('downloads.attribute', 'downloadAttribute')
            ->leftJoin('article.propertyValues', 'propertyValues')
            ->leftJoin('images.mappings', 'imageMapping')
            ->leftJoin('imageMapping.rules', 'mappingRule')
            ->leftJoin('mappingRule.option', 'ruleOption')
            ->where('attribute.afterbuyExport = 1')
            ->andWhere('images.parentId IS NULL')
            ->andWhere('article.configuratorSetId IS NULL');


        $afterbuyArticles = $builder->getQuery()->getArrayResult();
        foreach ($afterbuyArticles as $article) {
            $articleRepo = Shopware()->Models()->getRepository('Shopware\Models\Article\Article');
            $idQuery = $articleRepo->getConfiguratorListIdsQuery(
                $article['id']
            );
            $ids = $idQuery->getArrayResult();

            foreach ($ids as $key => $id) {
                $ids[$key] = $id['id'];
            }

            $query = $articleRepo->getDetailsByIdsQuery($ids);
            $details = $query->getArrayResult();

            foreach ($details as $key => $detail) {
                if (empty($detail['prices']) ) {
                    continue;
                }

                $detail['prices'] = $this->formatPricesFromNetToGross($detail['prices'], $article['tax']);
            }

             $mappedAfterbuyArticle = $this->mapAfterbuyArticleAttributes($article,$detail);
             $response = $client->updateArticleToAfterbuy($mappedAfterbuyArticle);

            $this->_fcAddAfterbuyIdToArticle($article['id'], $response);
        }

        return true;
    }

    public function importOrdersFromAfterbuy()
    {
        $client = Shopware()->Container()->get('fatchip_shopware2afterbuy_api_client');

        return true;
    }

    private function  mapAfterbuyArticleAttributes($article,$detail)
    {
        $fcAfterbuyArt = new Api\fcafterbuyart();
        $fcAfterbuyArt = $this->mapRequiredAfterbuyArticleAttributes($fcAfterbuyArt, $article, $detail);
        $fcAfterbuyArt = $this->mapImageAfterbuyArticleAttributes($fcAfterbuyArt, $article, $detail);

        $fcAfterbuyArt->UserProductID           = null;  // Integer
        $fcAfterbuyArt->Anr                     = $article['id']; //Float
        $fcAfterbuyArt->EAN                     = $article['mainDetail']['ean']; // String
        $fcAfterbuyArt->ProductID               = $article['mainDetail']['attribute']['afterbuyProductid']; // Integer
        $fcAfterbuyArt->ShortDescription        = $article['description']; // String
        $fcAfterbuyArt->Memo                    = null; // String
        $fcAfterbuyArt->Description             = $article['descriptionLong'];
        $fcAfterbuyArt->Keywords                = $article['keywords']; // String Kelkoo Keywords
        $fcAfterbuyArt->Quantity                = $article['mainDetail']['inStock']; // Integer
        $fcAfterbuyArt->AuctionQuantity         = null; // Integer
        $fcAfterbuyArt->AddQuantity             = null;
        $fcAfterbuyArt->AddAuctionQuantity      = null;
        $fcAfterbuyArt->Stock                   = null; // bool
        $fcAfterbuyArt->Discontinued            = null; // bool
        $fcAfterbuyArt->MergeStock              = null; // bool
        $fcAfterbuyArt->UnitOfQuantity          = null; //$this->mapUnitQuantity($); // float???
        $fcAfterbuyArt->BasepriceFactor         = null;
        $fcAfterbuyArt->MinimumStock            = null;
        $fcAfterbuyArt->SellingPrice            = $detail['prices']['EK']['price'];
        $fcAfterbuyArt->BuyingPrice             = str_replace('.', ',',$article['mainDetail']['purchasePrice']);
        $fcAfterbuyArt->DealerPrice             = $detail['prices']['H']['price'];
        $fcAfterbuyArt->Level                   = null;
        $fcAfterbuyArt->Position                = null;
        $fcAfterbuyArt->TitleReplace            = null;
        $fcAfterbuyArt->ScaledQuantity          = null;
        $fcAfterbuyArt->ScaledPrice             = null;
        $fcAfterbuyArt->ScaledDPrice            = null;
        $fcAfterbuyArt->TaxRate                 = str_replace('.', ',',$article['tax']['tax']);
        $fcAfterbuyArt->Weight                  = $article['mainDetail']['weight'];
        $fcAfterbuyArt->Stocklocation_1         = null;
        $fcAfterbuyArt->Stocklocation_2         = null;
        $fcAfterbuyArt->Stocklocation_3         = null;
        $fcAfterbuyArt->Stocklocation_4         = null;
        $fcAfterbuyArt->CountryOfOrigin         = null;
        $fcAfterbuyArt->SearchAlias             = null;
        $fcAfterbuyArt->Froogle                 = null;
        $fcAfterbuyArt->Kelkoo                  = null;
        $fcAfterbuyArt->ShippingGroup           = null;
        $fcAfterbuyArt->ShopShippingGroup       = null;
        $fcAfterbuyArt->CrossCatalogID          = null;
        $fcAfterbuyArt->FreeValue1              = null;
        $fcAfterbuyArt->FreeValue2              = null;
        $fcAfterbuyArt->FreeValue3              = null;
        $fcAfterbuyArt->FreeValue4              = null;
        $fcAfterbuyArt->FreeValue5              = null;
        $fcAfterbuyArt->FreeValue6              = null;
        $fcAfterbuyArt->FreeValue7              = null;
        $fcAfterbuyArt->FreeValue8              = null;
        $fcAfterbuyArt->FreeValue9              = null;
        $fcAfterbuyArt->FreeValue10             = null;
        $fcAfterbuyArt->DeliveryTime            = null;
        $fcAfterbuyArt->ImageSmallURL           = null;
        $fcAfterbuyArt->ImageLargeURL           = null;
        $fcAfterbuyArt->ImageName               = null;
        $fcAfterbuyArt->ImageSource             = null;
        $fcAfterbuyArt->ManufacturerStandardProductIDType         = null;
        $fcAfterbuyArt->ManufacturerStandardProductIDValue         = null;
        $fcAfterbuyArt->ProductBrand            = $article['supplier']['name'];
        $fcAfterbuyArt->CustomsTariffNumber     = null;
        $fcAfterbuyArt->ManufacturerPartNumber  = null;
        $fcAfterbuyArt->GoogleProductCategory   = null;
        $fcAfterbuyArt->Condition               = null;
        $fcAfterbuyArt->Pattern                 = null;
        $fcAfterbuyArt->Material                = null;
        $fcAfterbuyArt->ItemColor               = null;
        $fcAfterbuyArt->ItemSize                = null;
        $fcAfterbuyArt->CanonicalUrl            = $this->getArticleSeoUrl($article['id']);
        $fcAfterbuyArt->EnergyClass             = null;
        $fcAfterbuyArt->EnergyClassPictureUrl   = null;
        $fcAfterbuyArt->Gender                  = null;
        $fcAfterbuyArt->AgeGroup                = null;

        return $fcAfterbuyArt;
    }

    private function  mapRequiredAfterbuyArticleAttributes($fcAfterbuyArt, $article, $detail)
    {
        $fcAfterbuyArt->Name                    = $article['name']; // String

        return $fcAfterbuyArt;
    }

    private function  mapImageAfterbuyArticleAttributes($fcAfterbuyArt, $article, $detail)
    {
        $i = 1;
        foreach ($article['images'] as $image){
            // only 12 pictures are supported by afterbuy
            if ($i > 12){
                continue;
            }
            $varName_PicNr = "ProductPicture_Nr_".$i;
            $varName_PicUrl = "ProductPicture_Url_".$i;
            $varName_PicAltText = "ProductPicture_AltText_".$i;

            $fcAfterbuyArt->{$varName_PicNr} = $i;
            $fcAfterbuyArt->{$varName_PicUrl} = $this->getImageSeoUrl($image['mediaId']);
            $fcAfterbuyArt->{$varName_PicAltText} = $image['path']; // ToDO better description??
        }
        return $fcAfterbuyArt;
    }

    /**
     * Internal helper function to convert gross prices to net prices.
     *
     * @param $prices
     * @param $tax
     *
     * @return array
     */
    protected function formatPricesFromNetToGross($prices, $tax)
    {
        foreach ($prices as $key => $price) {
            $customerGroup = $price['customerGroup'];
            if ($customerGroup['taxInput']) {
                $price['price'] = str_replace('.', ',', $price['price'] / 100 * (100 + $tax['tax']));
                $price['pseudoPrice'] = str_replace('.', ',',$price['pseudoPrice'] / 100 * (100 + $tax['tax']));
            } else {
                $price['price'] = str_replace('.', ',', $price['price']);
                $price['pseudoPrice'] = str_replace('.', ',',$price['pseudoPrice']);
            }
            // Use customerGroup Key as new key
            // ToDO what to do with non-standard customerGroups
            if ($customerGroup['key'] == 'H' ){
                $prices['H'] = $price;
            }
            if ($customerGroup['key'] == 'EK' ){
                $prices['EK'] = $price;
            }
        }

        return $prices;
    }

    /**
     * Adds afterbuy id to article dataset
     *
     * @param $sArticleOxid
     * @param $sResponse
     * @return void
     */
    protected function _fcAddAfterbuyIdToArticle($articleId, $response)
    {
        $oXml = simplexml_load_string($response);
        $productId = (string) $oXml->Result->NewProducts->NewProduct->ProductID;
        if ($productId) {
            $article = Shopware()->Models()->getRepository('Shopware\Models\Article\Article')->find($articleId);
            $articleAttributes = $article->getAttribute();
            $articleAttributes->setAfterbuyProductid($productId);
            Shopware()->Models()->persist($articleAttributes);
            Shopware()->Models()->flush();
        }
    }

    protected function getImageSeoUrl($mediaId)
    {
        $mediaService = Shopware()->Container()->get('shopware_media.media_service');
        $mediaRepo = Shopware()->Models()->getRepository('Shopware\Models\Media\Media');
        $mediaImage = $mediaRepo->findOneBy(array('id' => $mediaId));
        // ToDO Exception Handling

        return $mediaService->getUrl($mediaImage->getPath());
    }

    protected function getArticleSeoUrl($articleId)
    {

        // ToDo Host is empty??
        $host = Shopware()->Config()->BasePath;

        $db = Shopware()->Db();

        $sql = "SELECT path FROM s_core_rewrite_urls WHERE org_path = :org_path";
        $params = [ ":org_path" => "sViewport=detail&sArticle={$articleId}" ];

        $query = $db->executeQuery($sql, $params);
        $row = $query->fetch();

        // ToDo http / https support
        return isset($row['path']) ? "http://{$host}/{$row['path']}" : "";
    }
}
