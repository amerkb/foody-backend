<?php

namespace App\Interfaces\user;

use App\Http\Requests\User\makeOrderRequest;

interface OrderInterface
{
    public function makeOrder(makeOrderRequest $request);
}
