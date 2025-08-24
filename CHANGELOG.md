# Changelog

All notable changes to `mondial-relay-api` will be documented in this file.

## [1.2.0] - 2025-08-24

### Removed
- **BREAKING CHANGE**: Removed unused `code_marque` parameter from configuration and constructor
- **Configuration Cleanup**: Removed `MONDIAL_RELAY_CODE_MARQUE` environment variable requirement
- **Code Cleanup**: Removed debug Log statements from MondialRelayClient

### Enhanced
- **Simplified Configuration**: Only `enseigne` and `private_key` are now required for API functionality
- **Cleaner Codebase**: Removed unused parameters and debug code for better maintainability
- **Verified Functionality**: All API features confirmed working without code_marque parameter

### Migration Guide
If upgrading from v1.1.x:
1. Remove `MONDIAL_RELAY_CODE_MARQUE` from your `.env` file
2. Remove `code_marque` from your `config/mondialrelay.php` if manually configured
3. Update any direct `MondialRelayClient` instantiation to remove the `$codeMarque` parameter

## [1.1.1] - 2025-08-23

### Enhanced
- **Error Handling**: Added comprehensive error messages for all Mondial Relay API status codes (0-99)
- **Debugging**: Improved error messages in French for better debugging and user feedback
- **API Coverage**: Complete coverage of all API response scenarios including tracking status codes (80-83)
- **User Experience**: More descriptive error messages instead of generic "Unknown error" responses

## [1.1.0] - 2025-08-22

### Added
- **Label Generation**: New `createExpeditionWithLabel()` method for creating expeditions with PDF labels
- **Batch Label Management**: New `getLabels()` method for retrieving multiple labels in a single PDF
- **Label Download**: Direct PDF download functionality with `downloadLabel()` method
- **Multi-format Support**: Support for A4, A5, and 10x15 label formats
- **New DTOs**:
  - `Label` model for individual label management
  - `LabelBatch` model for batch label operations
  - `ExpeditionWithLabel` model for expeditions with integrated labels
- **Enhanced Service Methods**:
  - `createExpeditionWithLabel()` - Create expedition with PDF label (relay point)
  - `createHomeDeliveryExpeditionWithLabel()` - Create home delivery expedition with PDF label
  - `getLabelsForExpeditions()` - Get labels for multiple expeditions
  - `downloadLabelPdf()` - Download PDF content from URL
  - `downloadExpeditionLabel()` - Download label for specific expedition
  - `downloadBatchLabels()` - Download batch labels PDF

### Enhanced
- **Client Methods**: All client methods now return proper DTOs instead of arrays
- **Service Layer**: Enhanced high-level service with label management capabilities
- **Validation**: Improved delivery mode validation (24L no longer requires relay point)
- **Documentation**: Updated README with label management examples
- **Testing**: Added comprehensive tests for new label functionality (64 tests total)

### Fixed
- **Delivery Mode Validation**: Fixed `24L` (home delivery) incorrectly requiring relay point
- **DTO Consistency**: All API methods now consistently return structured DTOs

## [1.0.0] - 2024-01-XX

### Added
- Initial release of the Laravel Mondial Relay API package
- Support for searching relay points with `searchRelayPoints()` method
- Support for creating expeditions with `createExpedition()` method
- Support for tracking packages with `trackPackage()` method
- Automatic MD5 security key generation
- Parameter validation for all API calls
- Comprehensive error handling with custom exceptions
- Data Transfer Objects (DTOs) for structured data:
  - `RelayPoint` model with utility methods
  - `Expedition` model with tracking URL generation
  - `TrackingInfo` and `TrackingEvent` models
- Helper utilities in `MondialRelayHelper` class:
  - Delivery mode validation and labeling
  - Weight and distance formatting
  - Country code validation
  - Postal code validation
  - Shipping cost calculation
  - Address and phone number formatting
- High-level service class `MondialRelayService` for simplified usage
- Laravel Facades for easy access (`MondialRelay` and `MondialRelayService`)
- Comprehensive test suite with PHPUnit
- Support for Laravel 9.x, 10.x, and 11.x
- Support for PHP 8.1+

### Features
- **Relay Point Search**: Find nearby relay points by postal code with filtering options
- **Expedition Creation**: Create shipments for both relay point and home delivery
- **Package Tracking**: Track packages with detailed event history
- **Validation**: Automatic validation of all parameters before API calls
- **Error Handling**: Detailed error messages with user-friendly translations
- **Caching**: Singleton pattern for efficient resource usage
- **Testing**: Mock-friendly design for easy testing in your applications

### Configuration
- Configurable API credentials via environment variables
- Test mode support for development
- Customizable API endpoint URL

### Documentation
- Complete README with installation and usage examples
- Inline code documentation
- PHPUnit test examples
