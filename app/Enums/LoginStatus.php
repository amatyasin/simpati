<?php

namespace App\Enums;

enum LoginStatus: string
{
    case Successful = 'successful';
    case Failed = 'failed';
    case Locked = 'locked';
}
