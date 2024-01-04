<?php

namespace App;

class Validator
{
    public function validate($url)
    {
        $errors = [];

        if (mb_strlen($url) > 255) {
            $errors['urlLength'] = "URL should be less than 255 characters";
        }

        if ($url == '') {
            $errors['urlBlank'] = "URL can't be blank";
        }

        if (filter_var($url, FILTER_VALIDATE_URL) === false) {
            $errors['urlValidate'] = "Not a valid URL";
        }

        return $errors;
    }
}
