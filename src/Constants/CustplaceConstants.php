<?php
/**
 * Custplace.com
 *
 * @author    Custplace <support@custplace.com> - https://fr.custplace.com
 * @copyright THIRD VOICE 2023 - https://fr.custplace.com
 * @license   see file: LICENSE.txt
 *
 * @version   1.2.0
 */

namespace Custplace\Constants;

class CustplaceConstants
{
    // Order Status IDs (Default values)
    public const ORDER_STATUS_PAYMENT_ACCEPTED = 2;
    public const ORDER_STATUS_REMOTE_PAYMENT_ACCEPTED = 11;
    public const DEFAULT_TRIGGER_STATUSES = [2, 11];
    
    // Configuration Keys
    public const CONFIG_API_ENABLED = 'custplace_api_enabled';
    public const CONFIG_API_CLIENT = 'custplace_api_client';
    public const CONFIG_API_KEY = 'custplace_api_key';
    public const CONFIG_DELAY_SOLICITATION = 'custplace_delai_sollicitation';
    public const CONFIG_TRIGGER_STATUSES = 'custplace_trigger_statuses';
    public const CONFIG_INVITATION_TEMPLATE_ID = 'custplace_invitation_template_id';
    public const CONFIG_EXCLUDED_CATEGORIES = 'custplace_excluded_categories';
    public const CONFIG_TEST_MODE = 'custplace_test_mode';
    public const CONFIG_WIDGET_TRUST_BADGE = 'custplace_widget_sceau_confiance';
    public const CONFIG_WIDGET_PRODUCT_REVIEWS = 'custplace_widget_wap';
    public const CONFIG_WIDGET_TOKEN = 'custplace_wap_token';
    public const CONFIG_WIDGET_FIRST_COLOR = 'custplace_wap_first_color';
    public const CONFIG_WIDGET_SECOND_COLOR = 'custplace_wap_second_color';
    public const CONFIG_WIDGET_SUBRATINGS = 'custplace_wap_subratings';
    public const CONFIG_WIDGET_TITLE_RATING = 'custplace_wap_title_rating';
    
    // Database Table
    public const TABLE_CUSTPLACE = 'custplace';
    
    // Invitation Statuses
    public const STATUS_PENDING = 'pending';
    public const STATUS_SUCCESS = 'success';
    public const STATUS_ERROR = 'error';
    
    // Invitation Types
    public const INVITATION_TYPE_POST_REVIEW = 'post_review';
    
    // Validation Limits
    public const MAX_DELAY_DAYS = 30;
    
    // API Configuration
    public const API_ENDPOINT_BASE = 'https://apis.custplace.com/v3/';
    public const API_ENDPOINT_TEST = 'https://apis.kustplace.com/v3/';
    
    // Widget JavaScript URLs
    public const WIDGET_PRODUCT_REVIEWS_JS = 'https://widgets.custplace.com/reviews/product/latest/static/js/bundle.js';
    public const WIDGET_TRUST_BADGE_JS = 'https://widgets.custplace.com/rating/v4/embed.js';
    
    // Test Environment Domains
    public const WIDGET_DOMAIN_PROD = 'https://widgets.custplace.com';
    public const WIDGET_DOMAIN_TEST = 'https://widgets.kustplace.com';
    
    // Template Files
    public const TEMPLATE_WIDGET_PRODUCT = 'module:custplace/views/templates/hook/custplace_widget_product.tpl';
    public const TEMPLATE_WIDGET_PRODUCT_TITLE = 'module:custplace/views/templates/hook/custplace_widget_product_title.tpl';
    public const TEMPLATE_WIDGET_BADGE = 'module:custplace/views/templates/hook/custplace_widget_badge.tpl';
    public const TEMPLATE_ADMIN_CUSTPLACE = 'custplace.html.twig';
    
    // Form Submit Actions
    public const FORM_SUBMIT_API = 'submission_api';
    public const FORM_SUBMIT_WIDGET_BADGE = 'submission_widget_sceau';
    public const FORM_SUBMIT_WIDGET_PRODUCT = 'submission_wap';
    public const FORM_SUBMIT_TEST_MODE = 'submission_test_mode';
    
    // CSS and JS Assets
    public const CSS_CUSTPLACE = 'custplace.css';
    
    // JavaScript Module Names
    public const JS_MODULE_PRODUCT_REVIEWS = 'module-custplace_product-reviews';
    public const JS_MODULE_TRUST_BADGE = 'module-custplace-api-widget_index';
}