<?php

namespace AmoClient\Order;

use AmoClient\AmoCrmManager;
use AmoClient\PropertiesBehaviorTrait;

/**
 * Class Order
 */
class Order
{
    use PropertiesBehaviorTrait;

    private const IDENTITY_BEHAVIOR = 'permanent_file';

    private $trackingId = null;

    const MAP_PRODUCTS_TO_PRICE = [
        AmoCrmManager::PRODUCT_ONLINE_PROJECT_ALL_INCLUSIVE => 200,
        AmoCrmManager::PRODUCT_ONLINE_PROJECT_ALL_INCLUSIVE_DISCOUNT => 180,
        AmoCrmManager::PRODUCT_ONLINE_PROJECT_GROUP => 100,
        AmoCrmManager::PRODUCT_ONLINE_PROJECT_ALL_BY_HERSELF => 50,
        AmoCrmManager::PRODUCT_NUTRITION_PROGRAM_INDIVIDUAL => 200,
        AmoCrmManager::PRODUCT_VIDEO_TRAINING => 60,
        AmoCrmManager::PRODUCT_BOOK_TURBO_MENU => 20,
    ];

    /**
     * default:  type => string
     */
    protected static $PROPERTIES_MAP = [
        'payment-name' => ['type' => [self::class, 'validName']],
        'payment-subname' => ['type' => [self::class, 'validSName']],
        'payment-phone' => ['type' => [self::class, 'validPhone']],
        'payment-email' => ['type' => [self::class, 'validEmail']],
        'payment-check' => ['type' => [self::class, 'validCheck']],
        'payment-product-id' => ['type' => [self::class, 'validProduct']],
        'payment-desc' => ['type' => 'string'],
        'payment-product-title' => ['type' => 'string'],
    ];

    protected static function validName($val)
    {
        return true;
    }

    protected static function validSName($val)
    {
        return true;
    }

    protected static function validPhone($val)
    {
        return true;
    }

    protected static function validEmail($val)
    {
        return true;
    }

    protected static function validCheck($val)
    {
        return true == $val;
    }

    protected static function validProduct($val)
    {
        return \in_array($val, \array_keys(self::MAP_PRODUCTS_TO_PRICE));
    }

    public function __construct($props)
    {
        $this->properties = $props;
    }

    /**
     * @param array $post
     * @return self|false
     * @throws \Exception
     */
    public static function fromPost(array $post)
    {
        $prop = static::prepareProperties($post);

        if(static::hasErrors()) {
            return false;
        }

        return new self($prop);
    }

    /**
     * @return int
     * @throws \Exception
     */
    public function getBepaidPrice(): int
    {
        return self::MAP_PRODUCTS_TO_PRICE[$this->prop('payment-product-id')] * 100;
    }

    /**
     * @return int
     * @throws \Exception
     */
    public function getPrice(): int
    {
        return self::MAP_PRODUCTS_TO_PRICE[$this->prop('payment-product-id')];
    }

    /**
     * @return string
     * @throws \Exception
     */
    public function getBepaidDescription(): string
    {
        return $this->prop('payment-desc');
    }

    /**
     * @return string
     * @throws \Exception
     */
    public function getTrackingId(): string
    {
        if (null === $this->trackingId) {
            $this->trackingId = $this->getNextTrack();
        }

        return $this->trackingId;
    }

    /**
     * @return int
     * @throws \Exception
     */
    private function getNextTrack()
    {
        switch (self::IDENTITY_BEHAVIOR) {
            case 'file':
                return (new OrderIdentity())->getNext();
            default:
                throw new \Exception('Fail config!');
        }
    }

    /**
     *
     * Can be set, only before call getTrackingId()!
     * @param $trackingId
     */
    public function setTrackingId($trackingId)
    {
        if (null === $this->trackingId) {
            $this->trackingId = $trackingId;
        }
    }

    /**
     * @return string
     * @throws \Exception
     */
    public function getFullName(): string
    {
        return \vsprintf('%s %s', [
            $this->prop('payment-name'),
            $this->prop('payment-subname'),
        ]);
    }

    /**
     * @return string
     * @throws \Exception
     */
    public function getEmail(): string
    {
        return $this->prop('payment-email');
    }

    /**
     * @return string
     * @throws \Exception
     */
    public function getPhone(): string
    {
        return $this->prop('payment-phone');
    }

    /**
     * @return mixed
     * @throws \Exception
     */
    public function getAmoProductId()
    {
        return $this->prop('payment-product-id');
    }

    /**
     * @throws \Exception
     */
    public function getAmoLeadTitle()
    {
        return \vsprintf('%s #%s', [
            $this->prop('payment-product-title'),
            $this->getTrackingId()
        ]);
    }

    /**
     * @return array
     * @throws \Exception
     */
    public function getCustomerBepaidData(): array
    {
        return [
            'first_name' => $this->prop('payment-name'),
            'last_name' => $this->prop('payment-subname'),
            'email' => $this->prop('payment-email'),
            'phone' => $this->prop('payment-phone')
        ];
    }

}