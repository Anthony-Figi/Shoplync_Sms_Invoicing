<?php
/**
* 2007-2022 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author    PrestaShop SA <contact@prestashop.com>
*  @copyright 2007-2022 PrestaShop SA
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/

if (!defined('_PS_VERSION_')) {
    exit;
}

include_once dirname (__FILE__).'/classes/WebserviceSpecificManagementSmsInvoiceUpload.php';

class Shoplync_sms_invoicing extends Module
{
    protected $config_form = false;

    protected $PS_ORDER_CANCELLED = 6;

    public function __construct()
    {
        $this->name = 'shoplync_sms_invoicing';
        $this->tab = 'front_office_features';
        $this->version = '1.0.0';
        $this->author = 'Shoplync';
        $this->need_instance = 0;

        $this->controllers = array('download');
        /**
         * Set $this->bootstrap to true if your module is compliant with bootstrap (PrestaShop 1.6)
         */
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('SMS Pro Invoice');
        $this->description = $this->l('Allows the user to view/download order invoices generated by SMS Pro.');
        $this->confirmUninstall = $this->l('');
        $this->ps_versions_compliancy = array('min' => '1.6', 'max' => _PS_VERSION_);
    }

    /**
     * Don't forget to create update methods if needed:
     * http://doc.prestashop.com/display/PS16/Enabling+the+Auto-Update
     */
    public function install()
    {
        Configuration::updateValue('SHOPLYNC_SMS_INVOICING_LIVE_MODE', true);

        return parent::install() &&
            $this->registerHook('header') &&
            $this->registerHook('displayHeader') &&
            $this->registerHook('displayOrderDetail') &&
            $this->registerHook('addWebserviceResources') &&
            $this->registerHook('displayBackOfficeHeader') &&
            $this->registerHook('actionOrderStatusPostUpdate');
    }

    public function uninstall()
    {
        Configuration::deleteByName('SHOPLYNC_SMS_INVOICING_LIVE_MODE');
        return parent::uninstall();
    }

    /**
     * Load the configuration form
     */
    public function getContent()
    {
        /**
         * If values have been submitted in the form, process.
         */
        if (((bool)Tools::isSubmit('submitShoplync_sms_invoicingModule')) == true) {
            $this->postProcess();
        }

        $this->context->smarty->assign('module_dir', $this->_path);
        $output = $this->context->smarty->fetch($this->local_path.'views/templates/admin/configure.tpl');

        return $output.$this->renderForm();
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
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);

        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitShoplync_sms_invoicingModule';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            .'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        $helper->tpl_vars = array(
            'fields_value' => $this->getConfigFormValues(), /* Add values for your inputs */
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        );

        return $helper->generateForm(array($this->getConfigForm()));
    }

    /**
     * Create the structure of your form.
     */
    protected function getConfigForm()
    {
        return array(
            'form' => array(
                'legend' => array(
                'title' => $this->l('Settings'),
                'icon' => 'icon-cogs',
                ),
                'input' => array(
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Live mode'),
                        'name' => 'SHOPLYNC_SMS_INVOICING_LIVE_MODE',
                        'is_bool' => true,
                        'desc' => $this->l('Use this module in live mode'),
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => true,
                                'label' => $this->l('Enabled')
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => false,
                                'label' => $this->l('Disabled')
                            )
                        ),
                    ),
                ),
                'submit' => array(
                    'title' => $this->l('Save'),
                ),
            ),
        );
    }

    /**
     * Set values for the inputs.
     */
    protected function getConfigFormValues()
    {
        return array(
            'SHOPLYNC_SMS_INVOICING_LIVE_MODE' => Configuration::get('SHOPLYNC_SMS_INVOICING_LIVE_MODE', true),
        );
    }

    /**
     * Save form data.
     */
    protected function postProcess()
    {
        $form_values = $this->getConfigFormValues();

        foreach (array_keys($form_values) as $key) {
            Configuration::updateValue($key, Tools::getValue($key));
        }
    }


    /**
    * Add the CSS & JavaScript files you want to be loaded in the BO.
    */
    public function hookBackOfficeHeader()
    {
        if (Tools::getValue('configure') == $this->name) {
            $this->context->controller->addJS($this->_path.'views/js/back.js');
            $this->context->controller->addCSS($this->_path.'views/css/back.css');
        }
    }

    /**
     * Add the CSS & JavaScript files you want to be added on the FO.
     */
    public function hookHeader()
    {
        $this->context->controller->addJS($this->_path.'/views/js/front.js');
        $this->context->controller->addCSS($this->_path.'/views/css/front.css');
        
        Media::addJsDef([
            'adminajax_link' => $this->context->link->getModuleLink($this->name, 'download', array(), true),
        ]);
    }
    
    /**
     *  Will display on the users order detail page.
     */     
    public function hookDisplayOrderDetail($params)
    {
        $path_to_save = __PS_BASE_URI__.'modules/'.$this->name.'/invoices/';
        $order_id = $params['order']->id;
        
        if(!Configuration::get('SHOPLYNC_SMS_INVOICING_LIVE_MODE', true))
        {
            return '';
        }
        
        $disabled = ($order_id == 0) ? 'disabled' : '';
        
        $shipmentElement = '<div id="viewSmsInvoiceBox" class="box text-center">'
            .'<h3>Order Details</h3>'
            .'<button id="viewInvoiceBtn" type="button" name="viewSmsInvoice" onclick="DownloadInvoice('.$order_id.');" class="btn btn-primary form-control-submit" '.$disabled.'>'
            .'<i class="material-icons" style="font-size: 2em;margin-right: 10px;">file_download</i>'
            .'<span style="vertical-align: middle;">View Invoice</span>'
            .'</button>'
            .'</div>';
        
        if(!file_exists($path_to_save.$order_id.'.pdf') && !OrderState::invoiceAvailable($params['order']->current_state))
        {
            $shipmentElement = '<!-- No SMS/Prestashop Invoice Available -->';
        }
            
        return $shipmentElement;
    }
    
    
    
    
    public function hookAddWebserviceResources()
    {
        return array(
            'SmsInvoiceUpload' => array('description' => 'Allows SMS Pro to upload generated invoices', 'specific_management' => true),
        );
    }
    
    public function hookActionOrderStatusPostUpdate($params)
    {
        if(!Configuration::get('SHOPLYNC_SMS_INVOICING_LIVE_MODE', true))
        {
            return '';
        }
        //Move file from tmp to module location, will overwrite file if exists
        $path_to_save = __PS_BASE_URI__.'modules/'.$this->name.'/invoices/';
        
        //find out how to identify canceled orders
        $order_status = array('Canceled', 'Voided', 'Refunded', 'Payment Error');
        if($params && $params['id_order'] && $params['newOrderStatus'] && in_array($params['newOrderStatus']->name, $order_status))
        {
            $file_path = $path_to_save.$params['id_order'].'.pdf';
            //check to see if we have an invoice for it and delete it 
            if(file_exists($file_path))
            {
                unlink($file_path);
            }
        }
    }
}
