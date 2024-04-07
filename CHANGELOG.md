# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

Although we try to follow the semantic versioning, breaking changes until v1.0.0 may release in minor versions.
This is to avoid jumping to v1.0.0 too soon while the package is still in development.

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
