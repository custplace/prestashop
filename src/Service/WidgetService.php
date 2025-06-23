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

namespace Custplace\Service;

use Custplace\Constants\CustplaceConstants;
use Context;
use ImageType;
use Product;
use Twig_Environment;

class WidgetService
{
    private $module;
    private $configService;

    public function __construct($module, ConfigurationService $configService)
    {
        $this->module = $module;
        $this->configService = $configService;
    }

    /**
     * Render product reviews widget
     *
     * @param int $productId
     * @return string|null
     */
    public function renderProductReviewsWidget(int $productId): ?string
    {
        if (!$this->configService->isProductReviewsEnabled()) {
            return null;
        }

        $product = new Product($productId);
        
        $this->assignProductReviewsVariables($product);
        
        return $this->module->fetch(CustplaceConstants::TEMPLATE_WIDGET_PRODUCT);
    }

    /**
     * Render trust badge widget
     *
     * @return string|null
     */
    public function renderTrustBadgeWidget(): ?string
    {
        if (!$this->configService->isTrustBadgeEnabled()) {
            return null;
        }

        $context = Context::getContext();
        if ($context->controller->php_self !== 'index') {
            return null;
        }

        $context->smarty->assign('data_id', $this->configService->getClientId());
        
        return $this->module->fetch(CustplaceConstants::TEMPLATE_WIDGET_BADGE);
    }

    /**
     * Render product title rating widget
     *
     * @return string|null
     */
    public function renderProductTitleRatingWidget(): ?string
    {
        if (!$this->configService->isProductReviewsEnabled() || 
            !$this->configService->isProductTitleRatingEnabled()) {
            return null;
        }

        return $this->module->fetch(CustplaceConstants::TEMPLATE_WIDGET_PRODUCT_TITLE);
    }

    /**
     * Render admin order side panel
     *
     * @param array $custplaceData
     * @return string
     */
    public function renderAdminOrderPanel(array $custplaceData): string
    {
        return $this->renderTwigTemplate($this->getModuleTemplatePath() . CustplaceConstants::TEMPLATE_ADMIN_CUSTPLACE, [
            'values' => $custplaceData,
        ]);
    }

    /**
     * Load required CSS and JavaScript for product pages
     *
     * @return void
     */
    public function loadProductPageAssets(): void
    {
        try {
            $context = Context::getContext();
            
            if ($context->controller->php_self === 'product' && $this->configService->isProductReviewsEnabled()) {
                // Add CSS safely
                try {
                    $context->controller->addCSS($this->module->getPathUri() . 'views/css/' . CustplaceConstants::CSS_CUSTPLACE, 'all');
                } catch (\Exception $e) {
                    // CSS loading failure should not break page - log and continue
                    error_log('Custplace CSS loading failed: ' . $e->getMessage());
                }
                
                // Add external JavaScript safely
                try {
                    $context->controller->registerJavascript(
                        CustplaceConstants::JS_MODULE_PRODUCT_REVIEWS,
                        $this->configService->getWidgetProductReviewsUrl(),
                        [
                            'position' => 'bottom',
                            'priority' => 0,
                            'attribute' => 'async',
                            'server' => 'remote',
                        ]
                    );
                } catch (\Exception $e) {
                    // External JS loading failure should not break page - log and continue
                    error_log('Custplace product reviews JS loading failed: ' . $e->getMessage());
                }
            }
        } catch (\Exception $e) {
            // Overall widget loading failure should not break page
            error_log('Custplace product page assets loading failed: ' . $e->getMessage());
        }
    }

    /**
     * Load required JavaScript for home page
     *
     * @return void
     */
    public function loadHomePageAssets(): void
    {
        try {
            $context = Context::getContext();
            
            if ($context->controller->php_self === 'index' && $this->configService->isTrustBadgeEnabled()) {
                try {
                    $context->controller->registerJavascript(
                        CustplaceConstants::JS_MODULE_TRUST_BADGE,
                        $this->configService->getWidgetTrustBadgeUrl(),
                        [
                            'position' => 'bottom',
                            'priority' => 0,
                            'attribute' => 'async',
                            'server' => 'remote',
                        ]
                    );
                } catch (\Exception $e) {
                    // External JS loading failure should not break home page - log and continue
                    error_log('Custplace trust badge JS loading failed: ' . $e->getMessage());
                }
            }
        } catch (\Exception $e) {
            // Overall widget loading failure should not break home page
            error_log('Custplace home page assets loading failed: ' . $e->getMessage());
        }
    }

    /**
     * Assign variables for product reviews widget
     *
     * @param Product $product
     * @return void
     */
    private function assignProductReviewsVariables(Product $product): void
    {
        $context = Context::getContext();
        
        $context->smarty->assign('data_id', $this->configService->getClientId());
        $context->smarty->assign('custplace_api_key', $this->configService->getWidgetToken());
        $context->smarty->assign('custplace_wap_first_color', $this->configService->getWidgetPrimaryColor());
        $context->smarty->assign('custplace_wap_second_color', $this->configService->getWidgetSecondaryColor());
        $context->smarty->assign('custplace_wap_subratings', $this->configService->areSubratingsEnabled() ? 'true' : 'false');
        $context->smarty->assign('product_sku', $product->reference);
    }

    /**
     * Render Twig template
     *
     * @param string $template
     * @param array $params
     * @return string
     */
    private function renderTwigTemplate(string $template, array $params = []): string
    {
        /** @var Twig_Environment $twig */
        $twig = $this->module->get('twig');
        
        return $twig->render($template, $params);
    }

    /**
     * Get module template path
     *
     * @return string
     */
    public function getModuleTemplatePath(): string
    {
        return sprintf('@Modules/%s/views/templates/hook/', $this->module->name);
    }
}