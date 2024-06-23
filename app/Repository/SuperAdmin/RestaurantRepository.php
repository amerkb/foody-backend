<?php

namespace App\Repository\SuperAdmin;

use App\Abstract\BaseRepositoryImplementation;
use App\ApiHelper\ApiResponseCodes;
use App\ApiHelper\ApiResponseHelper;
use App\ApiHelper\Result;
use App\Interfaces\SuperAdmin\RestaurantInterface;
use App\Models\Restaurant;
use Illuminate\Support\Facades\Hash;

class RestaurantRepository extends BaseRepositoryImplementation implements RestaurantInterface
{
    public function model()
    {
        return Restaurant::class;
    }

    public function createRestaurant($data)
    {
        $password = rand(0000, 9999);
        $restaurant = $this->create(array_merge($data, ['password' => Hash::make($password)]));

        return ApiResponseHelper::sendResponse(new Result($restaurant, 'Done'), ApiResponseCodes::CREATED);
    }
}
