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

> [!NOTE]
> 
> You can add more fields in the uploads table but the default fields should remain.

You can publish the config file with:

```bash
php artisan vendor:publish --tag="uploadable-config"
```

## Usage

Simply use the `NadLambino\Uploadable\Models\Traits\HasUpload` trait in your models that need file uploads.

```php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use NadLambino\Uploadable\Models\Traits\HasUpload;

class Post extends Model
{
    use HasUpload;
}
```

Now, everytime you create or update a post, it will automatically upload the file included in your request
and save the details in `uploads` table.

Files from the request should have the following request names:

| Request name | For                       | Rules                  |
|--------------|---------------------------|------------------------|
| document     | Single document upload    | sometimes, file, mime  |
| documents    | Multiple document uploads | sometimes, file, mime  |
| image        | Single image upload       | sometimes, image, mime |
| images       | Multiple image uploads    | sometimes, image, mime |
| video        | Single video upload       | sometimes, mime        |
| videos       | Multiple video uploads    | sometimes, mime        |

You can add more fields or override the default ones by defining the `uploadRules`
method in your model.
```php
protected function uploadRules() : array
{
    return [
        // Override the `document` rules
        'document' => ['required', 'file', 'mime:application/pdf'], 
        // Add a new field
        'avatar' => ['required', 'image', 'mime:png'] 
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

## Customizing the file name and path

You can customize the file name and path by defining the `getUploadFilename` and `getUploadPath` methods in your model.

```php
public function getUploadFilename(UploadedFile $file) : string
{
    return str_replace('.', '', microtime(true)) . '-' . $file->hashName();
}

public function getUploadPath(UploadedFile $file) : string
{
    return $this->getTable() . DIRECTORY_SEPARATOR . $this->{$this->getKeyName()};
}
```

> [!IMPORTANT]
> 
> Make sure that the file name is completely unique to avoid overwriting existing files.

## Manually processing file uploads

File upload happens when the uploadable model's `created` or `updated` event was fired.
If you're creating or updating an uploadable model without these events, 
you can call the `createUploads` or `updateUploads` method to manually process the file uploads.

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
> The `createUploads` will delete the uploadable model when the upload process failed, 
> while `updateUploads` will update it to its previous attributes.

## Temporarily disabling file uploads

You can temporarily disable the file uploads by calling the static method `dontUpload`.

```php
public function update(Request $request, Post $post)
{
    // Temporarily disable the file uploads
    Post::dontUpload();
    
    $post->update($request->all());
    
    // Do more stuff here...
    
    // Manually process the uploads after everything you want to do.
    $post->updateUploads();
}
```

## Uploading files on model update

By default, when you update an uploadable model, the files from the request will add up to the existing uploaded files.
If you want to replace the existing files with the new ones, you can configure it in the `uploadable.php` config file.

```php
'delete_previous_uploads' => false,
```

Or alternatively, you can call the static method `deletePreviousUploads` before updating the model.

```php
public function update(Request $request, Post $post)
{
    // Delete the previous uploads
    Post::deletePreviousUploads();
    
    $post->update($request->all());
}
```

> [!NOTE]
> 
> The process of deleting the previous uploads will only happen when new uploads were successfully uploaded.

## Relation methods

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

## After uploading

If you want to do something after the file is uploaded, you can define the `afterUpload` method in your model.
This method will be called after the file is uploaded and before the file details is saved in the database.

```php
public function afterUpload(Upload $upload, Model $model) : void
{
    $upload->additional_field = "some value";
}
```

Alternatively, you can statically call the `afterUploadUsing` method and pass a closure.
The closure has the same parameters as the `afterUpload` method.
Just make sure that you call this method before creating or updating the uploadable model.

```php
Post::afterUploadUsing(function (Upload $upload, Post $model) use ($request->get('additional_field')) {
    $model->additional_field = "some value";
});
```

> [!IMPORTANT]
> 
> Remember, when you're on queue, you are actually running your upload process in a different application instance,
> so you don't have access to the current application's state like the request object.
> 
> Also, make sure that the closure and its dependencies you passed to the `afterUploadUsing` method are serializable.

## Queueing

You can queue the file upload process by defining the queue name in the config.

```php
'upload_on_queue_using' => null,
```

## Testing

```bash
composer test
```

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
