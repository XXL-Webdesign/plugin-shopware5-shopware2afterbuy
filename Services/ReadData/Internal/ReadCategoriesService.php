<?php

namespace viaebShopwareAfterbuy\Services\ReadData\Internal;

use viaebShopwareAfterbuy\Services\Helper\ShopwareCategoryHelper;
use viaebShopwareAfterbuy\Services\ReadData\AbstractReadDataService;
use viaebShopwareAfterbuy\Services\ReadData\ReadDataInterface;
use viaebShopwareAfterbuy\ValueObjects\Category as ValueCategory;
use Shopware\Models\Category\Category as ShopwareCategory;

class ReadCategoriesService extends AbstractReadDataService implements ReadDataInterface
{
    /**
     * @var string $identifier
     */
    protected $identifier;

    /**
     * @var bool $isAttribute
     */
    protected $isAttribute;

    /**
     * @param array $filter
     *
     * @return ValueCategory[]
     */
    public function get(array $filter)
    {
        $data = $this->read($filter);

        return $this->transform($data);
    }

    /**
     * transforms api input into valueObject (targetEntity)
     *
     * @param ShopwareCategory[] $shopwareCategories
     *
     * @return ValueCategory[]
     */
    public function transform(array $shopwareCategories)
    {
        $this->logger->debug('Receiving categories from shop', $shopwareCategories);

        if ($this->targetEntity === null) {

            $this->logger->error('No target entity defined!', ['Categories', 'Read', 'Internal']);

            return null;
        }

        $this->logger->info('Got ' . count($shopwareCategories) . ' items', ['Categories', 'Read', 'Internal']);

        $valueCategories = [];

        foreach ($shopwareCategories as $shopwareCategory) {
            /** @var ValueCategory $valueCategory */
            $valueCategory = new $this->targetEntity();

            $valueCategory->setParentIdentifier($shopwareCategory->getParentId());
            $valueCategory->setName($shopwareCategory->getName());
            $valueCategory->setPosition($shopwareCategory->getPosition());
            $valueCategory->setDescription($shopwareCategory->getMetaDescription());
            $valueCategory->setCmsText($shopwareCategory->getCmsText());
            $valueCategory->setActive($shopwareCategory->getActive());
            $valueCategory->setInternalIdentifier($shopwareCategory->getId());

            if($shopwareCategory->getAttribute()) {
                $valueCategory->setExternalIdentifier($shopwareCategory->getAttribute()->getAfterbuyCatalogId());
            }

            $valueCategory->setPath($shopwareCategory->getPath());

            if ($valueCategory->isValid()) {
                $valueCategories[] = $valueCategory;
            } else {
                $this->logger->error('Error storing category', array($valueCategory));
            }
        }

        return $valueCategories;
    }


    /**
     * provides api data. dummy data as used here can be used in tests
     *
     * @param array $filter
     *
     * @return ShopwareCategory[]
     */
    public function read(array $filter)
    {
        /**
         * @var ShopwareCategoryHelper $categoryHelper
         */
        $categoryHelper = $this->helper;

        return $categoryHelper->getFilteredCategoryList();
    }
}
