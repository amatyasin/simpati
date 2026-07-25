<?php

namespace App\Enums;

enum MediaVerificationStatus: string
{
    case DRAFT = 'draft';
    case PENDING = 'pending';
    case APPROVED = 'approved';
    case REVISION = 'revision';
    case REJECTED = 'rejected';
}
