<?php

namespace App\Http\Controllers\Global;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class GlobalFunctionController extends Controller
{
    //

    public static function findFieldValueByKey($fieldValues, $key)
    {
        $i = 0;
        foreach ($fieldValues as $field) {
            if ($field['field'] == $key) {
                return $i;
                // return $field['value'];
            }
            $i++;
        }
        return null;
    }
}
