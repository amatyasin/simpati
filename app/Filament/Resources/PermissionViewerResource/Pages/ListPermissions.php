<?php

namespace App\Filament\Resources\PermissionViewerResource\Pages;

use App\Filament\Resources\PermissionViewerResource;
use Filament\Resources\Pages\ListRecords;

class ListPermissions extends ListRecords
{
    protected static string $resource = PermissionViewerResource::class;
}
