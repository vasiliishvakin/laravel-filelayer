# Laravel File Layer

The Laravel File Layer package provides a flexible and powerful API for managing files across different storage systems. It leverages the repository pattern to work with files as Data Transfer Objects (DTOs) and wraps file interactions within a unified layer that supports different storage types, such as local file systems and cloud providers.

## Dependencies
1. **[Laravel Framework](https://github.com/laravel/framework)** - Core framework for building PHP applications.

2. **[Intervention Image](https://github.com/Intervention/image)** - Image handling and manipulation library for PHP.

3. **[Spatie Image Optimizer](https://github.com/spatie/laravel-image-optimizer)** - Optimizes images for web performance, compatible with Laravel.

4. **[Spatie Laravel Data](https://github.com/spatie/laravel-data)** - Data transfer object package for handling structured data in Laravel applications.

5. **[Vaskiq Data Layer](https://github.com/vasiliishvakin/laravel-datalayer)** - Custom data layer for handling repositories, events, and DTOs in Laravel.


## Installation

`composer require vaskiq/laravel-filelayer`


## Configuration

Publish the configuration file to customize storage and relocation settings.


`php artisan vendor:publish --provider="Vaskiq\LaravelFileLayer\Providers\LaravelFileLayerServiceProvider" --tag=config
`

Laravel's filesystem configuration file is located at `config/filesystems.php`

## Usage

### Basic Setup

1.  **Initialize FileLayer**: Use dependency injection to initialize the `FileLayer` instance in your application services.
2.
    ```php
    use Vaskiq\FileLayer\FileLayer;

    class SomeService {
        private FileLayer $fileLayer;

        public function __construct(FileLayer $fileLayer) {
            $this->fileLayer = $fileLayer;
        }
    }
    ```

3.  **Working with Files**: Files can be retrieved, stored, and manipulated using wrappers that offer a consistent API.

    ```php
    $fileWrapper = $fileLayer->file($fileId); // Retrieve file by ID
    $fileWrapper = $fileLayer->file($pathId); // Retrieve file by path
    $fileWrapper->get();                      // Get file content
    $fileWrapper->delete();                   // Delete file`
    ```

### Key Features

#### 1. Store files metadata in database
-    To reduce the number of requests, which is especially relevant for cloud file systems

#### 2. Storage Management
-   **Select Storage**: Automatically select or specify a storage system for each file.

#### 3. File Operations
-   **Path Manipulation**: Retrieve file paths and full paths for easy management.

    ```php
    $path = $fileLayer->path($fileWrapper);
    $fullPath = $fileLayer->fullPath($fileWrapper);
    ```

-   **File Metadata**: Access metadata like MIME type, size, last modified timestamp, and URL.
    ```php
    $mimeType = $fileWrapper->mime();
    $size = $fileWrapper->size();
    $url = $fileWrapper->url();
    ```

-   **File Existence Check**: Verify if a file exists.
    ```php
    if ($fileWrapper->exists()) {
        // Do something
    }
    ```

#### 4\. File Copying and Relocation

-   **Copy Files**: Copy files across different paths or storage systems.

    ```php
    $copiedFile = $fileLayer->copy($fileWrapper, '/new/path', 'newStorage');
    ```

-   **Relocate Files**: Move files between storages or directories, supporting configurations and options.

    ```php
    $relocatedFile = $fileLayer->relocate($fileWrapper, 'newStorage');
    ```

#### 5\. Synchronization and ETag Support

-   **Sync Files**: Synchronize files to ensure they are up-to-date and relocated to primary storage.

    ```php
    $syncedFile = $fileLayer->sync($fileWrapper);
    ```

-   **ETag/Hash**: Calculate and verify ETags/Hashs for file integrity.

#### 6\. Pipelines for File Processing

-   **Process Files**: Use processing pipelines to apply transformations or actions to files.

    ```php
    $processedFile = $fileLayer->process($fileWrapper, [$resizeAction, $watermarkAction]);
    ```

-   **Process to a New Path**: Generate a new path and apply actions to files, storing the result.

    ```php
    $processedFile = $fileLayer->processTo($fileWrapper, [$resizeAction], '/new/path');
    ```

#### 7\. Directory Management

-   **Retrieve Files and Directories**: List files and directories within a directory.

    ```php
    $files = $fileLayer->files($directoryWrapper);
    $directories = $fileLayer->directories($directoryWrapper);
    ```

### Events

This package dispatches several events to facilitate monitoring and custom handling of file actions:

-   `Retrieved`: Triggered when a file is retrieved.
-   `Stored`: Triggered when a file is successfully stored.
-   `Copied`: Triggered when a file is copied.
-   `Deleted`: Triggered when a file is deleted.
-   `Processed`: Triggered when a file has been processed.
-   `Relocated`: Triggered when a file is relocated.
-   `Synced`: Triggered when a file is synchronized.

### Example

Below is a simple example of copying a file to a new storage with processing:

```php
use Vaskiq\FileLayer\FileLayer;
use Vaskiq\LaravelFileLayer\Processors\Thumbnail_300x300;

$fileWrapper = $fileLayer->fileByPath('/original/path/file.jpg');
$processedFile = $fileLayer->processTo($fileWrapper, [Thumbnail_300x300::class], '/new/path/file.jpg');
$url = $processedFile->url();
```

License
-------

This package is open-source software licensed under the Apache 2.0 License.

