<?php

namespace FatchipAfterbuy\Subscriber;

use Enlight\Event\SubscriberInterface;
use FatchipAfterbuy\Services\ReadData\External\ReadOrdersService;
use FatchipAfterbuy\Services\ReadData\ReadDataInterface;
use FatchipAfterbuy\Services\WriteData\External\WriteOrdersService;
use FatchipAfterbuy\Services\WriteData\WriteDataInterface;
use Shopware\Components\Plugin\CachedConfigReader;

class Cron implements SubscriberInterface
{
    /**
     * @var ReadDataInterface
     */
    protected $readOrderStatusService;

    /**
     * @var WriteDataInterface
     */
    protected $writeOrderStatusService;

    /**
     * @var ReadOrdersService
     */
    protected $readOrderService;

    /**
     * @var WriteOrdersService
     */
    protected $writeOrderService;

    /**
     * @var ReadDataInterface
     */
    protected $readCategoriesService;

    /**
     * @var WriteDataInterface
     */
    protected $writeCategoriesService;

    /**
     * @var ReadDataInterface
     */
    protected $readProductsService;

    /**
     * @var WriteDataInterface
     */
    protected $writeProductsService;

    public function __construct(CachedConfigReader $configReader, string $pluginName)
    {
        $config = $configReader->getByPluginName($pluginName);

        //if afterbuy data carrying system
        if($config['mainSystem'] == 2) {
            $this->readOrderService = Shopware()->Container()->get('fatchip_afterbuy.services.read_data.internal.read_orders_service');
            $this->writeOrderService = Shopware()->Container()->get('fatchip_afterbuy.services.write_data.external.write_orders_service');

            $this->readCategoriesService = Shopware()->Container()->get('fatchip_afterbuy.services.read_data.external.read_categories_service');
            $this->writeCategoriesService = Shopware()->Container()->get('fatchip_afterbuy.services.write_data.internal.write_categories_service');

            $this->readProductsService = Shopware()->Container()->get('fatchip_afterbuy.services.read_data.external.read_products_service');
            $this->writeProductsService = Shopware()->Container()->get('fatchip_afterbuy.services.write_data.internal.write_products_service');
        }
        //shopware is data carrying system otherwise
        else {
            $this->readCategoriesService = Shopware()->Container()->get('fatchip_afterbuy.services.read_data.internal.read_categories_service');
            $this->writeCategoriesService = Shopware()->Container()->get('fatchip_afterbuy.services.write_data.external.write_categories_service');

            $this->readProductsService = Shopware()->Container()->get('fatchip_afterbuy.services.read_data.internal.read_products_service');
            $this->writeProductsService = Shopware()->Container()->get('fatchip_afterbuy.services.write_data.external.write_products_service');

            $this->readOrderStatusService = Shopware()->Container()->get('fatchip_afterbuy.services.read_data.internal.read_status_service');
            $this->writeOrderStatusService = Shopware()->Container()->get('fatchip_afterbuy.services.write_data.external.write_status_service');

            $this->readOrderService = Shopware()->Container()->get('fatchip_afterbuy.services.read_data.external.read_orders_service');
            $this->writeOrderService = Shopware()->Container()->get('fatchip_afterbuy.services.write_data.internal.write_orders_service');
        }
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return array(
            'Shopware_CronJob_AfterbuyUpdateProducts' => 'updateProducts',
            'Shopware_CronJob_AfterbuyUpdateOrders' => 'updateOrders'
        );
    }

    public function updateProducts(\Shopware_Components_Cron_CronJob $job)
    {
        $filter = array(
            'categories' => array(),
            'products' => array(
                'submitAll' => false
            )
        );
        $output = "";

        $categories = $this->readCategoriesService->get($filter['categories']);
        $output .= 'Got Categories: ' . count($categories). "\n";
        $result = $this->writeCategoriesService->put($categories);
        $output .= 'New Categories submitted: ' . count($result). "\n";

        $products = $this->readProductsService->get($filter['products']);
        $output .= 'Got Products: ' . count($products). "\n";
        $result = $this->writeProductsService->put($products);
        $output .= 'New Products submitted: ' . count($result). "\n";

        return $output;
    }

    public function updateOrders(\Shopware_Components_Cron_CronJob $job)
    {
        $filter = array();
        $output = "";

        if($this->readOrderStatusService && $this->writeOrderStatusService) {
            $orders = $this->readOrderStatusService->get($filter);
            $output .= 'Update order status: ' . count($orders) . "\n";
            $result = $this->writeOrderStatusService->put($orders);
        }

        $filter = $this->writeOrderService->getOrderImportDateFilter(false);

        $orders = $this->readOrderService->get($filter);
        $output .= 'Got orders: ' . count($orders). "\n";
        $result = $this->writeOrderService->put($orders);

        return $output;
    }



}