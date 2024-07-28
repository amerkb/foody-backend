<?php

namespace App\Repository\Admin;

use App\Abstract\BaseRepositoryImplementation;
use App\ApiHelper\ApiResponseCodes;
use App\ApiHelper\ApiResponseHelper;
use App\ApiHelper\Result;
use App\Http\Resources\EmployeeResource;
use App\Interfaces\Admin\EmployeeInterface;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class EmployeeRepository extends BaseRepositoryImplementation implements EmployeeInterface
{
    public function model()
    {
        return User::class;
    }

    public function getemployees()
    {
        $emp = User::query()->where('active', 1)->get();
        $emp = EmployeeResource::collection($emp);

        return ApiResponseHelper::sendResponse(new Result($emp, 'table created'), ApiResponseCodes::CREATED);
    }

    public function storeemp($data)
    {
        $user = Auth::user();

        $emp = $this->create(array_merge($data, ['restaurant_id' => $user->id]));

        return ApiResponseHelper::sendResponse(new Result($emp, 'emp created'), ApiResponseCodes::CREATED);
    }

    public function updateemp($id, $data)
    {
        $emp = $this->updateById($id, $data);

        return ApiResponseHelper::sendResponse(new Result($emp, 'emp created'), ApiResponseCodes::CREATED);
    }

    public function deleteemp($id)
    {
        $emp = $this->deleteById($id);

        return ApiResponseHelper::sendMessageResponse('emp deleted');
    }

    public function activate($id)
    {
        $user = User::find($id);
        if ($user) {
            $user->active = ! $user->active;
            $user->save();

            return true;
        } else {
            return false;
        }
    }
}
