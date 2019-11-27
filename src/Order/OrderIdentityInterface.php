<?php

namespace AmoClient\Order;

/**
 * Interface OrderIdentityInterface
 * @package AmoClient\Order
 */
interface OrderIdentityInterface
{
    public function getCurrent();

    public function getNext();
}