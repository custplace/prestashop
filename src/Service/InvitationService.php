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
use Custplace\Repository\CustplaceRepository;
use CustplaceApi;
use Context;
use ImageType;
use Product;

class InvitationService
{
    private $repository;
    private $configService;

    public function __construct(CustplaceRepository $repository, ConfigurationService $configService)
    {
        $this->repository = $repository;
        $this->configService = $configService;
    }

    /**
     * Prepare and send invitation for order
     *
     * @param int $orderId
     * @return void
     * @throws \Exception
     */
    public function prepareInvitationData(int $orderId): void
    {
        try {
            // Check if invitation already sent successfully
            if ($this->repository->hasSuccessfulRecord($orderId)) {
                return;
            }

            // Check if API is properly configured
            if (!$this->configService->isApiEnabled()) {
                throw new \Exception('API is not properly configured');
            }

            // Insert pending record
            $recordId = $this->repository->insert($orderId, CustplaceConstants::STATUS_PENDING);
            if (!$recordId) {
                throw new \Exception('Failed to create custplace record');
            }

            // Prepare invitation data
            $invitationData = $this->buildInvitationData($orderId);
            
            // Allow other modules to modify invitation data
            $invitationData = $this->applyInvitationDataFilters($invitationData, $orderId);
            
            // Send invitation via API
            $this->sendInvitation($recordId, $invitationData);

        } catch (\Exception $e) {
            // Log error but don't throw to avoid breaking order flow
            error_log('Custplace invitation error: ' . $e->getMessage());
        }
    }

    /**
     * Build invitation data from order information
     *
     * @param int $orderId
     * @return array
     */
    public function buildInvitationData(int $orderId): array
    {
        $orderDetails = $this->repository->getOrderDetails($orderId);
        
        if (empty($orderDetails)) {
            throw new \Exception('Order details not found');
        }

        $invitation = [
            'order_ref' => $orderDetails[0]['ord_ref'],
            'order_date' => date('d/m/Y', strtotime($orderDetails[0]['date_add'])),
            'firstname' => $orderDetails[0]['firstname'],
            'lastname' => $orderDetails[0]['lastname'],
            'email' => $orderDetails[0]['email'],
            'type' => CustplaceConstants::INVITATION_TYPE_POST_REVIEW,
            'send_at' => $this->calculateSendTime(),
            'lang' => $this->getCustomerLanguageFromOrder($orderId),
            'products' => []
        ];

        // Add template ID if configured
        $templateId = $this->configService->getInvitationTemplateId();
        if ($templateId !== null) {
            $invitation['template_id'] = $templateId;
        }

        foreach ($orderDetails as $orderDetail) {
            $product = new Product($orderDetail['id_product']);
            $invitation['products'][] = [
                'sku' => $orderDetail['prod_ref'],
                'name' => $orderDetail['product_name'],
                'image' => $this->getProductImageUrl($product, $orderDetail['id_image']),
                'url' => Context::getContext()->link->getProductLink($product),
            ];
        }

        return $invitation;
    }

    /**
     * Send invitation via Custplace API
     *
     * @param int $recordId
     * @param array $invitationData
     * @return void
     */
    private function sendInvitation(int $recordId, array $invitationData): void
    {
        $orderRef = $invitationData['order_ref'] ?? 'Unknown';
        
        try {
            $custplaceApi = new CustplaceApi(
                $this->configService->getClientId(),
                $this->configService->getApiKey(),
                $this->configService->getApiEndpoint()
            );

            $response = $custplaceApi->sendInvitation($invitationData);
            
            if ($response) {
                $this->repository->updateStatus(
                    $recordId,
                    $response['status_order'],
                    $response['sollicitation_id']
                );
                
                // Log successful invitation
                if ($response['status_order'] === CustplaceConstants::STATUS_SUCCESS) {
                    \PrestaShopLogger::addLog(
                        'Custplace API Success: Invitation sent for order ' . $orderRef . ' - ID: ' . $response['sollicitation_id'],
                        \PrestaShopLogger::LOG_SEVERITY_LEVEL_INFORMATIVE,
                        null,
                        'Module',
                        'custplace'
                    );
                } else {
                    \PrestaShopLogger::addLog(
                        'Custplace API Warning: Invitation for order ' . $orderRef . ' returned status: ' . $response['status_order'],
                        \PrestaShopLogger::LOG_SEVERITY_LEVEL_WARNING,
                        null,
                        'Module',
                        'custplace'
                    );
                }
            }
        } catch (\Exception $e) {
            \PrestaShopLogger::addLog(
                'Custplace API Error: Failed to send invitation for order ' . $orderRef . ' - ' . $e->getMessage(),
                \PrestaShopLogger::LOG_SEVERITY_LEVEL_ERROR,
                null,
                'Module',
                'custplace'
            );
            // Re-throw to maintain existing error handling
            throw $e;
        }
    }

