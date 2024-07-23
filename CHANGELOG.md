# Changelog

All notable changes to `uploadable` will be documented in this file.

## [v1.2.0] - 2024-07-23

### Added
- [Add the ability to upload the file to a different disk](https://github.com/nadlambino/laravel-uploadable/pull/16/commits/78b30262075763f4c576024d09fb1e3b9a0b072b)
- [Add a new column `collection` to easily group file uploads](https://github.com/nadlambino/laravel-uploadable/pull/16/commits/78b30262075763f4c576024d09fb1e3b9a0b072b)
- [Add a new static method `uploadToCollection` to specify which group the file uploads belongs to](https://github.com/nadlambino/laravel-uploadable/pull/16/commits/a590e743457e6d0db42807079712573e9135a291)
- [Add a new scope query method `fromCollection` for `\NadLambino\Uploadable\Models\Upload::class` model to easily retrieve file uploads from specific group](https://github.com/nadlambino/laravel-uploadable/pull/16/commits/a590e743457e6d0db42807079712573e9135a291)

## [v1.1.2] - 2024-07-13

### Changed
- [Remove models from the list of enabled or disabled models when calling the `disableFor` or `onlyFor` method](https://github.com/nadlambino/laravel-uploadable/commit/f4f7e4d56f01622722882b79bfaefe828179e2bf)

## [v1.1.1] - 2024-07-11

### Added
- [Add `onlyFor` method to specifically process the upload for the given model](https://github.com/nadlambino/laravel-uploadable/commit/86861116729f347af4d2b0458f2c7db7d6b56e16)

### Changed
- [Amend `run-tests.yml` to fix failing tests on github workflow](https://github.com/nadlambino/laravel-uploadable/commit/1acce3834f1b9c5e5b6ea2ccbad06dc1d26009a7)

## [v1.1.0] - 2024-07-10

### Added
- [Support for storage options for uploading files](https://github.com/nadlambino/laravel-uploadable/commit/ccc5c441efea8cf3b23d11e1684549294b2639ea)
- [Lifecycle events](https://github.com/nadlambino/laravel-uploadable/commit/562f1fe822a55b3d7892faa08bbf4c4aebc19b3d)
- [Support for disabling the upload process for specific model class or instance](https://github.com/nadlambino/laravel-uploadable/commit/1e225aaa2d9c09f5b8a25ebaaf026700c67fa9c3)

### Changed
- [Improve temporary URL support for locally uploaded files](https://github.com/nadlambino/laravel-uploadable/commit/407ee133513530e5a87dc9782eb0fb5f9fc8f9d3)
- [Add more supported files in default mime validation](https://github.com/nadlambino/laravel-uploadable/commit/0523ffebc662d98b57ba3b02fa5c42ea17b03ba4)

## [v1.0.0] - 2024-07-06

### Changes

A complete rewrite of [Laravel Uploadable v0](https://github.com/nadlambino/laravel-uploadable-v0) with better code quality and complete tests
