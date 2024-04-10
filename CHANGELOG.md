# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

Although we try to follow the semantic versioning, breaking changes until v1.0.0 may release in minor versions.
This is to avoid jumping to v1.0.0 too soon while the package is still in development.

## [v0.5.1] - 2024-04-10

### Fixed
- [595e2ed](https://github.com/nadlambino/laravel-uploadable/commit/595e2edc6e252ea14ab85cc609bf00100af69863) Fix incorrect observer method call
- [5d47912](https://github.com/nadlambino/laravel-uploadable/commit/5d47912198c132231d5393147a83bf6dbc46a6ba) Fix issue with deleting duplicate uploads by making the filename as unique as possible

### Changed
- [40702fb](https://github.com/nadlambino/laravel-uploadable/commit/40702fb802310bac96ba2111d4c62d94ff5f3c1d) Delete or update the uploadable model to its previous state if the upload process fails
- [177b71d](https://github.com/nadlambino/laravel-uploadable/commit/177b71d180a30282c9fcbeb373b74eaf2992a63b) `afterUploadUsing` is now wrapped with `Laravel\SerializableClosure\SerializableClosure`
- [d6ea23a](https://github.com/nadlambino/laravel-uploadable/commit/d6ea23a546e823f17ff9be6c0c8c4eb82c4cfbd1) Remove the uploadable model being passed to `getUploadFilename` and `getUploadPath` methods

### Added
- [b9b89aa](https://github.com/nadlambino/laravel-uploadable/commit/b9b89aae865c70e04951502ca4bf26dc43c62d8a) Allow disabling the upload process

## [v0.4.1] - 2024-04-09

### Changed
- Use filesystems.php configuration to determine the disk

### Added
- Configurable deletion of uploadable model (both queued and non-queued uploads)
- Configurable temporary disk for queued uploads
- Uploadable model can now add or replace file uploads
- Upload process can now be called manually

## [v0.3.1] - 2024-04-08

### Fixed
- Fix handling of multiple files from a single request field when uploading on queue
- Fix the root temporary directory for queued uploads
- Fix the host for local disk uploads

## [v0.3.0] - 2024-04-08

### Breaking
- Change `file` and `files` morph relation methods to `document` and `documents` respectively.

### Added
- Mime types can now be defined in the config file
- Validation rules can now be customized by defining `uploadRulesMessages` method in the uploadable model

### Changed
- Update README.md to reflect the changes in the package

## [v0.2.1] - 2024-04-07

### Breaking
- Remove `beforeUpload` hook as it does not serve any purpose. `UploadedFile` is immutable

### Added
- Uploads data can now be forced deleted
- Uploads can now be queued

## [v0.1.1] - 2024-03-24

### Added
- Added basic feature tests for `Uploadable` facade

## v0.1.0 - 2024-03-24
### Added
- Initial release
