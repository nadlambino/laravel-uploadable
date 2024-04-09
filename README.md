# A simple implementation of uploading files automagically.

[![Latest Version on Packagist](https://img.shields.io/packagist/v/nadlambino/uploadable.svg?style=flat-square)](https://packagist.org/packages/nadlambino/uploadable)
[![Total Downloads](https://img.shields.io/packagist/dt/nadlambino/uploadable.svg?style=flat-square)](https://packagist.org/packages/nadlambino/uploadable)

The <b>Uploadable</b> package handles the file upload process for your models, automatically.

## Installation

You can install the package via composer:

```bash
composer require nadlambino/uploadable
```

You can publish and run the migrations with:

```bash
php artisan vendor:publish --tag="uploadable-migrations"
php artisan migrate
```

>**NOTE:** You can add more fields in the uploads table but the default fields should remain.

You can publish the config file with:

```bash
php artisan vendor:publish --tag="uploadable-config"
```

## Usage

Simply use the `NadLambino\Uploadable\Models\Traits\HasUpload` trait in the model that needs file uploads.

```php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use NadLambino\Uploadable\Models\Traits\HasUpload;

class Post extends Model
{
    use HasUpload;
}
```

Now, everytime you create a `Post` and there is a file included in your request, 
it will automatically upload the file and save the details in `uploads` table.

Files from the request should have the following request names:

| Request name | For                       | Rules                  |
|--------------|---------------------------|------------------------|
| document     | Single document upload    | sometimes, file, mime  |
| documents    | Multiple document uploads | sometimes, file, mime  |
| image        | Single image upload       | sometimes, image, mime |
| images       | Multiple image uploads    | sometimes, image, mime |
| video        | Single video upload       | sometimes, mime        |
| videos       | Multiple video uploads    | sometimes, mime        |

You can add more fields and their rules or override the default ones by defining the `uploadRules`
method in your model.
```php
protected function uploadRules() : array
{
    return [
        'document' => ['required', 'file', 'mime:application/pdf'], // Override the `document` rules
        'avatar' => ['required', 'image', 'mime:png'] // Add new field
    ];
}
```

To add or override the rules messages, you can define the `uploadRulesMessages` method in your model.
```php
public function uploadRulesMessages() : array
{
    return [
        'document.required' => 'The file is required.',
        'document.mime' => 'The file must be a PDF file.',
        'avatar.required' => 'The avatar is required.',
        'avatar.mime' => 'The avatar must be a PNG file.'
    ];
}
```

>**NOTE:** 
> File upload happens once the model `created` event was fired, 
> so make sure that the way you create the uploadable model should be firing this event.

There are already pre-defined relation method for specific upload type.
```php
// Relation for all types of uploads
public function upload() : MorphOne { }

// Relation for all types of uploads
public function uploads() : MorphMany { }

// Relation for uploads where extension or type is in the accepted image mimes
public function image() : MorphOne { }

// Relation for uploads where extension or type is in the accepted image mimes
public function images() : MorphMany { }

// Relation for uploads where extension or type is in the accepted video mimes
public function video() : MorphOne { }

// Relation for uploads where extension or type is in the accepted video mimes
public function videos() : MorphMany { }

// Relation for uploads where extension or type is in the accepted document mimes
public function document() : MorphOne { }

// Relation for uploads where extension or type is in the accepted document mimes
public function documents() : MorphMany { }
```

You can define an `afterUpload` method which runs after the file is uploaded and before the file details is saved in the database.
This is useful if you have additional fields in `uploads` table that you want to have a value before saving, or you want to fire an
event to notify the user that the upload was successful.
```php
public function afterUpload(Upload $upload, Model $model, Request $request) : void
{
    $upload->additional_field = "some value";
}
```

Alternatively, you can statically call the `afterUploadUsing` method in the uploadable model then call this method before the uploadable data is saved.
```php
Post::afterUploadUsing(function (Upload $upload, Post $model) {
    $model->additional_field = "some value";
});
```

>**NOTE:**
> The request object that the `afterUpload` receives is a new request object that doesn't contain the uploaded files.
> This is because when queueing the upload process, UploadedFile objects are not serializable.
> The `afterUploadUsing` method will not be called when you queue the file upload process.

## Queuing

You can queue the file upload process by defining the queue name in the config. However, when you queue the file upload process, 
the `afterUploadUsing` method will not be called and instead will call the `afterUpload` method.

```php
'upload_on_queue_using' => null,
```

## Testing

```bash
composer test
```

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
