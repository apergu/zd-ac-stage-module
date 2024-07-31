<?php

namespace App\Http\Controllers\Global;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class GlobalFunctionController extends Controller
{
    //

    public static function findFieldValueByKey($fieldValues, $find = [])
    {
        $true = 0;
        $i = 0;
        foreach ($fieldValues as $field) {
            if (in_array($field['value'], $find)) {
                // dd($field['value']);
                // return true;
                $true++;
            }
            $i++;
        }

        if ($true == count($find)) {
            # code...
            return true;
        } else {
            return false;
        }
    }
}
