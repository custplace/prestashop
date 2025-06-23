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
if (!defined('_PS_VERSION_')) {
    exit;
}

require_once dirname(__FILE__) . '/custplaceapi.php';
require_once dirname(__FILE__) . '/src/Constants/CustplaceConstants.php';
require_once dirname(__FILE__) . '/src/Repository/CustplaceRepository.php';
require_once dirname(__FILE__) . '/src/Service/EncryptionService.php';
require_once dirname(__FILE__) . '/src/Service/ConfigurationService.php';
require_once dirname(__FILE__) . '/src/Service/WidgetService.php';
require_once dirname(__FILE__) . '/src/Service/InvitationService.php';

use Custplace\Constants\CustplaceConstants;
use Custplace\Repository\CustplaceRepository;
use Custplace\Service\EncryptionService;
use Custplace\Service\ConfigurationService;
use Custplace\Service\WidgetService;
use Custplace\Service\InvitationService;

class Custplace extends Module
{
    private CustplaceRepository $repository;
    private ConfigurationService $configService;
    private WidgetService $widgetService;
    private InvitationService $invitationService;

    public function __construct()
    {
        $this->name = 'custplace';
        $this->version = '2.0.0';
        $this->tab = 'advertising_marketing';
        $this->author = 'Custplace';
        $this->need_instance = 0;
        $this->emailSupport = 'support@custplace.com';
        $this->ps_versions_compliancy = [
            'min' => '1.7.0.0',
            'max' => _PS_VERSION_,
        ];
        $this->bootstrap = true;

        parent::__construct();
        $this->displayName = $this->trans('Custplace.com', [], 'Modules.Custplace.Custplace');
        $this->description = $this->trans('Custplace.com est une plateforme web de gestion d\'avis clients et d\'enquêtes de satisfaction.', [], 'Modules.Custplace.Custplace');
        $this->confirmUninstall = $this->trans('Voulez-vous vraiment le désinstaller ?', [], 'Modules.Custplace.Custplace');

        $this->initializeServices();
    }

    /**
     * Initialize service dependencies
     */
    private function initializeServices(): void
    {
        $this->repository = new CustplaceRepository();
        $this->configService = new ConfigurationService();
        $this->widgetService = new WidgetService($this, $this->configService);
        $this->invitationService = new InvitationService($this->repository, $this->configService);

        // Migrate existing plain text API keys to encrypted format
        $this->configService->migrateApiKeysToEncrypted();
    }

    /**
     * Install module
     *
     * @return bool
     */
    public function install(): bool
    {
        return $this->repository->createTable() &&
               parent::install() &&
               $this->registerHook('displayHeader') &&
               $this->registerHook('actionOrderStatusUpdate') &&
               $this->registerHook('displayAdminOrderSideBottom') &&
               $this->registerHook('displayFooterProduct') &&
               $this->registerHook('displayProductPriceBlock') &&
               $this->registerHook('displayFooterAfter') &&
               $this->registerHook('ActionGetAdminOrderButtons');
    }

    /**
     * Uninstall module
     *
     * @return bool
     */
    public function uninstall(): bool
    {
        return $this->repository->dropTable() &&
               parent::uninstall() &&
               $this->unregisterHook('displayHeader') &&
               $this->unregisterHook('actionOrderStatusUpdate') &&
               $this->unregisterHook('displayAdminOrderSideBottom') &&
               $this->unregisterHook('displayFooterProduct') &&
               $this->unregisterHook('displayFooterAfter') &&
               $this->unregisterHook('ActionGetAdminOrderButtons');
    }

