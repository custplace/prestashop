# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [2.0.0] - 2024-06-22

### 🎉 Major Release - Complete Configuration Overhaul

Version 2.0.0 represents a major upgrade to the Custplace PrestaShop module with extensive new features, improved flexibility, and enhanced developer integration capabilities.

### ✨ Added

#### **Configurable Order Status Triggers**
- **Dynamic Order Status Selection**: Replaced hardcoded status IDs (2, 11) with admin-configurable checkbox selection
- **Multi-Status Support**: Administrators can now select multiple order statuses that trigger invitation sending
- **Backward Compatibility**: Automatic migration from hardcoded statuses to configurable system
- **User-Friendly Interface**: Checkbox-based selection in admin configuration panel

#### **Invitation Template System**
- **Custom Template ID**: Optional invitation template ID field for personalized invitation campaigns
- **Dynamic Template Assignment**: Template ID automatically included in API requests when configured
- **Flexible Configuration**: Template can be enabled/disabled without affecting core functionality

#### **Test Mode Environment**
- **Dual Environment Support**: Toggle between production and test environments
- **Automatic Domain Switching**: 
  - Production API: `apis.custplace.com`
  - Test API: `apis.kustplace.com`
  - Production Widgets: `widgets.custplace.com`
  - Test Widgets: `widgets.kustplace.com`
- **Seamless Testing**: Easy switching for pre-production testing without code changes
- **Separate Configuration Section**: Dedicated test mode configuration block

#### **Category Exclusion System**
- **Flexible Category Filtering**: Exclude orders containing products from specific categories
- **CSV Input Support**: Flexible parsing supporting multiple formats:
  - `1,2,3` (no spaces)
  - `1, 2, 3` (spaces after commas)
  - `1,2, 3` (mixed spacing)
- **Automatic Validation**: Only numeric, positive category IDs are accepted
- **Product-Level Checking**: Comprehensive category checking for all order products

#### **Enhanced Language Detection**
- **Customer Language Priority**: Fixed admin context issue - now uses customer's language from order data
- **Database-Level Detection**: Direct SQL query to retrieve customer's language preference
- **Fallback Mechanism**: Graceful fallback to shop default language if customer language unavailable
- **Context-Aware Processing**: Ensures invitations are sent in customer's preferred language

#### **API Headers Enhancement**
- **User-Agent Header**: Automatic PrestaShop version identification (`PrestaShop/X.X.X`)
- **Source Identification**: Custom `X-Source-Id: 39` header for tracking module requests
- **Enhanced Tracking**: Better API request identification and debugging capabilities

#### **Developer Hook System**
- **WordPress-Style Filters**: `actionCustplaceInvitationData` hook for invitation data modification
- **Flexible Data Manipulation**: Developers can add, modify, or enhance invitation data
- **Validation & Fallback**: Automatic validation ensures required fields remain intact
- **Error Handling**: Graceful error handling with fallback to original data
- **Safe Integration**: Hook failures don't break invitation sending process

#### **Comprehensive Logging System**
- **PrestaShop Logger Integration**: Full integration with PrestaShop's built-in logging system
- **Multi-Level Logging**:
  - **Success** (Informative): Successful invitation sending with order reference and invitation ID
  - **Warnings**: API responses with non-success status codes
  - **Errors**: HTTP errors, cURL failures, JSON parsing errors, API error codes
- **Admin Interface Access**: Logs viewable in PrestaShop admin under **Advanced Parameters > Logs**
- **Filterable Logs**: Filter by object "custplace" and type "Module"
- **Detailed Error Messages**: Comprehensive error information for debugging

#### **Documentation Enhancements**
- **Complete README Update**: Comprehensive documentation covering all new features
- **Configuration Guide**: Detailed explanation of all four configuration sections
- **Developer Integration Guide**: Hook usage examples and best practices
- **Logging & Monitoring Section**: Instructions for accessing and interpreting logs
- **Installation Instructions**: Updated installation guide with new configuration options

### 🔧 Changed

#### **Configuration Architecture**
- **Four-Section Layout**: Reorganized configuration into logical sections:
  1. **Invitation Settings**: Core API and invitation configuration
  2. **Trust Badge**: Badge widget configuration
  3. **Product Reviews**: Product review widget configuration  
  4. **Test Mode**: Test environment configuration
