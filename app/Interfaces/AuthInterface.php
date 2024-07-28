<?php

namespace App\Interfaces;

use App\Http\Requests\AuthRequest;

interface AuthInterface
{
    public function loginRestaurant(AuthRequest $request);

    public function loginEmployee(AuthRequest $request);

    public function loginAdmin(AuthRequest $request);
}