    /**
     * Hook: displayAdminOrderSideBottom
     *
     * @param array $params
     * @return string
     */
    public function hookDisplayAdminOrderSideBottom(array $params): string
    {
        try {
            $custplaceData = $this->repository->getByOrderId((int)$params['id_order']);

            // Debug: If no data, show empty panel with note
            if (empty($custplaceData)) {
                $custplaceData = [[
                    'dateRequest' => 'No invitation sent yet',
                    'status_order' => 'none'
                ]];
            }

            return $this->widgetService->renderAdminOrderPanel($custplaceData);
        } catch (\Exception $e) {
            // Try to create table if it doesn't exist
            if (strpos($e->getMessage(), 'doesn\'t exist') !== false) {
                $this->repository->createTable();
            }
            
            // Fallback: Simple HTML if services fail
            return '<div class="card">
                <div class="card-header">
                    <h3>Custplace Debug</h3>
                </div>
                <div class="card-body">
                    <p>Error: ' . htmlspecialchars($e->getMessage()) . '</p>
                    <p>Order ID: ' . (int)$params['id_order'] . '</p>
                    <p><strong>Solution:</strong> Go to Module Manager and reinstall the Custplace module</p>
                </div>
            </div>';
        }
    }


    /**
     * Hook: displayFooterProduct
     *
     * @param array $params
     * @return string|null
     */
    public function hookDisplayFooterProduct(array $params): ?string
    {
        $productId = (int)Tools::getValue('id_product');
        return $this->widgetService->renderProductReviewsWidget($productId);
    }

    /**
     * Hook: displayFooterAfter
     *
     * @param array $params
     * @return string|null
     */
    public function hookDisplayFooterAfter(array $params): ?string
    {
        return $this->widgetService->renderTrustBadgeWidget();
    }

    /**
     * Hook: displayProductPriceBlock
     *
     * @param array $params
     * @return string|null
     */
    public function hookDisplayProductPriceBlock(array $params): ?string
    {
        if ($params['type'] === 'after_price') {
            return $this->widgetService->renderProductTitleRatingWidget();
        }
        return null;
    }

    /**
     * Hook: displayHeader
     *
     * @return void
     */
    public function hookDisplayHeader(): void
    {
        $this->widgetService->loadProductPageAssets();
        $this->widgetService->loadHomePageAssets();
    }

    /**
     * Hook: ActionGetAdminOrderButtons
     *
     * @param array $params
     * @return void
     */
    public function hookActionGetAdminOrderButtons(array $params): void
    {
        $orderId = (int)$params['id_order'];

        if ($this->invitationService->canSendManualInvitation($orderId)) {
            $this->addManualInvitationButton($orderId, $params['actions_bar_buttons_collection']);
        }
    }

    /**
     * Add manual invitation button to order admin page
     *
     * @param int $orderId
     * @param mixed $actionsBarButtonsCollection
     * @return void
     */
    private function addManualInvitationButton(int $orderId, $actionsBarButtonsCollection): void
    {
        $router = $this->get('router');
        $custplacePostOrder = $router->generate('cusplace_api_post', ['id_order' => $orderId]);

        $actionsBarButtonsCollection->add(
            new \PrestaShopBundle\Controller\Admin\Sell\Order\ActionsBarButton(
                'btn-success',
                ['href' => $custplacePostOrder],
                $this->trans("Solicitez l'avis du client", [], 'Modules.Custplace.Custplace')
            )
        );
    }

    /**
     * Prepare invitation data for order (static for backward compatibility)
     *
     * @param int $orderId
     * @return void
     */
    public static function prepareInvitationData(int $orderId): void
    {
        try {
            $instance = new self();
            $instance->invitationService->prepareInvitationData($orderId);
        } catch (\Exception $e) {
            // Critical: Log error but don't break admin interface
            error_log('Custplace manual invitation error: ' . $e->getMessage());
            \PrestaShopLogger::addLog(
                'Custplace: Manual invitation failed for order ' . $orderId . ' - ' . $e->getMessage(),
                \PrestaShopLogger::LOG_SEVERITY_LEVEL_ERROR,
                null,
                'Module',
                'custplace'
            );
            // Note: Controller should handle user feedback based on logs
        }
    }

