<?php
/** @noinspection SpellCheckingInspection */

namespace viaebShopwareAfterbuy\Commands;

use viaebShopwareAfterbuy\Services\ReadData\ReadDataInterface;
use viaebShopwareAfterbuy\Services\WriteData\WriteDataInterface;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Shopware\Commands\ShopwareCommand;

class ExportOrders extends ShopwareCommand
{
    /**
     * @var ReadDataInterface
     */
    protected $readDataService;

    /**
     * @var WriteDataInterface
     */
    protected $writeDataService;

    /**
     * @param ReadDataInterface $readDataService
     * @param WriteDataInterface $writeDataService
     */
    public function __construct(ReadDataInterface $readDataService, WriteDataInterface $writeDataService) {
        parent::__construct();

        $this->readDataService = $readDataService;
        $this->writeDataService = $writeDataService;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('Afterbuy:Export:Orders')
            ->setDescription('Submit orders to Afterbuy')
            ->setHelp(<<<EOF
The <info>%command.name%</info> implements a command.
EOF
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /**
         * Structure for receiving and writing data
         * Should look everywhere the same.
         * Dependenciies are handeld via services.xml
         */

        /**
         * filter array is unused yet but can be implemented
         */
        $filter = array();

        $data = $this->readDataService->get($filter);
        $output->writeln('Got Orders: ' . count($data));
        $result = $this->writeDataService->put($data);
        $output->writeln('New orders submitted: ' . count($result));
    }
}
