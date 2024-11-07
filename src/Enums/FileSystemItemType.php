<?php

namespace Vaskiq\LaravelFileLayer\Enums;

enum FileSystemItemType: string
{
    case FILE = 'file';
    case DIRECTORY = 'directory';
}
