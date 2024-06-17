<?php

namespace App\Repository\Chef;

use App\Abstract\BaseRepositoryImplementation;
use App\ApiHelper\ApiResponseHelper;
use App\ApiHelper\Result;
use App\Http\Resources\OrderResource;
use App\Interfaces\Chef\OrderInterface;
use App\Models\Order;
use App\Statuses\OrderStatus;
use Illuminate\Support\Facades\Auth;

class OrderRepository extends BaseRepositoryImplementation implements OrderInterface
{
    public function getOrder()
    {
        $orders = Order::with(['orderDetail', 'table'])->where('status', OrderStatus::PENDING)->orWhere(function ($query) {
            $query->where('status', '=', OrderStatus::START_PREPARING)->Where('chef_id', '=', Auth::id());
        })->get();
        $orders = OrderResource::collection($orders);

        return ApiResponseHelper::sendResponse(
            new Result($orders, 'Done')
        );
    }

    public function changeStatusForStartPreparing(int $order_id)
    {
        $this->updateById($order_id, ['status' => OrderStatus::START_PREPARING, 'chef_id' => Auth::id()]);

        return ApiResponseHelper::sendMessageResponse(
            'update status order for start preparing'
        );
    }

    public function changeStatusForEndPreparing(int $order_id)
    {
        $this->updateById($order_id, ['status' => OrderStatus::END_PREPARING]);

        return ApiResponseHelper::sendMessageResponse(
            'update status order for end preparing'
        );
    }

    public function model()
    {
        return Order::class;
    }
}
