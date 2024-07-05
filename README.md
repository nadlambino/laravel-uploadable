# Automatically handle the file uploads for your models.

[![Latest Version on Packagist](https://img.shields.io/packagist/v/nadlambino/uploadable.svg?style=flat-square)](https://packagist.org/packages/nadlambino/uploadable)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/nadlambino/uploadable/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/nadlambino/uploadable/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/nadlambino/uploadable/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/nadlambino/uploadable/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/nadlambino/uploadable.svg?style=flat-square)](https://packagist.org/packages/nadlambino/uploadable)

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

Optionally, you can publish the Upload model using

```bash
php artisan vendor:publish --tag="uploadable-model"
```

You can publish the config file with:

```bash
php artisan vendor:publish --tag="uploadable-config"
```

This is the contents of the published config file:

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
    | Allowed Mime Types
    |--------------------------------------------------------------------------
    |
    | Specify the mime types allowed for uploads. Supports categorization
    | for images, videos, and documents with specific file extensions.
    |
    */
    'mimes' => [
        'image' => ['jpeg', 'jpg', 'png', 'gif', 'bmp', 'svg', 'webp'],
        'video' => ['mp4', 'avi', 'mov', 'wmv', 'flv', '3gp', 'mkv'],
        'document' => ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'csv', 'txt'],
    ],
];
```

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

## Customizing the file name and upload path

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

## Manually processing file uploads

File upload happens when the uploadable model's `created` or `updated` event was fired.
If you're creating or updating an uploadable model quietly, you can call the `createUploads` or `updateUploads` method to manually process the file uploads.

```php
public function update(Request $request, Post $post)
{
    $post->update($request->all());
    
    // When the post did not change, the `updated` event won't be fired.
    // So, we need to manually call the `updateUploads` method.
    if (! $post->wasChanged()) {
        $post->updateUploads();
    }
}
```
> [!IMPORTANT]
> 
> Depending on your configuration, the `createUploads` will delete the uploadable model when the upload process fails, while `updateUploads` will update it to its original attributes.

## Temporarily disabling the file upload process

You can temporarily disable the file uploads by calling the static method `disableUpload`.

```php
public function update(Request $request, Post $post)
{
    // Temporarily disable the file uploads
    Post::disableUpload();
    
    $post->update($request->all());
    
    // Do more stuff here...
    
    // Manually process the uploads after everything you want to do.
    $post->updateUploads();
}
```

## Uploading files on model update

By default, when you update an uploadable model, the files from the request will add up to the existing uploaded files. If you want to replace the existing files with the new ones, you can configure it in the `uploadable.php` config file.

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

## Uploading files that are not from the request

If you wish to upload a file that is not from the request, you can do so by calling the `uploadFrom` method. This method can accept an instance or an array of `\Illuminate\Http\UploadedFile` or a string path of a file that is uploaded on your `temporary_disk`.

```php
// DO
$post->uploadFrom(UploadedFile::fake()->image('avatar1.jpg'));

// OR
$post->uploadFrom([
    UploadedFile::fake()->image('avatar1.jpg'),
    UploadedFile::fake()->image('avatar2.jpg'),
]);

// OR
$fullpath = UploadedFile::fake()->image('avatar.jpg')->store('tmp', config('uploadable.temporary_disk', 'local'));

$post->uploadFrom($fullpath);

// OR
$post->uploadFrom([
    $fullpath1,
    $fullpath2
]);

// OR even a mixed of both
$post->uploadFrom([
    UploadedFile::fake()->image('avatar1.jpg'),
    $fullpath,
]);

$post->save();
```

> [!IMPORTANT]
> 
> Make sure that you've already validated the files that you're passing here as it does not run any validation like it does when uploading from request.

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

## Lifecycle

If you want to do something before the file upload data is stored to the `uploads` table, you can define the `beforeSavingUpload` public method in your model. This method will be called after the file is uploaded and before the file details is saved in the database.

```php
public function beforeSavingUpload(Upload $upload, Model $model) : void
{
    $upload->additional_field = "some value";
}
```

Alternatively, you can statically call the `beforeSavingUploadUsing` method and pass a closure.
The closure will receive the same parameters as the `beforeSavingUpload` method.
Just make sure that you call this method before creating or updating the uploadable model.

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

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Ronald Lambino](https://github.com/nadlambino)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
