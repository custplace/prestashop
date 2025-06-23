<?php
/**
 * Custplace.com
 *
 * @author    Custplace <support@custplace.com> - https://fr.custplace.com
 * @copyright THIRD VOICE 2023 - https://fr.custplace.com
 * @license   see file: LICENSE.txt
 *
 * @version   2.0.0
 */

namespace Custplace\Service;

use Custplace\Constants\CustplaceConstants;
use Configuration;
use Tools;

class ConfigurationService
{
    private EncryptionService $encryptionService;
    
    public function __construct()
    {
        $this->encryptionService = new EncryptionService();
    }
    /**
     * Get all configuration field values
     *
     * @return array
     */
    public function getConfigFieldsValues(): array
    {
        $values = [
            CustplaceConstants::CONFIG_API_ENABLED => Tools::getValue(
                CustplaceConstants::CONFIG_API_ENABLED,
                Configuration::get(CustplaceConstants::CONFIG_API_ENABLED)
            ),
            CustplaceConstants::CONFIG_API_CLIENT => Tools::getValue(
                CustplaceConstants::CONFIG_API_CLIENT,
                Configuration::get(CustplaceConstants::CONFIG_API_CLIENT)
            ),
            CustplaceConstants::CONFIG_API_KEY => Tools::getValue(
                CustplaceConstants::CONFIG_API_KEY,
                Configuration::get(CustplaceConstants::CONFIG_API_KEY)
            ),
            CustplaceConstants::CONFIG_DELAY_SOLICITATION => Tools::getValue(
                CustplaceConstants::CONFIG_DELAY_SOLICITATION,
                Configuration::get(CustplaceConstants::CONFIG_DELAY_SOLICITATION)
            ),
            CustplaceConstants::CONFIG_INVITATION_TEMPLATE_ID => Tools::getValue(
                CustplaceConstants::CONFIG_INVITATION_TEMPLATE_ID,
                Configuration::get(CustplaceConstants::CONFIG_INVITATION_TEMPLATE_ID)
            ),
            CustplaceConstants::CONFIG_EXCLUDED_CATEGORIES => Tools::getValue(
                CustplaceConstants::CONFIG_EXCLUDED_CATEGORIES,
                Configuration::get(CustplaceConstants::CONFIG_EXCLUDED_CATEGORIES)
            ),
            CustplaceConstants::CONFIG_TEST_MODE => Tools::getValue(
                CustplaceConstants::CONFIG_TEST_MODE,
                Configuration::get(CustplaceConstants::CONFIG_TEST_MODE)
            ),
            CustplaceConstants::CONFIG_WIDGET_TRUST_BADGE => Tools::getValue(
                CustplaceConstants::CONFIG_WIDGET_TRUST_BADGE,
                Configuration::get(CustplaceConstants::CONFIG_WIDGET_TRUST_BADGE)
            ),
            CustplaceConstants::CONFIG_WIDGET_PRODUCT_REVIEWS => Tools::getValue(
                CustplaceConstants::CONFIG_WIDGET_PRODUCT_REVIEWS,
                Configuration::get(CustplaceConstants::CONFIG_WIDGET_PRODUCT_REVIEWS)
            ),
            CustplaceConstants::CONFIG_WIDGET_TOKEN => Tools::getValue(
                CustplaceConstants::CONFIG_WIDGET_TOKEN,
                Configuration::get(CustplaceConstants::CONFIG_WIDGET_TOKEN)
            ),
            CustplaceConstants::CONFIG_WIDGET_FIRST_COLOR => Tools::getValue(
                CustplaceConstants::CONFIG_WIDGET_FIRST_COLOR,
                Configuration::get(CustplaceConstants::CONFIG_WIDGET_FIRST_COLOR)
            ),
            CustplaceConstants::CONFIG_WIDGET_SECOND_COLOR => Tools::getValue(
                CustplaceConstants::CONFIG_WIDGET_SECOND_COLOR,
                Configuration::get(CustplaceConstants::CONFIG_WIDGET_SECOND_COLOR)
            ),
            CustplaceConstants::CONFIG_WIDGET_SUBRATINGS => Tools::getValue(
                CustplaceConstants::CONFIG_WIDGET_SUBRATINGS,
                Configuration::get(CustplaceConstants::CONFIG_WIDGET_SUBRATINGS)
            ),
            CustplaceConstants::CONFIG_WIDGET_TITLE_RATING => Tools::getValue(
                CustplaceConstants::CONFIG_WIDGET_TITLE_RATING,
                Configuration::get(CustplaceConstants::CONFIG_WIDGET_TITLE_RATING)
            ),
        ];
        
        // Merge checkbox values for trigger statuses
        $checkboxValues = $this->getTriggerStatusesForForm();
        return array_merge($values, $checkboxValues);
    }

