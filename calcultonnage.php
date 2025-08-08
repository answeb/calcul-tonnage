<?php
/**
 * Calcul Tonnage Module
 *
 * @category  Module
 * @author    Claude
 * @copyright 2025
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

use PrestaShop\PrestaShop\Adapter\Presenter\Product\ProductLazyArray;
use PrestaShop\PrestaShop\Core\Domain\Product\Combination\QueryResult\ProductCombinationsCollection;

if (!defined('_PS_VERSION_')) {
    exit;
}

class CalculTonnage extends Module
{
    protected $config_form = false;

    public function __construct()
    {
        $this->name = 'calcultonnage';
        $this->tab = 'front_office_features';
        $this->version = '1.0.0';
        $this->author = 'Answeb';
        $this->need_instance = 0;

        /**
         * Set $this->bootstrap to true if your module is compliant with bootstrap (PrestaShop 1.6)
         */
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->trans('Calcul de tonnage', [], 'Modules.Calcultonnage.Admin');
        $this->description = $this->trans('Calculer le tonnage de gravier en fonction de la surface, de l\'épaisseur et de la densité du produit',
            [], 'Modules.Calcultonnage.Admin');

        $this->ps_versions_compliancy = ['min' => '8.0.0', 'max' => _PS_VERSION_];
    }

    /**
     * Install function
     */
    public function install()
    {
        Configuration::updateValue('CALCULTONNAGE_FEATURE_ID', 0);

        return parent::install() &&
            $this->registerHook('displayHeader') &&
            $this->registerHook('displayProductActionsVariants');
    }

    /**
     * Uninstall function
     */
    public function uninstall()
    {
        Configuration::deleteByName('CALCULTONNAGE_FEATURE_ID');

        return parent::uninstall();
    }

    /**
     * Load the configuration form
     */
    public function getContent()
    {
        $output = '';

        if (Tools::isSubmit('submitCalculTonnageModule')) {
            $featureId = (int)Tools::getValue('CALCULTONNAGE_FEATURE_ID');
            Configuration::updateValue('CALCULTONNAGE_FEATURE_ID', $featureId);

            $output = $this->displayConfirmation($this->trans('Settings updated', [], 'Admin.Notifications.Success'));
        }

        return $output . $this->renderForm();
    }

    /**
     * Create the form that will be displayed in the configuration of your module.
     */
    protected function renderForm()
    {
        $helper = new HelperForm();

        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->module = $this;
        $helper->default_form_language = Context::getContext()->language->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);

        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitCalculTonnageModule';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            . '&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        // Get all product features
        $features = Feature::getFeatures($this->context->language->id);
        $featureOptions = [];
        $featureOptions[] = ['id_option' => 0, 'name' => $this->trans('Choose a feature', [], 'Admin.Catalog.Feature')];
        foreach ($features as $feature) {
            $featureOptions[] = ['id_option' => $feature[ 'id_feature' ], 'name' => $feature[ 'name' ]];
        }

        $helper->tpl_vars = [
            'fields_value' => [
                'CALCULTONNAGE_FEATURE_ID' => Configuration::get('CALCULTONNAGE_FEATURE_ID'),
            ],
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        ];

        return $helper->generateForm([
            [
                'form' => [
                    'legend' => [
                        'title' => $this->trans('Settings', [], 'Admin.Global'),
                        'icon' => 'icon-cogs',
                    ],
                    'input' => [
                        [
                            'type' => 'select',
                            'label' => $this->trans('Densité', [], 'Modules.Calcultonnage.Admin'),
                            'desc' => $this->trans('Sélectionnez la caractéristique qui contient la valeur de densité pour les produits.',
                                [], 'Modules.Calcultonnage.Admin'),
                            'name' => 'CALCULTONNAGE_FEATURE_ID',
                            'options' => [
                                'query' => $featureOptions,
                                'id' => 'id_option',
                                'name' => 'name',
                            ],
                        ],
                    ],
                    'submit' => [
                        'title' => $this->trans('Save', [], 'Admin.Actions'),
                    ],
                ],
            ],
        ]);
    }

    /**
     * Display the tonnage calculator button
     */
    protected function hook($params, $hookName = '')
    {
        /**
         * @var ProductLazyArray $product
         */
        $product = $params[ 'product' ];
        // Get weight of each combination
        // Not used for now, maybe later, with the weight calculation ?
        // $productCombinations = (new Product($product->id))->getAttributeCombinations();

        // Get feature ID from configuration
        $featureId = (int)Configuration::get('CALCULTONNAGE_FEATURE_ID');
        if (!$featureId) {
            return '';
        }
        $density = null;

        foreach ($product[ 'features' ] as $feature) {
            if ((int)$feature[ 'id_feature' ] === $featureId) {
                // Get feature value
                $density = (float)str_replace(',','.', $feature[ 'value' ]);
                break;
            }
        }

        // If density is not defined, don't display the calculator
        if (null === $density || 0 === $density) {
            return '';
        }

        $this->smarty->assign([
            'product' => $product,
            'density' => $density,
            'module_template_dir' => $this->_path,
        ]);

        return $this->display(__FILE__, 'views/templates/hook/calculator.tpl');
    }

    public function hookDisplayHeader($params)
    {
        if (!$this->context->controller instanceof ProductControllerCore) {
            return;
        }
        $product = $this->context->controller->getProduct();
        $idProductAttribute = (int) Tools::getValue('id_product_attribute');
        $weight = $product->weight ?? 0;
        if ($idProductAttribute) {
            $c = new Combination($idProductAttribute);
            $weight += $c->weight;
        }
        $this->smarty->assign([
            'weight' => (float)$weight,
            'weight_unit' => $product->weight_unit,
        ]);
        return $this->display(__FILE__, 'views/templates/hook/productmeta.tpl');
    }

    public function __call($method, $params)
    {
        if (str_starts_with($method, 'hookDisplay')) {
            return $this->hook($params[0], $method);
        }

        return null;
    }

}