    /**
     * Calculate when to send the invitation
     *
     * @return string
     */
    private function calculateSendTime(): string
    {
        $delay = $this->configService->getSolicitationDelay();
        $dateNow = date('Y-m-d H:i');
        
        if ($delay > 0) {
            $dateNow = date('Y-m-d H:i', strtotime($dateNow . ' +' . $delay . ' day'));
        }
        
        return $dateNow;
    }

    /**
     * Get product image URL
     *
     * @param Product $product
     * @param int $imageId
     * @return string
     */
    private function getProductImageUrl(Product $product, int $imageId): string
    {
        $context = Context::getContext();
        
        return $context->link->getImageLink(
            $product->link_rewrite[$context->language->id],
            $imageId,
            ImageType::getFormattedName('large')
        );
    }

    /**
     * Check if order should trigger invitation
     *
     * @param int $orderStatusId
     * @param int $orderId
     * @return bool
     */
    public function shouldTriggerInvitation(int $orderStatusId, int $orderId = null): bool
    {
        $triggerStatuses = $this->configService->getTriggerStatuses();
        if (!in_array($orderStatusId, $triggerStatuses)) {
            return false;
        }
        
        // Check if order contains excluded categories
        if ($orderId && $this->orderContainsExcludedCategories($orderId)) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Get customer's language from order
     *
     * @param int $orderId
     * @return string
     */
    private function getCustomerLanguageFromOrder(int $orderId): string
    {
        $sql = 'SELECT l.iso_code 
                FROM ' . _DB_PREFIX_ . 'orders o
                JOIN ' . _DB_PREFIX_ . 'lang l ON o.id_lang = l.id_lang
                WHERE o.id_order = ' . (int)$orderId;
                
        $result = \Db::getInstance()->getValue($sql);
        
        // Fallback to default shop language if not found
        return $result ?: \Context::getContext()->language->iso_code;
    }
    
    /**
     * Apply invitation data filters to allow other modules to modify the data
     *
     * @param array $invitationData
     * @param int $orderId
     * @return array
     */
    private function applyInvitationDataFilters(array $invitationData, int $orderId): array
    {
        try {
            // Allow other modules to modify invitation data via hook
            $hookParams = [
                'invitation_data' => $invitationData,
                'order_id' => $orderId,
            ];
            
            $result = \Hook::exec('actionCustplaceInvitationData', $hookParams, null, true);
            
            // If any module returned modified data, use it
            if (is_array($result)) {
                foreach ($result as $moduleResult) {
                    if (is_array($moduleResult) && isset($moduleResult['invitation_data'])) {
                        $invitationData = $moduleResult['invitation_data'];
                        break; // Use first valid modification
                    }
                }
            }
            
            // Validate the modified data to ensure it has required fields
            $requiredFields = ['order_ref', 'firstname', 'lastname', 'email', 'type', 'send_at', 'lang', 'products'];
            foreach ($requiredFields as $field) {
                if (!isset($invitationData[$field])) {
                    error_log('Custplace: Missing required field "' . $field . '" after applying filters. Using original data.');
                    return $this->buildInvitationData($orderId); // Fallback to original data
                }
            }
            
        } catch (\Exception $e) {
            error_log('Custplace: Error applying invitation data filters: ' . $e->getMessage());
            // Return original data if hook execution fails
        }
        
        return $invitationData;
    }
    
    /**
     * Check if order contains products from excluded categories
     *
     * @param int $orderId
     * @return bool
     */
    private function orderContainsExcludedCategories(int $orderId): bool
    {
        $excludedCategories = $this->configService->getExcludedCategories();
        if (empty($excludedCategories)) {
            return false;
        }
        
        $orderDetails = $this->repository->getOrderDetails($orderId);
        if (empty($orderDetails)) {
            return false;
        }
        
        foreach ($orderDetails as $orderDetail) {
            $productId = (int)$orderDetail['id_product'];
            $product = new \Product($productId);
            
            // Get all categories for this product (including parent categories)
            $productCategories = $product->getCategories();
            
            // Check if any product category is in excluded list
            foreach ($productCategories as $categoryId) {
                if (in_array((int)$categoryId, $excludedCategories)) {
                    return true;
                }
            }
        }
        
        return false;
    }

    /**
     * Check if manual invitation can be sent for order
     *
     * @param int $orderId
     * @return bool
     */
    public function canSendManualInvitation(int $orderId): bool
    {
        // Check if already sent successfully
        if ($this->repository->hasSuccessfulRecord($orderId)) {
            return false;
        }

        // Check current order status
        $orderStates = $this->repository->getCurrentOrderState($orderId);
        if (empty($orderStates)) {
            return false;
        }

        $currentState = (int)$orderStates[0]['current_state'];
        
        // Only allow for configured trigger states and check excluded categories
        return $this->shouldTriggerInvitation($currentState, $orderId);
    }
}