- **Improved User Experience**: Better organization and visual separation of different feature sets
- **Enhanced Form Validation**: Comprehensive validation for all configuration options

#### **Service Layer Improvements**
- **ConfigurationService Enhancements**:
  - Added trigger status management methods
  - Implemented CSV parsing for category exclusions
  - Added test mode URL generation methods
  - Enhanced form value handling for checkboxes
- **InvitationService Updates**:
  - Dynamic status checking instead of hardcoded values
  - Category exclusion validation
  - Customer language detection improvements
  - Hook system integration
  - Enhanced error handling and logging

#### **API Integration Improvements**
- **Dynamic Endpoint Selection**: Automatic API endpoint switching based on test mode
- **Enhanced Error Handling**: Comprehensive error catching and logging
- **Header Management**: Standardized header configuration with version tracking
- **Response Processing**: Improved response parsing with detailed error logging

### 🐛 Fixed

#### **Language Context Issues**
- **Admin vs Customer Context**: Fixed issue where admin language was used instead of customer language
- **Database Query Solution**: Implemented direct database query to retrieve customer's order language
- **Context Independence**: Invitation language now independent of admin user's language setting

#### **Form Submission Handling**
- **Checkbox Field Processing**: Fixed checkbox field data not being saved properly
- **Field Name Mapping**: Corrected PrestaShop checkbox field naming convention handling
- **Form Validation**: Enhanced form validation to prevent configuration loss

#### **Backward Compatibility**
- **Migration System**: Automatic migration from hardcoded status IDs to configurable system
- **Default Values**: Preserved existing behavior for installations upgrading from previous versions
- **Configuration Preservation**: Existing API keys and settings maintained during upgrade

### 🔒 Security

#### **Data Validation**
- **Input Sanitization**: Enhanced validation for all user inputs
- **Category ID Validation**: Strict numeric validation for category exclusions
- **Template ID Validation**: Proper validation for optional template ID field
- **SQL Injection Prevention**: Parameterized queries for all database operations

#### **Error Handling**
- **Information Disclosure Prevention**: Sanitized error messages in logs
- **Graceful Degradation**: System continues to function even when individual features fail
- **Validation Fallbacks**: Multiple layers of validation with safe fallback mechanisms

### 📊 Technical Improvements

#### **Code Architecture**
- **Service-Oriented Design**: Clean separation of concerns across service classes
- **Consistent Error Handling**: Standardized error handling patterns throughout the module
- **Code Documentation**: Comprehensive PHPDoc documentation for all new methods
- **Type Safety**: Improved type declarations and parameter validation

#### **Performance Optimizations**
- **Efficient Category Checking**: Optimized product category validation
- **Caching Considerations**: Proper handling of configuration caching
- **Database Query Optimization**: Efficient queries for language and category detection

#### **Maintenance**
- **Version Consistency**: Updated version numbers across all module files
- **Change Documentation**: Comprehensive changelog for tracking modifications
- **Code Standards**: Consistent coding standards throughout the module

### 🔄 Migration Guide

#### **Automatic Migrations**
- **Status Triggers**: Existing installations automatically migrate from hardcoded statuses [2, 11] to configurable system
- **API Keys**: Existing plain text API keys automatically encrypted
- **Configuration Preservation**: All existing settings preserved during upgrade

#### **Manual Configuration**
After upgrading to version 2.0.0, administrators should:

1. **Review Trigger Statuses**: Verify that the correct order statuses are selected for invitation triggering
2. **Configure New Features**: 
   - Set up invitation template ID if using custom templates
   - Configure category exclusions if needed
   - Enable test mode for pre-production testing
3. **Test Configuration**: Use test mode to verify all settings before production use

### 🔗 Dependencies

- **PrestaShop**: 1.7.0.0 or higher
- **PHP**: 7.4 or higher (as per PrestaShop requirements)
- **cURL**: Required for API communications
- **JSON**: Required for data serialization

---

## [1.2.0] and Earlier

Previous versions focused on core functionality:
- Basic invitation sending system
- Widget integration (trust badge and product reviews)
- API communication with Custplace service
- Basic configuration options

---

**Note**: This changelog documents all changes made during the development of version 2.0.0. For technical support or questions about any of these features, please contact support@custplace.com.