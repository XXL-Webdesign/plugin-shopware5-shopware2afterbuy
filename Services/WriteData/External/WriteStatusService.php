<?php

namespace abaccAfterbuy\Services\WriteData\External;

use Fatchip\Afterbuy\ApiClient;
use abaccAfterbuy\Components\Helper;
use abaccAfterbuy\Services\WriteData\AbstractWriteDataService;
use abaccAfterbuy\Services\WriteData\WriteDataInterface;
use abaccAfterbuy\ValueObjects\Category as ValueCategory;
use abaccAfterbuy\ValueObjects\OrderStatus;

class WriteStatusService extends AbstractWriteDataService implements WriteDataInterface
{

    /**
     * @param ValueCategory[] $valueCategories
     *
     * @return array|null
     */
    public function put(array $valueCategories) :?array
    {
        $catalogs = $this->transform($valueCategories);

        return $this->send($catalogs);
    }

    public function transform(array $orders)
    {
        $this->logger->debug('Storing ' . count($orders) . ' items.', array($orders));

        if(empty($orders)) {
            return array();
        }

        $content = array(
            'Orders' => array()
        );

        foreach($orders as $order) {
            /**
             * @var OrderStatus $order
             */

            $content['Orders'][] = array(
                'Order' => array(
                    'OrderID' => $order->getAfterbuyOrderId(),
                    'PaymentInfo' => array(
                        'PaymentDate' => date_format($order->getPaymentDate(), 'd.m.Y H:i:s'),
                        'AlreadyPaid' => Helper::convertNumberToABString($order->getAmount())
                    ),
                    'ShippingInfo' => array(
                        'DeliveryDate' => date_format($order->getShippingDate(), 'd.m.Y H:i:s')
                    )
                )
            );
        }

        return $content;
    }

    /**
     * @param [] $catalogs
     *
     * @return array
     */
    public function send($orders) :?array
    {
        /** @var ApiClient $api */
        $api = new ApiClient($this->apiConfig, $this->logger);

        if(is_array($orders) && count($orders)) {
            $response = $api->updateOrderStatus($orders);

            if ($response['CallStatus'] === 'Error') {
                $this->logger->error('Error submitting data', $response);
            }
        }

        $this->storeSubmissionDate('lastStatusExport');

        return array();
    }
}
