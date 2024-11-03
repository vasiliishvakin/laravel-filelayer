<?php

namespace Vaskiq\LaravelFileLayer\Enums;

enum FileRefreshedProperties: string
{
    case SIZE = 'size';
    case MIME = 'mime';
    case LAST_MODIFIED = 'last_modified';
    case URL = 'url';
}
