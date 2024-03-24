# A simple implementation of uploading files automagically.

[![Latest Version on Packagist](https://img.shields.io/packagist/v/nadlambino/uploadable.svg?style=flat-square)](https://packagist.org/packages/nadlambino/uploadable)
[![Total Downloads](https://img.shields.io/packagist/dt/nadlambino/uploadable.svg?style=flat-square)](https://packagist.org/packages/nadlambino/uploadable)

The <b>Uploadable</b> package is a simple implementation for automatically uploading files. It is designed to streamline and simplify the process of file upload that works on local and production environment, and storing the uploaded file details in the database.

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

Note: You can add more fields in the uploads table but the default fields should remain.

You can publish the config file with:

```bash
php artisan vendor:publish --tag="uploadable-config"
```

This is the contents of the published config file:

```php
return [
    'disks' => [
        
        'local' => [
            'disk' => 'public',
            'directory' => 'uploads',
            'host' => request()->getSchemeAndHttpHost(),
        ],

        'production' => [
            'disk' => 's3',
            'directory' => 'uploads',
        ],
    ],
];
```

Note: If you are running your application on another server like `development` or `staging`, you should add the disk mapping here.

## Usage

Simply use the `NadLambino\Uploadable\Models\Traits\HasUpload` trait in the model that needs file uploads.

```php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use NadLambino\Uploadable\Models\Traits\HasUpload;

class Post extends Model
{
    use HasFactory, HasUpload;
}
```

Now, everytime you create a `Post` and there is a file included in your request, it will automatically upload the file and save the details in `uploads` table.

Files from the request should have the following request names:
| Request name      | For                   | Rules                 |
| -                 | -                     | -                     |
| file              | Single file upload    | sometimes, file       |
| files             | Multiple files upload | sometimes, file       |
| image             | Single image upload   | sometimes, image, mime|
| images            | Multiple images upload| sometimes, image, mime|
| video             | Single video upload   | sometimes, mime       |
| videos            | Multiple videos upload| sometimes, mime       |

You can add more fields and their rules or override the default by defining the `uploadRules`
in your model, where key is the request name and the value is the rule.

```php
protected function uploadRules() : array
{
    return [
        // Override the `file` rules
        'file' => ['required', 'file', 'mime:application/pdf'],

        // Add new field
        'avatar' => ['required', 'image', 'mime:png']
    ];
}
```

Note: File upload happens once the model `created` event fired, so make sure that the way you create the uploadable model should firing the `created` event.

There are already defined relation method for specific upload type.
```php
// Relation for all types of uploads
public function upload() : MorphOne { }

// Relation for all types of uploads
public function uploads() : MorphMany { }

// Relation for uploads where extension is in the accepted image mimes
public function image() : MorphOne { }

// Relation for uploads where extension is in the accepted image mimes
public function images() : MorphMany { }

// Relation for uploads where extension is in the accepted video mimes
public function video() : MorphOne { }

// Relation for uploads where extension is in the accepted video mimes
public function videos() : MorphMany { }

// Relation for uploads where extension is NOT image nor video mimes
public function file() : MorphOne { }

// Relation for uploads where extension is NOT image nor video mimes
public function files() : MorphMany { }
```

If you wish to do something on the file before uploading it, you can define the `beforeUpload` method in your model which receives the `UploadedFile` and the current model instance.
```php
public function beforeUpload(UploadedFile $file, Model $model) : void
{

}
```

You can also define `afterUpload` which runs after the file is uploaded but before the file details is saved in the database.
This is useful if you have additional fields in `uploads` table that is not part of the default fields.
```php
public function afterUpload(Upload $upload, UploadedFile $file, Model $model, ?string $path) : void
{

}
```

## Testing

```bash
composer test
```

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
