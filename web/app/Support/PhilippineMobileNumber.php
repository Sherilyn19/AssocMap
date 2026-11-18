<?php

namespace App\Support;

final class PhilippineMobileNumber
{
    // Philippine mobile numbers use the local 09 format and exactly 11 digits.
    public const RULE = 'regex:/\A09[0-9]{9}\z/';
    public const MESSAGE = 'Enter an 11-digit Philippine mobile number starting with 09, such as 09123456789.';
}
