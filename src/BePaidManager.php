<?php

namespace AmoClient;

use AmoClient\Order\Order;

/**
 * Class BePaidManager
 * @property string|int $market_id
 * @property string     $market_secret_key
 */
class BePaidManager extends AbstractManager
{

    const DEFAULT_HEADERS = [
        'Accept: application/json',
        'Content-type: application/json',
    ];

    /**
     * default:  type => string
     */
    protected static $PROPERTIES_MAP = [
        'api_version' => ['type' => 'numeric', 'default' => 2.1],
        'is_test' => ['type' => 'bool', 'default' => false],
        'checkout_url' => ['type' => 'string'],
        'market_id' => ['type' => 'numeric'],
        'market_secret_key' => ['type' => 'string'],
        'attempts' => ['type' => 'int', 'default' => 1],
        'success_url' => ['type' =>'string'],
        'decline_url' => ['type' =>'string'],
        'fail_url' => ['type' =>'string'],
        'cancel_url' => ['type' =>'string'],
        'notification_url' => ['type' =>'string'],
        'language' => ['type' => 'string', 'default' => 'ru'],
        'currency' => ['type' => 'string', 'default' => 'BYN'],
        'payment_lifetime' => ['type' => 'int', 'default' => 10],
    ];

    /**
     * AmoCrmManager constructor.
     * @param array $config
     * @throws \Exception
     */
    public function __construct(array $config = [])
    {
        parent::__construct($config);

        $this
            ->httpClient
            ->setHeaders(self::DEFAULT_HEADERS)
            ->addBasicAuth(
                (string) $this->prop('market_id'),
                (string) $this->prop('market_secret_key')
            );
    }

    /**
     * @param Order $order
     * @return array|bool|mixed|object
     * @throws Exception
     * @throws \Exception
     */
    public function createPaymentToken(Order $order)
    {
        $response = $this->httpClient->sendPost(
            $this->prop('checkout_url'),
            [
                'checkout' =>
                    [
                        'version' => $this->prop('api_version'),
                        'test' => $this->prop('is_test'),
                        'transaction_type' => 'payment',
                        'attempts' => $this->prop('attempts'),
                        'settings' => [
                            'success_url' => $this->prop('success_url'). '&tracking_id=' . $order->getTrackingId(),
                            'decline_url' => $this->prop('decline_url'). '&tracking_id=' . $order->getTrackingId(),
                            'fail_url' => $this->prop('fail_url'). '&tracking_id=' . $order->getTrackingId(),
                            'cancel_url' => $this->prop('cancel_url'). '&tracking_id=' . $order->getTrackingId(),
                            'notification_url' => $this->prop('notification_url'). '&tracking_id=' . $order->getTrackingId(),
                            'language' => $this->prop('language'),
                            'customer_fields' => [
                                'visible' => ['first_name', 'last_name', 'email'],
                                'read_only' => ['first_name', 'last_name', 'email'],
                            ],
                        ],
                        'order' => [
                            'currency' => $this->prop('currency'),
                            'amount' => $order->getBepaidPrice(),
                            'description' => $order->getBepaidDescription(),
                            'tracking_id' => $order->getTrackingId(),
                            'expired_at' => $this->getPayExpireDt(),
                        ],
                        'customer' => $order->getCustomerBepaidData(),
                    ],
            ]
        );

        if (false === $response || !isset($response['checkout']['redirect_url'])) {
            return false;
        }

        return $response;
    }

    /**
     * @param $transactionId
     * @return bool
     */
    public function checkTransaction($transactionId)
    {
        $response =  $this
            ->httpClient
            ->setHeaders([])
            ->sendGet('https://gateway.bepaid.by/v2/transactions/tracking_id/'.$transactionId);

        return $response['transactions'][0]['status'] ?? false;
    }

    /**
     * @return string
     * @throws \Exception
     */
    public function getPayExpireDt(): string
    {
        return (new \DateTime())
            ->modify(
                \sprintf(
                    '+%d minutes',
                    $this->prop('payment_lifetime')
                )
            )
            ->format('c');
    }
}