<?php

namespace App\Domain\Enums;

enum MessageStatus: int
{
    case Awaiting = 0;
    case Success = 200;
    case Failed = 400;
}
