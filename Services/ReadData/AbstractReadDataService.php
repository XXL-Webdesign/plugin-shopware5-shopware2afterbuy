<?php

namespace viaebShopwareAfterbuy\Services\ReadData;

use viaebShopwareAfterbuy\Services\AbstractDataService;
use Shopware\Components\Model\ModelEntity;

/**
 * implements methods we should use in every ReadDataService
 *
 * Class AbstractReadDataService
 * @package viaebShopwareAfterbuy\Services\ReadData
 */
class AbstractReadDataService extends AbstractDataService {

    /**
     * @var ModelEntity
     */
    protected $sourceRepository;

    /**
     * @var string
     */
    protected $targetEntity;


    /**
     * @param string $repo
     */
    public function setRepo(string $repo) {
        $this->sourceRepository = $repo;
    }

    public function setTarget(string $target) {
        $this->targetEntity = $target;
    }
}