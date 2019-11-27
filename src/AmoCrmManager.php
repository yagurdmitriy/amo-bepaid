<?php

namespace AmoClient;

use AmoClient\Order;

/**
 * Class AmoCrmManager
 */
class AmoCrmManager extends AbstractManager
{
    const API_URL = 'http://{some_sub_domain}.amocrm.ru/api/v2';

    const CUSTOM_FIELD_PHONE_ID = 52535;
    const CUSTOM_FIELD_EMAIL_ID = 52537;
    const CUSTOM_FIELD_PAY_FORM_ID = 52567;
    const CUSTOM_FIELD_PAY_STATUS_ID = 53703;
    const CUSTOM_FIELD_PRODUCT_ID = 56919;
    const CUSTOM_FIELD_TRACKING_ID = 52565;

    const PAY_STATUS_NOT_PAID = 84375;
    const PAY_STATUS_PAID = 84377;
    const PAY_STATUS_REJECTED = 84379;
    const PAY_STATUS_NOT_COMPLETE = 84381;

    const FORM_STATUS_ONLINE = 82733;
    const FORM_STATUS_OFFLINE = 82735;

    const PRODUCT_ONLINE_PROJECT_ALL_INCLUSIVE = 89275;
    const PRODUCT_ONLINE_PROJECT_GROUP = 89277;
    const PRODUCT_ONLINE_PROJECT_ALL_BY_HERSELF = 89279;
    const PRODUCT_NUTRITION_PROGRAM_INDIVIDUAL = 89281;
    const PRODUCT_VIDEO_TRAINING = 89283;
    const PRODUCT_BOOK_TURBO_MENU = 89285;
    const PRODUCT_ONLINE_PROJECT_ALL_INCLUSIVE_DISCOUNT = 182299;

    const PIPELINE_STATUS_PAID = 23433937;
    const PIPELINE_STATUS_REQUEST = 23433934;

    const DEFAULT_RESPONSIBLE_USER_ID = 3005692;

    const DEFAULT_HEADERS = [
        'Accept: application/json',
        'Content-type: application/json',
    ];

