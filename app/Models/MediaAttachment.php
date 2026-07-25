<?php

namespace App\Models;

use Spatie\MediaLibrary\MediaCollections\Models\Media as BaseMedia;

class MediaAttachment extends BaseMedia
{
    protected $table = 'media_attachments';
}
