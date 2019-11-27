<?php

namespace AmoClient\Order;

/**
 * Count from file
 * Class OrderIdentity
 * @package AmoClient\Order
 */
class OrderIdentity implements OrderIdentityInterface
{
    const PERMANENT_MEMORY_FILE = __DIR__ . '/../../order_counter.cache';

    /**
     * @return int
     */
    public function getCurrent()
    {
        return (int) file_get_contents(self::PERMANENT_MEMORY_FILE);
    }

    /**
     * @return int
     */
    public function getNext()
    {
        $curr = self::getCurrent();

        $curr += 1;

        \file_put_contents(self::PERMANENT_MEMORY_FILE, $curr);

        return $curr;
    }

    protected static function  checkFile()
    {
        if (!\file_exists(self::PERMANENT_MEMORY_FILE)) {
            \file_put_contents(self::PERMANENT_MEMORY_FILE, 0);
        }
    }
}