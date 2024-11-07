<?php

namespace Vaskiq\LaravelFileLayer\Enums;

enum FileRefreshedProperty: string
{
    case SIZE = 'size';
    case MIME = 'mime';
    case LAST_MODIFIED = 'last_modified';
    case URL = 'url';
    case ETAG = 'etag';
}
