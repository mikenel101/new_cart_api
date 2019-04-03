<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Custom Validation
    |--------------------------------------------------------------------------
    */

    'alphanumeric' => ':attribute must be alphanumeric characters.',
    'bankaccount' => 'The :attribute includes invalid character.',
    'cant_start_with' => 'The specified :attribute cant start with :prefix.',
    'exists_or_zero' => 'The selected :attribute is invalid.',
    'htmltagcheck' => ':attribute has html tag.',
    'katakana' => ':attribute must be Katakana.',
    'max_length' => 'The :attribute may not be less than :value characters.',
    'min_length' => 'The :attribute may not be greater than :value characters.',
    'move' => 'Couldn\'t move the :attribute.',
    'noblank' => ':attribute does not allow space or tab or line break.',
    'numericarray'  => 'The :attribute must be an array of numbers only.',
    'password' => 'The :attribute includes invalid character.',
    'stringarray'  => 'The :attribute must be an array of string only.',
    'uuid'  => 'The :attribute must be UUID.',
    'uuidarray'  => 'The :attribute must be an array of UUID only.',
    'zerostart' => ':attribute must start with zero number.',
    'unique'     => 'The entered :attribute is a duplication prohibition value.',
    'header'     => 'The value of the request header is invalid.',
];
