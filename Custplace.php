<?php
/**
 * Custplace.com
 *
 * @author    Custplace <support@custplace.com> - https://fr.custplace.com
 * @copyright THIRD VOICE 2023 - https://fr.custplace.com
 * @license   see file: LICENSE.txt
 *
 * @version   1.0.1
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

require dirname(__FILE__) . '/custplaceapi.php';

class Custplace extends Module
{
    protected $templatefile_widget_product = 'module:custplace/views/templates/hook/custplace_widget_carousel.tpl';
    protected $templatefile_widget_index = 'module:custplace/views/templates/hook/custplace_widget_custseal.tpl';

    public function __construct()
    {
        $this->name = 'custplace';
        $this->version = '1.0.1';
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
    }

    public function install()
    {
        $sql = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'custplace`(
            `id` INT(11) unsigned NOT NULL AUTO_INCREMENT,
            `id_order` INT(10) unsigned NOT NULL ,
            `dateRequest` DATETIME NOT NULL ,
            `status_order` VARCHAR(255) NOT NULL ,
            `invitation_id` VARCHAR(50), 
            PRIMARY KEY (id), 
            FOREIGN KEY (id_order) REFERENCES ' . _DB_PREFIX_ . 'orders(id_order)
        )';
        if (!$result = Db::getInstance()->Execute($sql)) {
            return false;
        }
        return parent::install() && $this->registerHook('displayHeader') && $this->registerHook('actionOrderStatusUpdate') && $this->registerHook('displayAdminOrderSideBottom') && $this->registerHook('displayFooterProduct') && $this->registerHook('displayFooterAfter') && $this->registerHook('ActionGetAdminOrderButtons');
    }

    public function uninstall()
    {
        $sql = 'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'custplace`';
        if (!$result = Db::getInstance()->Execute($sql)) {
            return false;
        }
        return parent::uninstall() && $this->unregisterHook('displayHeader') && $this->unregisterHook('actionOrderStatusUpdate') && $this->unregisterHook('displayAdminOrderSideBottom') && $this->unregisterHook('displayFooterProduct') && $this->unregisterHook('displayFooterAfter') && $this->unregisterHook('ActionGetAdminOrderButtons');
    }

    public function HookdisplayAdminOrderSideBottom($params)
    {
        $sql = 'SELECT status_order,dateRequest FROM ' . _DB_PREFIX_ . 'custplace WHERE id_order = ' . $params['id_order'];
        $values = Db::getInstance()->executeS($sql);
        return $this->render($this->getModuleTemplatePath() . 'custplace.html.twig', [
            'values' => $values,
        ]);
    }

    public function getModuleTemplatePath()
    {
        return sprintf('@Modules/%s/views/templates/hook/', $this->name);
    }

    private function render(string $template, array $params = []): string
    {
        /** @var Twig_Environment $twig */
        $twig = $this->get('twig');

        return $twig->render($template, $params);
    }

    public function HookdisplayFooterProduct($params)
    {
        $product = new Product((int) Tools::getValue('id_product'));
        $custplace_widget_avis_produit = Configuration::get('custplace_widget_avis_produit');
        if ($custplace_widget_avis_produit == null || $custplace_widget_avis_produit == '0') {
            return;
        }
        $this->context->smarty->assign('data_id', Configuration::get('custplace_id_client'));
        $this->context->smarty->assign('custplace_api_key', Configuration::get('custplace_api_key'));
        $this->context->smarty->assign('product_sku', $product->reference);
        return $this->fetch($this->templatefile_widget_product);
    }

    public function hookdisplayFooterAfter($params)
    {
        $custplace_widget_sceau_confiance = Configuration::get('custplace_widget_sceau_confiance');
        if ($custplace_widget_sceau_confiance == null || $custplace_widget_sceau_confiance == '0') {
            return;
        }
        if ($this->context->controller->php_self == 'index') {
            $this->context->smarty->assign('data_id', Configuration::get('custplace_id_client'));
            return $this->fetch($this->templatefile_widget_index);
        }
    }

    public function HookdisplayHeader()
    {
        if ($this->context->controller->php_self == 'product') {
            $this->context->controller->addCSS($this->_path . 'views/css/custplace.css', 'all');
            $this->context->controller->registerJavascript(
                'module-custplace_product-reviews',
                'https://widgets.custplace.com/reviews/product/v1/static/js/bundle.js',
                [
                    'position' => 'bottom',
                    'priority' => 0,
                    'attribute' => 'async',
                    'server' => 'remote',
                ],
            );
        }
        if ($this->context->controller->php_self == 'index') {
            $this->context->controller->registerJavascript(
                'module-custplace-api-widget_index',
                'https://widgets.custplace.com/rating/v4/embed.js',
                [
                    'position' => 'bottom',
                    'priority' => 0,
                    'attribute' => 'async',
                    'server' => 'remote',
                ],
            );
        }
    }

    public function hookActionGetAdminOrderButtons(array $params)
    {
        $sql_verification_status_success = 'SELECT DISTINCT status_order , current_state FROM ' . _DB_PREFIX_ . 'custplace cust , ' . _DB_PREFIX_ . 'orders ord
                WHERE ord.id_order = cust.id_order
                and cust.status_order = \'success\'
                AND ord.id_order = ' . $params['id_order'];
        $last_orders_status_success = Db::getInstance()->executeS($sql_verification_status_success);
        if (!empty($last_orders_status_success)) {
            return;
        } else {
            $sql_get_current_status = 'SELECT current_state FROM ' . _DB_PREFIX_ . 'orders WHERE id_order = ' . $params['id_order'];
            $last_orders = Db::getInstance()->executeS($sql_get_current_status);
            if (isset($last_orders[0]['current_state']) && $last_orders[0]['current_state'] != 2 && $last_orders[0]['current_state'] != 11) {
                return;
            }
            $this->redirectToCustplaceController($params['id_order'], $params['actions_bar_buttons_collection']);
        }
    }

    public function redirectToCustplaceController($id_order, $actions_bar_buttons_collection)
    {
        $order = new Order($id_order);
        $router = $this->get('router');
        $bar = $actions_bar_buttons_collection;
        $custplacePostOrder = $router->generate('cusplace_api_post', ['id_order' => $id_order]);
        $bar->add(
            new \PrestaShopBundle\Controller\Admin\Sell\Order\ActionsBarButton(
                'btn-success',
                ['href' => $custplacePostOrder],
                $this->trans("Solicitez l'avis du client", [], 'Modules.Custplace.Custplace'),
            )
        );
    }

    public static function prepareInvitationData($id_order)
    {
        $sql_orders_id = 'SELECT id_order , status_order from ' . _DB_PREFIX_ . 'custplace WHERE id_order = ' . $id_order . ' AND status_order = \'success\'';
        $order_id = Db::getInstance()->executeS($sql_orders_id);
        if (isset($order_id[0]['status_order']) && $order_id[0]['status_order'] == 'success') {
            return;
        }

        $custplace_id_client = Configuration::get('custplace_id_client');
        $custplace_api_key = Configuration::get('custplace_api_key');
        $delai_sollicitation = Configuration::get('delai_sollicitation');

        if ($custplace_id_client != null && $custplace_api_key != null && $delai_sollicitation != null) {
            $sql_import = 'INSERT INTO ' . _DB_PREFIX_ . 'custplace VALUES (\'\' , \'' . $id_order . '\' , \'' . date('Y-m-d H:i:s') . '\' , \'pending\' , \'\')';
            $execution = Db::getInstance()->Execute($sql_import);
            $id = Db::getInstance()->Insert_ID();
            $custplace_obj = new self();
            $invitation = $custplace_obj->getOrderInfo($id_order);
            $body = $invitation;
            $custplaceapi = new CustplaceApi($custplace_id_client, $custplace_api_key);
            $response = $custplaceapi->sendInvitation($body);
            if (isset($response)) {
                $sql_api_status = 'UPDATE ' . _DB_PREFIX_ . 'custplace 
                            SET status_order = \'' . $response['status_order'] . '\',
                            invitation_id = \'' . $response['sollicitation_id'] . '\'
                            WHERE id = \'' . $id . '\'';
                $execution = Db::getInstance()->Execute($sql_api_status);
            }
            return;
        }
    }

    public function HookactionOrderStatusUpdate($params)
    {
        if ($params['newOrderStatus']->id == 2 || $params['newOrderStatus']->id == 11) {
            Custplace::prepareInvitationData($params['id_order']);
        }
    }

    public function getorderinfo($id_order)
    {
        $sql = 'SELECT ord.`id_order` as id_order , ord.`reference` as ord_ref , ord.`date_add` , ord.`delivery_date`, cust.`firstname`,cust.`lastname`,cust.`email` , prod.`id_product` , prod.`reference` as prod_ref,  orddet.`product_name` , img.`id_image` 
                    FROM ' . _DB_PREFIX_ . 'orders ord 
                    INNER JOIN ' . _DB_PREFIX_ . 'customer cust ON ord.id_customer = cust.id_customer 
                    INNER JOIN ' . _DB_PREFIX_ . 'order_detail orddet ON ord.id_order = orddet.id_order 
                    INNER JOIN ' . _DB_PREFIX_ . 'product prod ON orddet.product_id = prod.id_product
                    INNER JOIN ' . _DB_PREFIX_ . 'image img ON prod.id_product = img.id_product
                    WHERE ord.`id_order` = \'' . $id_order . '\'
                    GROUP BY prod.`id_product`';

        $values = Db::getInstance()->executeS($sql);
        $invitation = [];
        if (count($values)) {
            $invitation['order_ref'] = $values[0]['ord_ref'];
            $invitation['order_date'] = date('d/m/Y', strtotime($values[0]['date_add']));
            $invitation['firstname'] = $values[0]['firstname'];
            $invitation['lastname'] = $values[0]['lastname'];
            $invitation['email'] = $values[0]['email'];
            $invitation['type'] = 'post_review';
            $invitation['sent_at'] = $this->send_at();
            $invitation['lang'] = $this->context->language->iso_code;
        }
        foreach ($values as $value) {
            $product = new Product($value['id_product']);
            $products = [
                'sku' => $value['prod_ref'],
                'name' => $value['product_name'],
                'image' => $this->context->link->getImageLink($product->link_rewrite[Context::getContext()->language->id], $value['id_image'], ImageType::getFormattedName('large')),
                'url' => $this->context->link->getProductLink($product),
            ];
            $invitation['products'][] = $products;
        }
        return $invitation;
    }

    public function send_at()
    {
        $delai_sollicitation = (int) Configuration::get('delai_sollicitation');
        $date_now = date('Y-m-d H:i');
        if ($delai_sollicitation) {
            $date_now = date('Y-m-d H:i', strtotime($date_now . ' +' . $delai_sollicitation . ' day'));
        }
        return $date_now;
    }

    public function getConfigFieldsValues()
    {
        $res = [];
        $res['custplace_id_client'] = Tools::getValue('custplace_id_client', Configuration::get('custplace_id_client'));
        $res['custplace_api_key'] = Tools::getValue('custplace_api_key', Configuration::get('custplace_api_key'));
        $res['delai_sollicitation'] = (int) Tools::getValue('delai_sollicitation', Configuration::get('delai_sollicitation'));
        $res['custplace_api_widget'] = Tools::getValue('custplace_api_widget', Configuration::get('custplace_api_widget'));
        $res['custplace_widget_avis_produit'] = Tools::getValue('custplace_widget_avis_produit', Configuration::get('custplace_widget_avis_produit'));
        $res['custplace_widget_sceau_confiance'] = Tools::getValue('custplace_widget_sceau_confiance', Configuration::get('custplace_widget_sceau_confiance'));
        return $res;
    }

    public function renderForm()
    {
        $default_lang = (int) Configuration::get('PS_LANG_DEFAULT');
        $config_values = $this->getConfigFieldsValues();
        $fields_form = [
            'form' => [
                'legend' => [
                    'title' => $this->trans('Informations de connexion', [], 'Modules.Custplace.Custplace'),
                    'icon' => 'icon-lock',
                ],
                'input' => [
                    [
                        'type' => 'html',
                        'label' => $this->trans('ID client', [], 'Modules.Custplace.Custplace'),
                        'name' => 'custplace_id_client',
                        'required' => true,
                        'html_content' => '<input type="number" name="custplace_id_client" class="form-control" value="' . $config_values['custplace_id_client'] . '">',
                    ],
                    [
                        'type' => 'html',
                        'label' => $this->trans('Clé API ', [], 'Modules.Custplace.Custplace'),
                        'name' => 'custplace_api_key',
                        'required' => true,
                        'html_content' => '<input type="password" name="custplace_api_key" class="form-control" value="' . $config_values['custplace_api_key'] . '">',
                    ],
                ],
                'submit' => [],
            ],
        ];
        $input[] =
            [
                'type' => 'text',
                'label' => $this->trans('Delai de sollicitation', [], 'Modules.Custplace.Custplace'),
                'name' => 'delai_sollicitation',
                'desc' => $this->trans('jours', [], 'Modules.Custplace.Custplace'),
                'required' => true,
            ];
        $fields_form_2 = [
            'form' => [
                'legend' => [
                    'title' => $this->trans('Delai de sollicitation', [], 'Modules.Custplace.Custplace'),
                    'icon' => 'icon-bell',
                ],
                'input' => $input,
                'submit' => [
                    'title' => $this->trans('Enregistrer', [], 'Modules.Custplace.Custplace'),
                    'name' => 'submission_admin',
                ],
            ],
        ];
        $input_2 =
            [
                [
                    'type' => 'text',
                    'label' => $this->trans('Clé widget', [], 'Modules.Custplace.Custplace'),
                    'name' => 'custplace_api_widget',
                    'required' => true,
                ],
                [
                    'type' => 'switch',
                    'label' => $this->trans('Widget Avis Produit', [], 'Modules.Custplace.Custplace'),
                    'name' => 'custplace_widget_avis_produit',
                    'is_bool' => true,
                    'desc' => $this->trans('Activer le widget avis produit afin d\'atteindre les avis de votre clients', [], 'Modules.Custplace.Custplace'),
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
                    'label' => $this->trans('Widget Sceau de confiance', [], 'Modules.Custplace.Custplace'),
                    'name' => 'custplace_widget_sceau_confiance',
                    'is_bool' => true,
                    'desc' => $this->trans('Activer le widget sceau de confiance', [], 'Modules.Custplace.Custplace'),
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
            ];
        $fields_form_3 = [
            'form' => [
                'legend' => [
                    'title' => $this->trans('Clé Widget', [], 'Modules.Custplace.Custplace'),
                    'icon' => 'icon-key',
                ],
                'input' => $input_2,
                'submit' => [
                    'title' => $this->trans('Enregistrer', [], 'Modules.Custplace.Custplace'),
                    'name' => 'submission_api',
                ],
            ],
        ];
        if (Tools::getIsset(Tools::getValue('custplace_id_client')) && Tools::getIsset(Tools::getValue('custplace_api_key')) && Tools::getIsset(Tools::getValue('delai_sollicitation')) && Tools::getIsset(Tools::getValue('custplace_api_widget')) && Tools::getIsset(Tools::getValue('custplace_widget_sceau_confiance')) && Tools::getIsset(Tools::getValue('custplace_widget_avis_produit'))) {
            Configuration::updateValue('custplace_id_client', Tools::getValue('custplace_id_client', true));
            Configuration::updateValue('custplace_api_key', Tools::getValue('custplace_api_key', true));
            Configuration::updateValue('delai_sollicitation', Tools::getValue('delai_sollicitation'));
            Configuration::updateValue('custplace_api_widget', Tools::getValue('custplace_api_widget'));
            Configuration::updateValue('custplace_widget_avis_produit', Tools::getValue('custplace_widget_avis_produit'));
            Configuration::updateValue('custplace_widget_sceau_confiance', Tools::getValue('custplace_widget_sceau_confiance'));
        }

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
        return $helper->generateForm([$fields_form, $fields_form_2, $fields_form_3]);
    }
    public function postValue()
    {
        if (Tools::isSubmit('submission_admin')) {
            Configuration::updateValue('delai_sollicitation', Tools::getValue('delai_sollicitation'));
            Configuration::updateValue('custplace_id_client', Tools::getValue('custplace_id_client'), true);
            Configuration::updateValue('custplace_api_key', Tools::getValue('custplace_api_key'), true);
        }
        if (Tools::isSubmit('submission_api')) {
            Configuration::updateValue('custplace_api_widget', Tools::getValue('custplace_api_widget'));
            Configuration::updateValue('custplace_widget_sceau_confiance', Tools::getValue('custplace_widget_sceau_confiance'));
            Configuration::updateValue('custplace_widget_avis_produit', Tools::getValue('custplace_widget_avis_produit'));
        }
    }
    public function getContent()
    {
        return $this->postValue() . $this->renderForm();
    }

    public function isUsingNewTranslationSystem()
    {
        return true;
    }
}