    /**
     * Hook: actionOrderStatusUpdate
     *
     * @param array $params
     * @return void
     */
    public function hookActionOrderStatusUpdate(array $params): void
    {
        try {
            $orderStatusId = (int)$params['newOrderStatus']->id;
            $orderId = (int)$params['id_order'];

            if ($this->invitationService->shouldTriggerInvitation($orderStatusId, $orderId)) {
                $this->invitationService->prepareInvitationData($orderId);
            }
        } catch (\Exception $e) {
            // Critical: Never break order status updates - log error and continue
            error_log('Custplace order status hook error: ' . $e->getMessage());
            \PrestaShopLogger::addLog(
                'Custplace: Order status hook failed for order ' . ($orderId ?? 'unknown') . ' - ' . $e->getMessage(),
                \PrestaShopLogger::LOG_SEVERITY_LEVEL_ERROR,
                null,
                'Module',
                'custplace'
            );
        }
    }

    /**
     * Get order info for invitation (kept for backward compatibility)
     *
     * @param int $orderId
     * @return array
     */
    public function getOrderInfo(int $orderId): array
    {
        return $this->invitationService->buildInvitationData($orderId);
    }


    /**
     * Get configuration field values
     *
     * @return array
     */
    public function getConfigFieldsValues(): array
    {
        return $this->configService->getConfigFieldsValues();
    }

