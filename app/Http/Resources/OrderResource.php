<?php

namespace App\Http\Resources;

use App\Models\User;
use App\Statuses\UserStatus;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;

class OrderResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $user = Auth::user();
        if ($user instanceof User && $user->user_type == UserStatus::CHEF) {
            return [
                'order_id' => $this->id,
                'table_name' => $this->table->table_num,
                'estimated_time' => $this->estimated_time,
                'status' => $this->status,
                'meals' => OrderDetailResource::collection($this->orderDetail),
            ];
        } elseif ($user instanceof User && $user->user_type == UserStatus::WAITER) {
            return [
                'order_id' => $this->id,
                'table_name' => $this->table->table_num,
                'estimated_time' => $this->estimated_time,
                'status' => $this->status,
            ];
        }

        return [];
    }
}