    /**
     * Validate API configuration
     *
     * @param array $values
     * @return bool
     */
    public function validateApiConfiguration(array $values): bool
    {
        if ($values[CustplaceConstants::CONFIG_API_ENABLED] == '1') {
            return !empty($values[CustplaceConstants::CONFIG_API_CLIENT]) &&
                   !empty($values[CustplaceConstants::CONFIG_API_KEY]) &&
                   is_numeric($values[CustplaceConstants::CONFIG_API_CLIENT]) &&
                   is_numeric($values[CustplaceConstants::CONFIG_DELAY_SOLICITATION]) &&
                   intval($values[CustplaceConstants::CONFIG_DELAY_SOLICITATION]) <= CustplaceConstants::MAX_DELAY_DAYS;
        }
        return true;
    }

    /**
     * Validate widget configuration
     *
     * @param array $values
     * @return bool
     */
    public function validateWidgetConfiguration(array $values): bool
    {
        if (isset($values[CustplaceConstants::CONFIG_WIDGET_PRODUCT_REVIEWS]) && 
            $values[CustplaceConstants::CONFIG_WIDGET_PRODUCT_REVIEWS] === '1') {
            return !empty($values[CustplaceConstants::CONFIG_WIDGET_TOKEN]);
        }
        return true;
    }

    /**
     * Update API configuration
     *
     * @return bool
     */
    public function updateApiConfiguration(): bool
    {
        $values = $this->getConfigFieldsValues();
        
        if (!$this->validateApiConfiguration($values)) {
            return false;
        }

        Configuration::updateValue(CustplaceConstants::CONFIG_API_ENABLED, $values[CustplaceConstants::CONFIG_API_ENABLED]);
        Configuration::updateValue(CustplaceConstants::CONFIG_API_CLIENT, $values[CustplaceConstants::CONFIG_API_CLIENT]);
        
        // Encrypt API key before storing
        if (!empty($values[CustplaceConstants::CONFIG_API_KEY])) {
            $this->setApiKey($values[CustplaceConstants::CONFIG_API_KEY]);
        }
        
        Configuration::updateValue(CustplaceConstants::CONFIG_DELAY_SOLICITATION, $values[CustplaceConstants::CONFIG_DELAY_SOLICITATION]);
        Configuration::updateValue(CustplaceConstants::CONFIG_INVITATION_TEMPLATE_ID, $values[CustplaceConstants::CONFIG_INVITATION_TEMPLATE_ID]);
        Configuration::updateValue(CustplaceConstants::CONFIG_EXCLUDED_CATEGORIES, $values[CustplaceConstants::CONFIG_EXCLUDED_CATEGORIES]);

        return true;
    }

    /**
     * Update trust badge configuration
     *
     * @return bool
     */
    public function updateTrustBadgeConfiguration(): bool
    {
        Configuration::updateValue(
            CustplaceConstants::CONFIG_WIDGET_TRUST_BADGE,
            Tools::getValue(CustplaceConstants::CONFIG_WIDGET_TRUST_BADGE, true)
        );
        return true;
    }

