<?php

namespace App\Enums;

enum DocumentVerificationStatus: string
{
    case PENDING = 'pending';
    case APPROVED = 'approved';
    case REVISION = 'revision';
    case REJECTED = 'rejected';
}
