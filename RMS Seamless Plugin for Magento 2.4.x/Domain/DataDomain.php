<?php

namespace RazerPay\Payment\Domain;

class DataDomain
{
    protected const CHANNELS_CACHE_IDENTIFIER = 'razerpay_payment_channels';

    protected \Magento\Framework\App\CacheInterface $magentoCache;

    protected \Magento\Framework\Api\SearchCriteriaBuilder $magentoApiSearchCriteriaBuilder;

    protected \Magento\Sales\Api\OrderRepositoryInterface $magentoSalesOrderRepository;

    protected \RazerPay\Payment\Domain\Api\GetChannelStatusRequestFactory $getChannelStatusRequestFactory;

    protected \RazerPay\Payment\Gateway\Config\Config $paymentGatewayConfig;

    public function __construct(
        \Magento\Framework\App\CacheInterface $magentoCache,
        \Magento\Framework\Api\SearchCriteriaBuilder $magentoApiSearchCriteriaBuilder,
        \Magento\Sales\Api\OrderRepositoryInterface $magentoSalesOrderRepository,
        \RazerPay\Payment\Domain\Api\GetChannelStatusRequestFactory $getChannelStatusRequestFactory,
        \RazerPay\Payment\Gateway\Config\Config $paymentGatewayConfig
    ) {
        $this->magentoCache = $magentoCache;
        $this->magentoApiSearchCriteriaBuilder = $magentoApiSearchCriteriaBuilder;
        $this->magentoSalesOrderRepository = $magentoSalesOrderRepository;
        $this->getChannelStatusRequestFactory = $getChannelStatusRequestFactory;
        $this->paymentGatewayConfig = $paymentGatewayConfig;
    }

    /**
     * @return array<int, array{title: string, request_code: string, response_code: string, logo: string}>
     */
    public function getAvailableChannels(): array
    {
        if ($this->paymentGatewayConfig->isMerchantInfoFilled() === false) {
            return [];
        }

        $channelsCacheIdentifierPerMerchant = static::CHANNELS_CACHE_IDENTIFIER.'_'.$this->paymentGatewayConfig->getMerchantId();

        $channels = json_decode($this->magentoCache->load($channelsCacheIdentifierPerMerchant), true);
        if ($channels) {
            return $channels;
        }

        try {
            /**
             * @var \RazerPay\Payment\Domain\Api\GetChannelStatusRequest $request
             */
            $request = $this->getChannelStatusRequestFactory->create();
            $response = $request->send();
        } catch (\GuzzleHttp\Exception\ClientException $exception) {
            return [];
        }

        $channels = array_map(
            function (array $channel) {
                return [
                    'title' => $channel['title'],
                    'request_code' => $channel['channel_map']['seamless']['request'],
                    'response_code' => $channel['channel_map']['seamless']['response'],
                    'logo' => $channel['logo_url_120x43'],
                    'type' => $channel['channel_type'],
                ];
            },
            $response['result'] ?? []
        );

        // filter out invalid channels
        $channels = array_filter($channels, function (array $channel) {
            return $channel['request_code'] && $channel['response_code'];
        });

        // remove duplicate channels
        $channels = array_unique($channels, SORT_REGULAR);

        $channels = array_values($channels);

        $this->magentoCache->save(
            json_encode($channels),
            $channelsCacheIdentifierPerMerchant,
            [\Magento\Framework\App\Cache\Type\Collection::CACHE_TAG],
            604800 //1 week
        );

        return $channels;
    }

    public function getEnabledChannels()
    {
        $enabledChannelCodes = $this->paymentGatewayConfig->getEnabledChannelCodes();

        $enabledChannels = array_Filter(
            $this->getAvailableChannels(),
            function (array $channel) use ($enabledChannelCodes) {
                return in_array($channel['request_code'], $enabledChannelCodes);
            }
        );

        return array_values($enabledChannels);
    }

    public function getChannel(
        string $code
    ): ?array {
        foreach ($this->getAvailableChannels() as $channel) {
            if ($channel['request_code'] === $code) {
                return $channel;
            }
        }

        return null;
    }

    public function getChannelTitle(
        string $code
    ): ?string {
        return $this->getChannel($code)['title'] ?? null;
    }

    public function checkChannelIsCC(
        string $code
    ): bool {
        return $this->getChannel($code)['type'] === $this->paymentGatewayConfig::CREDIT_CHANNEL_TYPE_CODE;
    }

    /**
     * @param  string  $orderIncrementId
     *
     * @return \Magento\Sales\Model\Order|false
     */
    public function getSalesOrderByIncrementId(
        string $orderIncrementId
    ) {
        $salesOrderSearchCriteria = $this->magentoApiSearchCriteriaBuilder->addFilter(
            \Magento\Sales\Api\Data\OrderInterface::INCREMENT_ID,
            $orderIncrementId
        )->create();

        $salesOrderCollection = $this->magentoSalesOrderRepository->getList($salesOrderSearchCriteria)->getItems();

        return reset($salesOrderCollection);
    }
}
