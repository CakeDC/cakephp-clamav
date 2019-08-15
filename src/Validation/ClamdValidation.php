<?php
namespace CakeDC\Clamav\Validation;

use Cake\Validation\Validator;

class ClamdValidation
{
    public function fileHasNoVirusesFound($check, $context)
    {
        dd($check);
    }
}