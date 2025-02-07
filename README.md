# Automatically handle the file uploads for your models.

[![Latest Version on Packagist](https://img.shields.io/packagist/v/nadlambino/uploadable.svg?style=flat-square)](https://packagist.org/packages/nadlambino/uploadable)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/nadlambino/uploadable/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/nadlambino/uploadable/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/nadlambino/uploadable/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/nadlambino/uploadable/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/nadlambino/uploadable.svg?style=flat-square)](https://packagist.org/packages/nadlambino/uploadable)

## Table of Contents

- [Installation](#installation)
- [Usage](#usage)
- [Customization](#customization)
- [Manually Processing of File Uploads](#manually-processing-of-file-uploads)
- [Temporarily Disable the File Uploads](#temporarily-disable-the-file-uploads)
- [Uploading Files on Model Update](#uploading-files-on-model-update)
- [Uploading Files that are NOT from the Request](#uploading-files-that-are-not-from-the-request)
- [Relation Methods](#relation-methods)
- [Lifecycle and Events](#lifecycle-and-events)
- [Queueing](#queueing)
- [Testing](#testing)
- [Changelog](#changelog)
- [Contributing](#contributing)
- [Security Vulnerabilities](#security-vulnerabilities)
- [Credits](#credits)
- [License](#license)

## Installation

You can install the package via composer:

```bash
composer require nadlambino/uploadable
```

Publish and run the migrations with:

```bash
php artisan vendor:publish --tag="uploadable-migrations"
php artisan migrate
```
> [!IMPORTANT]
> 
> You can add more fields to the uploads table according to your needs, but the existing fields should remain.

You can publish the config file with:

```bash
php artisan vendor:publish --tag="uploadable-config"
```

This is the contents of the published config file:

<details>
    <summary>uploadable.php</summary>

```php
return [

    /*
    |--------------------------------------------------------------------------
    | Validation
    |--------------------------------------------------------------------------
    |
    | Enable or disable the package's validation rules. Set to false if validation
    | has been performed separately, such as in a form request.
    |
    */
    'validate' => true,

    /*
    |--------------------------------------------------------------------------
    | Uploads Model
    |--------------------------------------------------------------------------
    |
    | Specify the model to use for uploads.
    |
    */
    'uploads_model' => \NadLambino\Uploadable\Models\Upload::class,

    /*
    |--------------------------------------------------------------------------
    | Delete Model on Upload Failure
    |--------------------------------------------------------------------------
    |
    | Automatically delete the newly created model if the upload process fails.
    | Applicable only to models that are being created.
    |
    */
    'delete_model_on_upload_fail' => true,

    /*
    |--------------------------------------------------------------------------
    | Rollback Model on Upload Failure
    |--------------------------------------------------------------------------
    |
    | Revert changes made to an existing model if the upload fails, restoring
    | the model's original attributes. Applies only to updated models.
    |
    */
    'rollback_model_on_upload_fail' => true,

    /*
    |--------------------------------------------------------------------------
    | Force Delete Uploads
    |--------------------------------------------------------------------------
    |
    | Determines whether uploaded files are permanently deleted. By default,
    | files are soft deleted, allowing for recovery.
    |
    */
    'force_delete_uploads' => false,

    /*
    |--------------------------------------------------------------------------
    | Replace Previous Uploads
    |--------------------------------------------------------------------------
    |
    | Determines whether uploaded files should be replaced with new ones. If
    | false, new files will be uploaded. If true, previous files will be
    | deleted once the new ones are uploaded.
    |
    */
    'replace_previous_uploads' => false,

    /*
    |--------------------------------------------------------------------------
    | Upload Queue
    |--------------------------------------------------------------------------
    |
    | Specify the queue name for uploading files. If set to null, uploads are
    | processed immediately. Otherwise, files are queued and processed.
    |
    */
    'upload_on_queue' => null,

    /*
    |--------------------------------------------------------------------------
    | Delete Model on Queued Upload Failure
    |--------------------------------------------------------------------------
    |
    | Delete the newly created model if a queued upload fails. Only affects models
    | that are being created.
    |
    */
    'delete_model_on_queue_upload_fail' => false,

    /*
    |--------------------------------------------------------------------------
    | Rollback Model on Queued Upload Failure
    |--------------------------------------------------------------------------
    |
    | Revert changes to a model if a queued upload fails, using the model's original
    | attributes before the upload started. Affects only updated models.
    |
    */
    'rollback_model_on_queue_upload_fail' => false,

    /*
    |--------------------------------------------------------------------------
    | Temporary Storage Disk
    |--------------------------------------------------------------------------
    |
    | Define the disk for temporary file storage during queued uploads. This
    | is where files are stored before being processed.
    |
    */
    'temporary_disk' => 'local',

    /*
    |--------------------------------------------------------------------------
    | Temporary URL
    |--------------------------------------------------------------------------
    |
    | Temporary URL for files that are uploaded locally is not supported by the
    | local disk. This setting allows you to specify the path and middleware to
    | access the files temporarily which uses a signed URL under the hood.
    | `expiration` can be a string or an instance of `DateTimeInterface`.
    |
    */
    'temporary_url' => [
        'path' => '/temporary',
        'middleware' => ['signed'],
        'expiration' => '1 hour',
    ],

    /*
    |--------------------------------------------------------------------------
    | Allowed Mimes by Extension
    |--------------------------------------------------------------------------
    |
    | Specify the mime types by extension that is allowed for uploads. Supports
    | categorization for images, videos, and documents with specific file
    | extensions.
    |
    */
    'mimes' => [
        'image' => ['jpeg', 'jpg', 'png', 'gif', 'bmp', 'svg', 'webp', 'ico'],
        'video' => ['mp4', 'webm', 'avi', 'mov', 'wmv', 'flv', '3gp', 'mkv', 'mpg', 'mpeg'],
        'document' => ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'csv', 'txt'],
    ],
];
```
</details>

<hr style="border-bottom: 3px solid #dadada" />

## Usage

Simply use the `NadLambino\Uploadable\Concerns\Uploadable` trait to your model that needs file uploads.

```php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use NadLambino\Uploadable\Concerns\Uploadable;

class Post extends Model
{
    use Uploadable;
}
```

Now, everytime you create or update a post, it will automatically upload the files that are included in your request and it will save the details in `uploads` table.

<hr style="border-bottom: 3px solid #dadada" />

## Customization

### 1. Rules and Messages

Files from the request should have the following request names:

| Request name | Use Case                  | Rules                  |
|--------------|---------------------------|------------------------|
| document     | Single document upload    | sometimes, file, mime  |
| documents    | Multiple document uploads | sometimes, file, mime  |
| image        | Single image upload       | sometimes, image, mime |
| images       | Multiple image uploads    | sometimes, image, mime |
| video        | Single video upload       | sometimes, mime        |
| videos       | Multiple video uploads    | sometimes, mime        |

You can add more fields or override the default ones by defining the protected `uploadRules`
method in your model.

```php
protected function uploadRules(): array
{
    return [
        // Override the rules for `document` field
        'document' => ['required', 'file', 'mime:application/pdf'], 

        // Add a new field with it's own set of rules
        'avatar' => ['required', 'image', 'mime:png'] 
    ];
}
```

To add or override the rules messages, you can define the protected `uploadRuleMessages` method in your model.

```php
public function uploadRuleMessages(): array
{
    return [
        'document.required' => 'The file is required.',
        'document.mime' => 'The file must be a PDF file.',
        'avatar.required' => 'The avatar is required.',
        'avatar.mime' => 'The avatar must be a PNG file.'
    ];
}
```

### 2. File Name and Upload Path

You can customize the file name and path by defining the public methods `getUploadFilename` and `getUploadPath` in your model.

```php
public function getUploadFilename(UploadedFile $file): string
{
    return str_replace('.', '', microtime(true)).'-'.$file->hashName();
}

public function getUploadPath(UploadedFile $file): string
{
    return $this->getTable().DIRECTORY_SEPARATOR.$this->{$this->getKeyName()};
}
```

> [!IMPORTANT]
> 
> Make sure that the file name is completely unique to avoid overriding existing files.

### 3. Storage Options

When you're uploading your files on cloud storage, oftentimes you want to provide options like visibility, cache control, and other metadata. To do so, you can define the `getUploadStorageOptions` in your model.

```php
public function getUploadStorageOptions(): array
{
    return [
        'visibility' => 'public',
        'CacheControl' => 'max-age=315360000, no-transform, public'
    ];
}
```

### 4. Upload Disk

When you're uploading your files, sometimes you just upload it on your local disk. However for larger files, you may want to use `s3`. This can be with static method `uploadDisk`.

```php
// Let us say that on your config/filesystems.php, your default disk is set to `local`.
// This method will create a user and will upload the file from the request, e.g., user avatar.
public function store(Request $request)
{
    User::create(...);
}

// While this method store the message and will upload the file from the request to `s3`.
public function store(Request $request)
{
    Message::uploadDisk('s3');
    Message::create(...);
}
```

### 5. Grouping of Uploads

When uploading a files for your model, sometimes you can have multiple file uploads for different purposes. For example, a post could have a file upload for thumbnail, banners, and gallery. This can be achieve with `uploadToCollection` method.

```php
public function store(Request $request)
{
    Post::uploadToCollection('banner');

    Post::create(...);
}
```

Also, to retrieve file uploads of specific collection, you can use the scope query `fromCollection`.

```php
$post = Post::query()
    ->with('image', fn ($query) => $query->fromCollection('banner'))
    ->find(...);
```

<hr style="border-bottom: 3px solid #dadada" />

## Manually Processing of File Uploads

File upload happens when the model's `created` or `updated` event was fired.
If you're creating or updating a model quietly, you can call the `createUploads` or `updateUploads` method to manually process the file uploads.

```php
public function update(Request $request, Post $post)
{
    $post->update($request->all());
    
    // If the post did not change, the `updated` event won't be fired.
    // So, we need to manually call the `updateUploads` method.
    if (! $post->wasChanged()) {
        $post->updateUploads();
    }
}
```
> [!IMPORTANT]
> 
> Depending on your configuration, the `createUploads` will delete the model when the upload process fails, while `updateUploads` will update it to its original attributes.

## Temporarily Disable the File Uploads

You can temporarily disable the file uploads by calling the static method `disableUpload`.

```php
public function update(Request $request, Post $post)
{
    // Temporarily disable the file uploads
    Post::disableUpload();
    
    $post->update($request->all());
    
    // Do more stuff here...
    
    // Manually process the uploads after everything you want to do
    $post->updateUploads();
}
```

### Caveat

When you are trying to create or update multiple models, the default behavior is that all of the files from the request will be uploaded and will be attached to all of these models. This is because these models are firing the `created` or `updated` event which triggers the upload process. 

There are multiple ways to prevent this from happening such as:

- Silently create or update the models. By doing so, the `created` or `updated` event won't be fired which will not trigger the upload process. This may not be what you want if you have a model observer for these two events.
- Disable the upload process on the specific model by calling the `disableUpload()` method.
- Disable the upload process from the `NadLambino\Uploadable\Actions\Upload` action itself. The `Upload::disableFor()` method can accept a model class name, a model instance, or an array of each or both. See below example:

```php
use NadLambino\Uploadable\Actions\Upload;

public function store(Request $request)
{
    // Disable the uploads for all of the instances of Post model
    Upload::disableFor(Post::class);

    // Files will be uploaded for User model
    User::create(...);

    // Files won't be uploaded for Post model
    Post::create(...);
}

// OR

public function update(Request $request, User $user)
{
     // Disable the uploads only for this specific $user
    Upload::disableFor($user);

    // Files won't be uploaded for this specific $user
    $user->update($request->validated());
    
    $anotherUser = User::find(...);

    // Files will be uploaded for this $anotherUser
    $anotherUser->update(...);
}
```

- Lastly, specifically instruct the `NadLambino\Uploadable\Actions\Upload` to process the upload only for the specific given model by calling the `Upload::onlyFor` method. This method has the same parameter signature as `Upload::disableFor` and ensure that only these given model classes or instances will be process.

```php
use NadLambino\Uploadable\Actions\Upload;

public function store()
{
    // Process the uploads only for all of the instances of User model
    Upload::onlyFor(User::class);

    // Files will be uploaded for this User model
    User::create(...);

    // Files won't be uploaded for this Post model
    Post::create(...);
}

public function update(User $user)
{
    // Process the uploads only for this specific $user
    Upload::onlyFor($user);

    // Files will be uploaded for this specific $user
    $user->update(...);

    $anotherUser = User::find(...);

    // Files won't be uploaded for this $anotherUser
    $anotherUser->update(...)
}
```

Also, there is `NadLambino\Uploadable\Actions\Upload::enableFor()` method if you need to delist a model from the disabled list. It is different from `onlyFor` in a way that `onlyFor` method ensures that the files were only be uploaded to the given models while `enableFor` just simply removes the given models from the disabled list.

All of these methods could also work even when you are uploading on a queue.

> [!NOTE]
>
> When calling the `disableFor` method, it will remove the given model from the list of `onlyFor` models. Same goes when calling the `onlyFor` method, it will remove the given model from the list of disabled models.

<hr style="border-bottom: 3px solid #dadada" />

## Uploading files on model update

By default, when you update a model, the files from the request will add up to the existing uploaded files. If you want to replace the existing files with the new ones, you can configure it in the `uploadable.php` config file.

```php
'replace_previous_uploads' => true,
```

Or alternatively, you can call the static method `replacePreviousUploads` before updating the model.

```php
public function update(Request $request, Post $post)
{
    // Replace the previous uploads
    Post::replacePreviousUploads();

    $post->update($request->all());
}
```

> [!NOTE]
> 
> The process of deleting the previous uploads will only happen when new files were successfully
> uploaded.

<hr style="border-bottom: 3px solid #dadada" />

## Uploading files that are NOT from the request

If you wish to upload a file that is NOT directly from the request, you can do so by calling the `uploadFrom` method. This method can accept an instance or an array of `Illuminate\Http\UploadedFile` or a string path of a file that is uploaded on your `temporary_disk`.

```php
// DO
$post->uploadFrom($request->file('image'));

// OR
$post->uploadFrom(new UploadedFile(...));

// OR
$post->uploadFrom([
    $request->file('image'),
    $request->file('avatar')
]);

// OR
$fullpath = ... // The path of the file that is uploaded in your `temporary_disk`. This could be something like an image that was modified by `ImageIntervention` then temporarily stored before uploading

$post->uploadFrom($fullpath);

// OR
$post->uploadFrom([
    $fullpath1,
    $fullpath2
]);

// OR even a mixed of both
$post->uploadFrom([
    $request->file('image'),
    $fullpath,
]);

$post->save();
```

> [!IMPORTANT]
> 
> Make sure that you've already validated the files that you're passing here as it does not run any validation like it does when uploading directly from the request.

<hr style="border-bottom: 3px solid #dadada" />

## Relation methods

There are already pre-defined relation method for specific upload type.

```php
// Relation for all types of uploads
public function upload(): MorphOne { }

// Relation for all types of uploads
public function uploads(): MorphMany { }

// Relation for uploads where extension or type is in the accepted image mimes
public function image(): MorphOne { }

// Relation for uploads where extension or type is in the accepted image mimes
public function images(): MorphMany { }

// Relation for uploads where extension or type is in the accepted video mimes
public function video(): MorphOne { }

// Relation for uploads where extension or type is in the accepted video mimes
public function videos(): MorphMany { }

// Relation for uploads where extension or type is in the accepted document mimes
public function document(): MorphOne { }

// Relation for uploads where extension or type is in the accepted document mimes
public function documents(): MorphMany { }
```

> [!IMPORTANT]
> 
> MorphOne relation method sets a limit of one in the query.

<hr style="border-bottom: 3px solid #dadada" />

## Lifecycle and Events

During the entire process of uploading your files, events are being fired in each step. This comes very helpful if you need to do something in between these steps or just for debugging purposes.

| Event                                              | When It Is Fired                       | What The Event Receives When Dispatched |
|----------------------------------------------------|----------------------------------------|-----------------------------------------|
| `NadLambino\Uploadable\Events\BeforeUpload::class` | Fired before the upload process starts | `Model $uploadable`, `array $files`, `UploadOptions $options` |
| `NadLambino\Uploadable\Events\StartUpload::class`  | Fired when the upload process has started and its about to upload the first file in the list. This event may fired up multiple times depending on the number of files that is being uploaded | `Model $uploadable`, `string $filename`, `string $path` |
| `NadLambino\Uploadable\Events\AfterUpload::class` | Fired when the file was successfully uploaded and file information has been stored in the `uploads` table. This event may fired up multiple times depending on the number of files that is being uploaded | `Model $uploadable`, `Upload $upload` |
| `NadLambino\Uploadable\Events\CompleteUpload::class` | Fired when all of the files are uploaded and all of the necessary clean ups has been made | `Model $uploadable`, `Collection $uploads` |
| `NadLambino\Uploadable\Events\FailedUpload::class` | Fired when an exception was thrown while trying to upload a specific file. | `Throwable $exception`, `Model $uploadable` |

If you want to do something before the file information is stored to the `uploads` table, you can define the `beforeSavingUpload` public method in your model. This method will be called after the file is uploaded in the storage and before the file information is saved in the database.

```php
public function beforeSavingUpload(Upload $upload, Model $model) : void
{
    $upload->additional_field = "some value";
}
```

Alternatively, you can statically call the `beforeSavingUploadUsing` method and pass a closure.
The closure will receive the same parameters as the `beforeSavingUpload` method.
Just make sure that you call this method before creating or updating the model.
Also, `beforeSavingUploadUsing` has the higher precedence than the `beforeSavingUpload` allowing you to override it when needed.

```php
Post::beforeSavingUploadUsing(function (Upload $upload, Post $model) use ($value) {
    $model->additional_field = $value;
});

$post->save();
```

> [!IMPORTANT]
> 
> Remember, when you're on a queue, you are actually running your upload process in a different
> application instance so you don't have access to the current application state like the request object.
> Also, make sure that the closure and its dependencies you passed to the `beforeSavingUploadUsing` method are serializable.

<hr style="border-bottom: 3px solid #dadada" />

## Queueing

You can queue the file upload process by defining the queue name in the config.

```php
'upload_on_queue' => null,
```

Alternatively, you can also call the static method `uploadOnQueue`.

```php
Post::uploadOnQueue('default');

$post->save();
```

<hr style="border-bottom: 3px solid #dadada" />

## Testing

```bash
composer test
```

<hr style="border-bottom: 3px solid #dadada" />

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

<hr style="border-bottom: 3px solid #dadada" />

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

<hr style="border-bottom: 3px solid #dadada" />

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

<hr style="border-bottom: 3px solid #dadada" />

## Credits

- [Ronald Lambino](https://github.com/nadlambino)
- [All Contributors](../../contributors)

<hr style="border-bottom: 3px solid #dadada" />

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
