<?php

namespace App\Interfaces\Admin;

interface OrderInterface
{
    public function createOrder($data);

    public function showOrder($id);

    public function updateOrder($data, $id);

    public function deleteOrder($id);
}
