<?php

namespace App\Enums;

enum ComplaintMessageAuthorType: string
{
    case Complainant = 'complainant';
    case Admin = 'admin';
}