    protected static $PROPERTIES_MAP = [
        'login' => ['type' => 'string'],
        'password' => ['type' => 'string'],
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
                (string)$this->prop('login'),
                (string)$this->prop('password')
            );
    }

    /**
     * @param $trackCode
     * @return bool
     * @throws \Exception
     */
    public function getLeadByTrack($trackCode)
    {
        $offset = 0;

        while(true) {
            if ($offset > 10000) {
                return false;
            }

            $response = $this->httpClient->sendGet(
                self::API_URL.'/api/v2/leads',
                [
                    'limit_rows' => 10000,
                    'limit_offset' => $offset,
                ]
            );
            $offset+=500;


            if (isset($response['_embedded']['items']) && \count($response['_embedded']['items'])) {
                $items = $response['_embedded']['items'];

                $validItems = AmoFilterByCustomFields::filter(
                    $items,
                    [
                        ['id' => self::CUSTOM_FIELD_TRACKING_ID, 'condition' => ['value' => $trackCode]],
                    ]
                );

                if (\count($validItems) === 0) {
                    continue;
//                return false;
                }

                if (\count($validItems) > 1) {
                    //Todo: Why items > 1
                }

                $itemV = \array_shift($validItems);

                return $itemV['id'];
            } else {
                return false;
            }
        }
    }

    /**
     * @param $contactId
     * @param Order $order
     * @return array|bool
     * @throws \Exception
     */
    public function searchLead($contactId, Order $order)
    {
        $offset = 0;
        while(true) {
            if ($offset > 10000) {
                return false;
            }
            $response = $this->httpClient->sendGet(
                self::API_URL.'/api/v2/leads',
                [
                    'limit_rows' => 10000,
                    'limit_offset' => $offset,
                ]
            );
            $offset+=500;

            if (isset($response['_embedded']['items']) && \count($response['_embedded']['items'])) {
                $items = $response['_embedded']['items'];

                /** filter by contact Id */
                $items = \array_filter($items, function ($item) use ($contactId) {
                    return $item['main_contact']['id'] == $contactId;
                });

                $validItems = AmoFilterByCustomFields::filter(
                    $items,
                    [
                        ['id' => self::CUSTOM_FIELD_PRODUCT_ID, 'condition' => ['enum' => $order->getAmoProductId()]],
                        ['id' => self::CUSTOM_FIELD_PAY_STATUS_ID, 'condition' => ['enum' => [self::PAY_STATUS_NOT_PAID, self::PAY_STATUS_REJECTED, self::PAY_STATUS_NOT_COMPLETE]]],
                    ]
                );

                if (\count($validItems) === 0) {
                    continue;
//                    return false;
                }

                if (\count($validItems) > 1) {
                    //Todo: Why found > 1
                }

                $itemV = \array_shift($validItems);

                return [
                    'lead_id' => $itemV['id'],
                    'pay_status' => AmoFilterByCustomFields::getCustomField(self::CUSTOM_FIELD_PAY_STATUS_ID, $itemV, 'enum'),
                    'tracking_id' => AmoFilterByCustomFields::getCustomField(self::CUSTOM_FIELD_TRACKING_ID, $itemV),
                ];
            } else {
                return false;
            }
        }
    }

    /**
     * @param $contactId
     * @param Order $order
     * @param int $formStatus
     * @return bool
     * @throws \Exception
     */
    public function createLead($contactId, Order $order, $formStatus = self::FORM_STATUS_ONLINE)
    {
        $response = $this
            ->httpClient
            ->addOpt(CURLOPT_USERAGENT, 'amoCRM-API-client-undefined/2.0')
            ->sendPost(
                self::API_URL.'/api/v2/leads',
            [
                'add' => [
                    [
                        'name' => $order->getAmoLeadTitle(),
                        'sale' => $order->getPrice(),
                        'status_id' => self::PIPELINE_STATUS_REQUEST,
                        'responsible_user_id' => self::DEFAULT_RESPONSIBLE_USER_ID,
                        'contacts_id' => [
                            $contactId,
                        ],
                        'custom_fields' => [
                            $this->buildCustomFiled(self::CUSTOM_FIELD_PAY_FORM_ID, $formStatus),
                            $this->buildCustomFiled(self::CUSTOM_FIELD_PAY_STATUS_ID, self::PAY_STATUS_NOT_COMPLETE),
                            $this->buildCustomFiled(self::CUSTOM_FIELD_PRODUCT_ID, $order->getAmoProductId()),
                            $this->buildCustomFiled(self::CUSTOM_FIELD_TRACKING_ID, $order->getTrackingId()),
                        ],
                    ],
                ],
            ]
        );

        return $response['_embedded']['items'][0]['id'] ?? false;
    }

    /**
     * @param $id
     * @param array $fields
     * @param array $customFields
     * @return bool
     */
    public function updateLead($id, array $fields = [], array $customFields = [])
    {
        $update = [
            'id' => $id,
            'updated_at' => (string) time()
        ];

        if ($fields) {
            $update = \array_merge($update, $fields);
        }

        if ($customFields) {
            $update['custom_fields'] = \array_map(function($k, $v) {
                return $this->buildCustomFiled($k, $v);
            }, \array_keys($customFields), $customFields);
        }

        $response = $this
            ->httpClient
            ->addOpt(CURLOPT_USERAGENT, 'amoCRM-API-client-undefined/2.0')
            ->sendPost(
                self::API_URL.'/api/v2/leads/',
                [
                    'update' => [
                        $update,
                    ],
                ]
            );

        return $response['_embedded']['items'][0]['id'] ?? false;
    }

    /**
     * @param Order $order
     * @return bool
     * @throws \Exception
     */
    public function getContact(Order $order)
    {
        $offset = 0;
        while(true) {
            if ($offset > 10000) {
                return false;
            }
            $response = $this
                ->httpClient
                ->sendGet(
                    self::API_URL.'/api/v2/contacts/',
                    [
                        'limit_rows' => 10000,
                        'limit_offset' => $offset,
                    ]
                );
            $offset += 500;

            if (isset($response['_embedded']['items']) && \count($response['_embedded']['items'])) {
                $items = $response['_embedded']['items'];

                $phoneItems  = AmoFilterByCustomFields::filter(
                    $items,
                    [
                        ['id' => self::CUSTOM_FIELD_PHONE_ID, 'condition' => ['value' => $order->getPhone()]],
                    ]
                );

                $emailItems = AmoFilterByCustomFields::filter(
                    $items,
                    [
                        ['id' => self::CUSTOM_FIELD_EMAIL_ID, 'condition' => ['value' => $order->getEmail()]],
                    ]
                );

                if (0 === \count($phoneItems) && 0 === \count($emailItems)) {
                    continue;
//                    return false;
                }

                if (\count($phoneItems) > 0 || \count($emailItems) > 0) {
                    //TODO: Does this mean that the user has changed email, entered another with the same phone?
                }

                $validItems = \count($phoneItems) ===  0 ?  $emailItems : $phoneItems;


                $itemV = \array_shift($validItems);

                return $itemV['id'];
            } else {
                return false;
            }

        }
    }

    /**
     * @param Order $order
     * @return bool
     * @throws \Exception
     */
    public function createContact(Order $order)
    {
        $response = $this
            ->httpClient
            ->addOpt(CURLOPT_USERAGENT, 'amoCRM-API-client-undefined/2.0')
            ->sendPost(
                self::API_URL.'/api/v2/contacts',
                [
                    'add' => [
                        [
                            'name' => $order->getFullName(),
                            'responsible_user_id' => self::DEFAULT_RESPONSIBLE_USER_ID,
                            'custom_fields' => [
                                $this->buildCustomFiled(self::CUSTOM_FIELD_EMAIL_ID, $order->getEmail(), 'WORK'),
                                $this->buildCustomFiled(self::CUSTOM_FIELD_PHONE_ID, $order->getPhone(), 'WORK'),
                            ],
                        ],
                    ],
                ]
            );

        return $response['_embedded']['items'][0]['id'] ?? false;
    }

    /**
     * @param $id
     * @param $value
     * @param string $enum
     * @return array
     */
    private function buildCustomFiled($id, $value, $enum = ''): array
    {
        $valueItem = [
            'value' => $value,
        ];

        if($enum) {
            $valueItem['enum'] = $enum;
        }

        return [
            'id' => $id,
            'values' => [
                $valueItem
            ],
        ];
    }
}