    /**
     * Render configuration form
     *
     * @return string
     */
    public function renderForm(): string
    {
        $default_lang = (int) Configuration::get('PS_LANG_DEFAULT');
        $config_values = $this->getConfigFieldsValues();
        $fields_form = [
            'form' => [
                'legend' => [
                    'title' => $this->trans('Sollicitation des avis', [], 'Modules.Custplace.Custplace'),
                    'icon' => 'icon-lock',
                ],
                'input' => [
                    [
                        'type' => 'switch',
                        'label' => $this->trans('Activer la sollicitation', [], 'Modules.Custplace.Custplace'),
                        'name' => 'custplace_api_enabled',
                        'is_bool' => true,
                        'values' => [
                            [
                                'id' => 'active_on',
                                'value' => 1,
                                'label' => $this->trans('Oui', [], 'Modules.Custplace.Custplace'),
                            ],
                            [
                                'id' => 'active_off',
                                'value' => 0,
                                'label' => $this->trans('Non', [], 'Modules.Custplace.Custplace'),
                            ],
                        ],
                    ],
                    [
                        'type' => 'html',
                        'label' => $this->trans('ID client', [], 'Modules.Custplace.Custplace'),
                        'name' => 'custplace_api_client',
                        'required' => true,
                        'html_content' => '<input type="number" autocomplete="off" name="custplace_api_client" class="form-control" value="' . $config_values['custplace_api_client'] . '">',
                    ],
                    [
                        'type' => 'html',
                        'label' => $this->trans('Clé API ', [], 'Modules.Custplace.Custplace'),
                        'name' => 'custplace_api_key',
                        'required' => true,
                        'html_content' => '<input type="password" autocomplete="off" name="custplace_api_key" class="form-control" value="' . $config_values['custplace_api_key'] . '">',
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->trans('Delai de sollicitation', [], 'Modules.Custplace.Custplace'),
                        'name' => 'custplace_delai_sollicitation',
                        'desc' => $this->trans('Nombre de jours après la commande. Max 30 jours.', [], 'Modules.Custplace.Custplace'),
                        'required' => true
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->trans('ID template d\'invitation', [], 'Modules.Custplace.Custplace'),
                        'name' => 'custplace_invitation_template_id',
                        'desc' => $this->trans('Optionnel. ID du template d\'invitation personnalisé.', [], 'Modules.Custplace.Custplace'),
                        'required' => false
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->trans('Catégories exclues', [], 'Modules.Custplace.Custplace'),
                        'name' => 'custplace_excluded_categories',
                        'desc' => $this->trans('IDs des catégories séparés par des virgules (ex: 1,2,3). Les commandes contenant des produits de ces catégories ne recevront pas d\'invitation.', [], 'Modules.Custplace.Custplace'),
                        'required' => false
                    ],
                    [
                        'type' => 'checkbox',
                        'label' => $this->trans('Statuts déclencheurs', [], 'Modules.Custplace.Custplace'),
                        'name' => 'custplace_trigger_statuses',
                        'desc' => $this->trans('Sélectionnez les statuts de commande qui déclenchent automatiquement l\'envoi d\'invitations', [], 'Modules.Custplace.Custplace'),
                        'required' => true,
                        'values' => [
                            'query' => $this->getOrderStatesForForm(),
                            'id' => 'id_order_state',
                            'name' => 'name'
                        ]
                    ]
                ],
                'submit' => [
                    'title' => $this->trans('Enregistrer', [], 'Modules.Custplace.Custplace'),
                    'name' => 'submission_api',
                ],
            ],
        ];
        $fields_form_2 = [
            'form' => [
                'legend' => [
                    'title' => $this->trans('Badge de confiance', [], 'Modules.Custplace.Custplace'),
                    'icon' => 'icon-bell',
                ],
                'input' => [
                    [
                        'type' => 'switch',
                        'label' => $this->trans('Widget Sceau de confiance', [], 'Modules.Custplace.Custplace'),
                        'name' => 'custplace_widget_sceau_confiance',
                        'is_bool' => true,
                        'desc' => $this->trans('Activer le widget Badge de confiance', [], 'Modules.Custplace.Custplace'),
                        'values' => [
                            [
                                'id' => 'active_on',
                                'value' => 1,
                                'label' => $this->trans('Oui', [], 'Modules.Custplace.Custplace'),
                            ],
                            [
                                'id' => 'active_off',
                                'value' => 0,
                                'label' => $this->trans('Non', [], 'Modules.Custplace.Custplace'),
                            ],
                        ],
                    ]
                ],
                'submit' => [
                    'title' => $this->trans('Enregistrer', [], 'Modules.Custplace.Custplace'),
                    'name' => 'submission_widget_sceau',
                ],
            ],
        ];
        $fields_form_3 = [
            'form' => [
                'legend' => [
                    'title' => $this->trans('Widget Avis Produits', [], 'Modules.Custplace.Custplace'),
                    'icon' => 'icon-key',
                ],
                'input' => [
                    [
                        'type' => 'switch',
                        'label' => $this->trans('Widget Avis Produit', [], 'Modules.Custplace.Custplace'),
                        'name' => 'custplace_widget_wap',
                        'is_bool' => true,
                        'desc' => $this->trans('Afficher le Widget Avis Produit dans les pages Fiches Produits.', [], 'Modules.Custplace.Custplace'),
                        'values' => [
                            [
                                'id' => 'active_on',
                                'value' => 1,
                                'label' => $this->trans('Oui', [], 'Modules.Custplace.Custplace'),
                            ],
                            [
                                'id' => 'active_off',
                                'value' => 0,
                                'label' => $this->trans('Non', [], 'Modules.Custplace.Custplace'),
                            ],
                        ],
                    ],
                    [
                        'type' => 'html',
                        'label' => $this->trans('Clé Widget ', [], 'Modules.Custplace.Custplace'),
                        'name' => 'custplace_wap_token',
                        'required' => true,
                        'html_content' => '<input type="password" autocomplete="off" name="custplace_wap_token" class="form-control" value="' . $config_values['custplace_wap_token'] . '">',
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->trans('Couleur primaire', [], 'Modules.Custplace.Custplace'),
                        'name' => 'custplace_wap_first_color',
                        'desc' => $this->trans('Code couleur HEX', [], 'Modules.Custplace.Custplace'),
                        'required' => false
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->trans('Couleur secondaire', [], 'Modules.Custplace.Custplace'),
                        'name' => 'custplace_wap_second_color',
                        'desc' => $this->trans('Code couleur HEX', [], 'Modules.Custplace.Custplace'),
                        'required' => false
                    ],
                    [
                        'type' => 'switch',
                        'label' => $this->trans('Note détaillés', [], 'Modules.Custplace.Custplace'),
                        'name' => 'custplace_wap_subratings',
                        'is_bool' => true,
                        'desc' => $this->trans('Afficher les notes des réponses détaillés si vos produits font l\objet d\une enquête de saisfaction.', [], 'Modules.Custplace.Custplace'),
                        'values' => [
                            [
                                'id' => 'active_on',
                                'value' => 1,
                                'label' => $this->trans('Oui', [], 'Modules.Custplace.Custplace'),
                            ],
                            [
                                'id' => 'active_off',
                                'value' => 0,
                                'label' => $this->trans('Non', [], 'Modules.Custplace.Custplace'),
                            ],
                        ],
                    ],
                    [
                        'type' => 'switch',
                        'label' => $this->trans('Note produit global', [], 'Modules.Custplace.Custplace'),
                        'name' => 'custplace_wap_title_rating',
                        'is_bool' => true,
                        'desc' => $this->trans('Afficher la note global du produit a côté du nom du produit.', [], 'Modules.Custplace.Custplace'),
                        'values' => [
                            [
                                'id' => 'active_on',
                                'value' => 1,
                                'label' => $this->trans('Oui', [], 'Modules.Custplace.Custplace'),
                            ],
                            [
                                'id' => 'active_off',
                                'value' => 0,
                                'label' => $this->trans('Non', [], 'Modules.Custplace.Custplace'),
                            ],
                        ],
                    ]

                ],
                'submit' => [
                    'title' => $this->trans('Enregistrer', [], 'Modules.Custplace.Custplace'),
                    'name' => 'submission_wap',
                ],
            ],
        ];
        
        $fields_form_4 = [
            'form' => [
                'legend' => [
                    'title' => $this->trans('Mode Test', [], 'Modules.Custplace.Custplace'),
                    'icon' => 'icon-cog',
                ],
                'input' => [
                    [
                        'type' => 'switch',
                        'label' => $this->trans('Activer le mode test', [], 'Modules.Custplace.Custplace'),
                        'name' => 'custplace_test_mode',
                        'is_bool' => true,
                        'desc' => $this->trans('Active les environnements de test pour l\'API et les widgets', [], 'Modules.Custplace.Custplace'),
                        'values' => [
                            [
                                'id' => 'active_on',
                                'value' => 1,
                                'label' => $this->trans('Oui', [], 'Modules.Custplace.Custplace'),
                            ],
                            [
                                'id' => 'active_off',
                                'value' => 0,
                                'label' => $this->trans('Non', [], 'Modules.Custplace.Custplace'),
                            ],
                        ],
                    ]
                ],
                'submit' => [
                    'title' => $this->trans('Enregistrer', [], 'Modules.Custplace.Custplace'),
                    'name' => 'submission_test_mode',
                ],
            ],
        ];

        $helper = new HelperForm();
        $helper->show_toolbar = false;
        $helper->id = (int) Tools::getValue('id_carrier');
        $helper->identifier = $this->identifier;
        $helper->submit_action = 'btnSubmit';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false) . '&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->tpl_vars = [
            'fields_value' => $this->getConfigFieldsValues(),
        ];
        $helper->default_form_language = $default_lang;
        $helper->allow_employee_form_lang = $default_lang;

        foreach (Language::getLanguages(false) as $lang) {
            $helper->languages[] = [
                'id_lang' => $lang['id_lang'],
                'iso_code' => $lang['iso_code'],
                'name' => $lang['name'],
                'is_default' => ($default_lang == $lang['id_lang'] ? 1 : 0),
            ];
        }

        return $helper->generateForm([$fields_form, $fields_form_2, $fields_form_3, $fields_form_4]);
    }

    /**
     * Process form submission
     *
     * @return string
     */
    public function postValue(): string
    {
        if (Tools::isSubmit(CustplaceConstants::FORM_SUBMIT_API)) {
            if (!$this->configService->updateApiConfiguration() || !$this->configService->updateTriggerStatusConfiguration()) {
                return $this->displayError($this->trans('Bad configuration.', [], 'Modules.Custplace.Custplace'));
            }
        }

        if (Tools::isSubmit(CustplaceConstants::FORM_SUBMIT_WIDGET_BADGE)) {
            $this->configService->updateTrustBadgeConfiguration();
        }

        if (Tools::isSubmit(CustplaceConstants::FORM_SUBMIT_WIDGET_PRODUCT)) {
            if (!$this->configService->updateWidgetConfiguration()) {
                return $this->displayError($this->trans('Bad configuration.', [], 'Modules.Custplace.Custplace'));
            }
        }

        if (Tools::isSubmit(CustplaceConstants::FORM_SUBMIT_TEST_MODE)) {
            $this->configService->updateTestModeConfiguration();
        }

        return $this->displayConfirmation($this->trans('Settings updated successfully.', [], 'Modules.Custplace.Custplace'));
    }

    /**
     * Get module content for admin configuration
     *
     * @return string
     */
    public function getContent(): string
    {
        $output = '';

        $output .= $this->display(__FILE__, './views/templates/admin/config.tpl');

        if (Tools::isSubmit('btnSubmit')) {
            $output = $this->postValue();
        }

        return $output . $this->renderForm();
    }

    /**
     * Get order states as choices for choice field
     *
     * @return array
     */
    private function getOrderStatesChoices(): array
    {
        $orderStates = OrderState::getOrderStates(Context::getContext()->language->id);
        $choices = [];
        
        foreach ($orderStates as $orderState) {
            $choices[(string)$orderState['id_order_state']] = $orderState['name'];
        }
        
        return $choices;
    }

    /**
     * Get order states for form dropdown
     *
     * @return array
     */
    private function getOrderStatesForForm(): array
    {
        $orderStates = OrderState::getOrderStates(Context::getContext()->language->id);
        $selectedStatuses = $this->configService->getTriggerStatuses();
        
        foreach ($orderStates as &$orderState) {
            $orderState['selected'] = in_array((int)$orderState['id_order_state'], $selectedStatuses);
        }
        
        return $orderStates;
    }

    /**
     * Generate custom HTML for trigger statuses with PrestaShop styling
     *
     * @return string
     */
    private function generateTriggerStatusesHtml(): string
    {
        $orderStates = OrderState::getOrderStates(Context::getContext()->language->id);
        $selectedStatuses = $this->configService->getTriggerStatuses();
        
        $html = '<div class="choice-group type-choice">';
        
        foreach ($orderStates as $orderState) {
            $statusId = (int)$orderState['id_order_state'];
            $isChecked = in_array($statusId, $selectedStatuses) ? 'checked' : '';
            $fieldName = CustplaceConstants::CONFIG_TRIGGER_STATUSES . '[]';
            
            $html .= '
                <div class="choice-field form-check">
                    <input type="checkbox" 
                           id="trigger_status_' . $statusId . '" 
                           name="' . $fieldName . '" 
                           value="' . $statusId . '" 
                           class="form-check-input" 
                           ' . $isChecked . '>
                    <label for="trigger_status_' . $statusId . '" class="form-check-label">
                        ' . htmlspecialchars($orderState['name']) . '
                    </label>
                </div>';
        }
        
        $html .= '</div>';
        
        return $html;
    }

    /**
     * Check if using new translation system
     *
     * @return bool
     */
    public function isUsingNewTranslationSystem(): bool
    {
        return true;
    }
}
