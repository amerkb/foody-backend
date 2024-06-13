<?php

namespace App\Repository\User;

use App\Abstract\BaseRepositoryImplementationForMoreThanModel;
use App\Http\Requests\User\makeOrderRequest;
use App\Interfaces\user\OrderInterface;

class OrderRepository extends BaseRepositoryImplementationForMoreThanModel implements OrderInterface
{
    public function makeOrder(makeOrderRequest $request)
    {

    }
}
