<?php

namespace App\Api\V1\Requests;

use Config;
use Dingo\Api\Http\FormRequest;

class ChangePasswordRequest extends FormRequest
{
    public function rules()
    {
        return Config::get('boilerplate.change_password.validation_rules');
    }

    public function authorize()
    {
        return true;
    }
}