    /**
     * Update test mode configuration
     *
     * @return bool
     */
    public function updateTestModeConfiguration(): bool
    {
        Configuration::updateValue(
            CustplaceConstants::CONFIG_TEST_MODE,
            Tools::getValue(CustplaceConstants::CONFIG_TEST_MODE, 0)
        );
        return true;
    }

    /**
     * Update widget configuration
     *
     * @return bool
     */
    public function updateWidgetConfiguration(): bool
    {
        $values = $this->getConfigFieldsValues();
        
        if (!$this->validateWidgetConfiguration($values)) {
            return false;
        }

        Configuration::updateValue(CustplaceConstants::CONFIG_WIDGET_PRODUCT_REVIEWS, $values[CustplaceConstants::CONFIG_WIDGET_PRODUCT_REVIEWS]);
        
        // Encrypt widget token before storing
        if (!empty($values[CustplaceConstants::CONFIG_WIDGET_TOKEN])) {
            $this->setWidgetToken($values[CustplaceConstants::CONFIG_WIDGET_TOKEN]);
        }
        Configuration::updateValue(CustplaceConstants::CONFIG_WIDGET_FIRST_COLOR, $values[CustplaceConstants::CONFIG_WIDGET_FIRST_COLOR]);
        Configuration::updateValue(CustplaceConstants::CONFIG_WIDGET_SECOND_COLOR, $values[CustplaceConstants::CONFIG_WIDGET_SECOND_COLOR]);
        Configuration::updateValue(CustplaceConstants::CONFIG_WIDGET_SUBRATINGS, $values[CustplaceConstants::CONFIG_WIDGET_SUBRATINGS]);
        Configuration::updateValue(CustplaceConstants::CONFIG_WIDGET_TITLE_RATING, $values[CustplaceConstants::CONFIG_WIDGET_TITLE_RATING]);

        return true;
    }

    /**
     * Check if API is enabled and properly configured
     *
     * @return bool
     */
    public function isApiEnabled(): bool
    {
        return Configuration::get(CustplaceConstants::CONFIG_API_ENABLED) == '1' &&
               !empty(Configuration::get(CustplaceConstants::CONFIG_API_CLIENT)) &&
               !empty(Configuration::get(CustplaceConstants::CONFIG_API_KEY));
    }

    /**
     * Check if trust badge widget is enabled
     *
     * @return bool
     */
    public function isTrustBadgeEnabled(): bool
    {
        return Configuration::get(CustplaceConstants::CONFIG_WIDGET_TRUST_BADGE) == '1';
    }

    /**
     * Check if product reviews widget is enabled
     *
     * @return bool
     */
    public function isProductReviewsEnabled(): bool
    {
        return Configuration::get(CustplaceConstants::CONFIG_WIDGET_PRODUCT_REVIEWS) == '1';
    }

    /**
     * Check if product title rating is enabled
     *
     * @return bool
     */
    public function isProductTitleRatingEnabled(): bool
    {
        return Configuration::get(CustplaceConstants::CONFIG_WIDGET_TITLE_RATING) == '1';
    }

    /**
     * Get client ID
     *
     * @return int
     */
    public function getClientId(): int
    {
        return (int)Configuration::get(CustplaceConstants::CONFIG_API_CLIENT);
    }

    /**
     * Get API key (automatically decrypted)
     *
     * @return string
     */
    public function getApiKey(): string
    {
        $encryptedKey = Configuration::get(CustplaceConstants::CONFIG_API_KEY);
        return $this->encryptionService->decrypt($encryptedKey);
    }
    
    /**
     * Set API key (automatically encrypted)
     *
     * @param string $apiKey
     * @return bool
     */
    public function setApiKey(string $apiKey): bool
    {
        $encryptedKey = $this->encryptionService->encrypt($apiKey);
        return Configuration::updateValue(CustplaceConstants::CONFIG_API_KEY, $encryptedKey);
    }
    
