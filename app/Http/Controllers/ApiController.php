<?php

namespace App\Http\Controllers;

use App\Sheerdata;
use Illuminate\Http\Request;

class ApiController extends Controller
{
    public function getDataByDate($date)
    {
        $columnName = 'dd';
        $data = Sheerdata::where($columnName, $date)->get();

        return response()->json(['data' => $data]);
    }
}
