<?php

namespace App\Http\Controllers;

use App\Models\Table;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    public function download($id)
    {
       $table= Table::find($id);
        return response()->file(public_path($table->Qr_code_path));



    }
}