    /**
     * Get API key for display (masked)
     *
     * @return string
     */
    public function getApiKeyForDisplay(): string
    {
        $apiKey = $this->getApiKey();
        return $this->encryptionService->maskForDisplay($apiKey);
    }

    /**
     * Get solicitation delay in days
     *
     * @return int
     */
    public function getSolicitationDelay(): int
    {
        return (int)Configuration::get(CustplaceConstants::CONFIG_DELAY_SOLICITATION);
    }

    /**
     * Get invitation template ID
     *
     * @return int|null
     */
    public function getInvitationTemplateId(): ?int
    {
        $templateId = Configuration::get(CustplaceConstants::CONFIG_INVITATION_TEMPLATE_ID);
        return !empty($templateId) ? (int)$templateId : null;
    }

    /**
     * Check if test mode is enabled
     *
     * @return bool
     */
    public function isTestMode(): bool
    {
        return Configuration::get(CustplaceConstants::CONFIG_TEST_MODE) == '1';
    }

    /**
     * Get API endpoint based on test mode
     *
     * @return string
     */
    public function getApiEndpoint(): string
    {
        return $this->isTestMode() ? CustplaceConstants::API_ENDPOINT_TEST : CustplaceConstants::API_ENDPOINT_BASE;
    }

    /**
     * Get widget product reviews URL based on test mode
     *
     * @return string
     */
    public function getWidgetProductReviewsUrl(): string
    {
        if ($this->isTestMode()) {
            return str_replace(CustplaceConstants::WIDGET_DOMAIN_PROD, CustplaceConstants::WIDGET_DOMAIN_TEST, CustplaceConstants::WIDGET_PRODUCT_REVIEWS_JS);
        }
        return CustplaceConstants::WIDGET_PRODUCT_REVIEWS_JS;
    }

    /**
     * Get widget trust badge URL based on test mode
     *
     * @return string
     */
    public function getWidgetTrustBadgeUrl(): string
    {
        if ($this->isTestMode()) {
            return str_replace(CustplaceConstants::WIDGET_DOMAIN_PROD, CustplaceConstants::WIDGET_DOMAIN_TEST, CustplaceConstants::WIDGET_TRUST_BADGE_JS);
        }
        return CustplaceConstants::WIDGET_TRUST_BADGE_JS;
    }

    /**
     * Get widget token (automatically decrypted)
     *
     * @return string
     */
    public function getWidgetToken(): string
    {
        $encryptedToken = Configuration::get(CustplaceConstants::CONFIG_WIDGET_TOKEN);
        return $this->encryptionService->decrypt($encryptedToken);
    }
    
    /**
     * Set widget token (automatically encrypted)
     *
     * @param string $token
     * @return bool
     */
    public function setWidgetToken(string $token): bool
    {
        $encryptedToken = $this->encryptionService->encrypt($token);
        return Configuration::updateValue(CustplaceConstants::CONFIG_WIDGET_TOKEN, $encryptedToken);
    }

    /**
     * Get widget primary color
     *
     * @return string
     */
    public function getWidgetPrimaryColor(): string
    {
        return Configuration::get(CustplaceConstants::CONFIG_WIDGET_FIRST_COLOR);
    }

    /**
     * Get widget secondary color
     *
     * @return string
     */
    public function getWidgetSecondaryColor(): string
    {
        return Configuration::get(CustplaceConstants::CONFIG_WIDGET_SECOND_COLOR);
    }

    /**
     * Check if subratings are enabled
     *
     * @return bool
     */
    public function areSubratingsEnabled(): bool
    {
        return Configuration::get(CustplaceConstants::CONFIG_WIDGET_SUBRATINGS) === '1';
    }
    
    /**
     * Get trigger statuses as array
     *
     * @return array
     */
    public function getTriggerStatuses(): array
    {
        $triggerStatuses = Configuration::get(CustplaceConstants::CONFIG_TRIGGER_STATUSES);
        
        if (empty($triggerStatuses)) {
            return CustplaceConstants::DEFAULT_TRIGGER_STATUSES;
        }
        
        $decoded = json_decode($triggerStatuses, true);
        return is_array($decoded) ? array_map('intval', $decoded) : CustplaceConstants::DEFAULT_TRIGGER_STATUSES;
    }
    
    /**
     * Set trigger statuses
     *
     * @param array $statusIds
     * @return bool
     */
    public function setTriggerStatuses(array $statusIds): bool
    {
        $statusIds = array_map('intval', array_filter($statusIds));
        return Configuration::updateValue(CustplaceConstants::CONFIG_TRIGGER_STATUSES, json_encode($statusIds));
    }
    
    /**
     * Get trigger statuses formatted for form checkboxes
     *
     * @return array
     */
    public function getTriggerStatusesForForm(): array
    {
        $triggerStatuses = $this->getTriggerStatuses();
        $formValues = [];
        
        // For checkbox fields, PrestaShop expects field names like: custplace_trigger_statuses_1, custplace_trigger_statuses_2, etc.
        foreach ($triggerStatuses as $statusId) {
            $fieldName = CustplaceConstants::CONFIG_TRIGGER_STATUSES . '_' . $statusId;
            $formValues[$fieldName] = 1;
        }
        
        return $formValues;
    }
    
    /**
     * Get excluded categories as array from CSV input
     *
     * @return array
     */
    public function getExcludedCategories(): array
    {
        $excludedCategories = Configuration::get(CustplaceConstants::CONFIG_EXCLUDED_CATEGORIES);
        
        if (empty($excludedCategories)) {
            return [];
        }
        
        // Parse CSV with flexible spacing: "1,2,3" or "1, 2, 3" or "1,2, 3"
        $categoryIds = array_map('trim', explode(',', $excludedCategories));
        $categoryIds = array_filter($categoryIds, function($id) {
            return is_numeric($id) && $id > 0;
        });
        
        return array_map('intval', $categoryIds);
    }
    
    /**
     * Update trigger status configuration
     *
     * @return bool
     */
    public function updateTriggerStatusConfiguration(): bool
    {
        // For checkbox fields, PrestaShop sends values like: custplace_trigger_statuses_1, custplace_trigger_statuses_2, etc.
        $triggerStatuses = [];
        $allOrderStates = \OrderState::getOrderStates(\Context::getContext()->language->id);
        
        foreach ($allOrderStates as $orderState) {
            $checkboxName = CustplaceConstants::CONFIG_TRIGGER_STATUSES . '_' . $orderState['id_order_state'];
            if (Tools::getValue($checkboxName)) {
                $triggerStatuses[] = (int)$orderState['id_order_state'];
            }
        }
        
        return $this->setTriggerStatuses($triggerStatuses);
    }
    
    /**
     * Migrate existing plain text API keys to encrypted format
     *
     * @return bool
     */
    public function migrateApiKeysToEncrypted(): bool
    {
        try {
            // Migrate API key
            $apiKey = Configuration::get(CustplaceConstants::CONFIG_API_KEY);
            if (!empty($apiKey) && !$this->encryptionService->isEncrypted($apiKey)) {
                $this->setApiKey($apiKey);
            }
            
            // Migrate widget token
            $widgetToken = Configuration::get(CustplaceConstants::CONFIG_WIDGET_TOKEN);
            if (!empty($widgetToken) && !$this->encryptionService->isEncrypted($widgetToken)) {
                $this->setWidgetToken($widgetToken);
            }
            
            // Migrate trigger statuses if not set
            if (empty(Configuration::get(CustplaceConstants::CONFIG_TRIGGER_STATUSES))) {
                $this->setTriggerStatuses(CustplaceConstants::DEFAULT_TRIGGER_STATUSES);
            }
            
            return true;
        } catch (\Exception $e) {
            error_log('Custplace API key migration error: ' . $e->getMessage());
            return false;
        }
    }
}