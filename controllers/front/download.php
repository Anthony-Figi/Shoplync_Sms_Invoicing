<?php
/**
* @author    Anthony Figueroa - Shoplync Inc <sales@shoplync.com>
* @copyright 2007-2022 Shoplync Inc
* @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
* @category  PrestaShop module
* @package   Bike Model Filter
*      International Registered Trademark & Property of Shopcreator
* @version   1.0.0
* @link      http://www.shoplync.com/
*/

/**
 * Class itself
 */
class shoplync_sms_invoicingdownloadModuleFrontController extends ModuleFrontController
{
    /**
     * Save form data.
     */
    public function postProcess()
    {
        return parent::postProcess(); 
    }

    /**
     * This function sets the appropritate error headers and returns the default 'Failed' error response
     * 
     * $errorMessage string - The error message to return
     * $extra_details array() - array of key:value pairs to be added to the error json response
     * 
    */
    public function setErrorHeaders($errorMessage = 'Failed', $extra_details = [])
    {
        header('HTTP/1.1 500 Internal Server Error');
        header('Content-Type: application/json; charset=UTF-8');
        
        $error_array = ['errorResponse' => $errorMessage];
        
        if(!empty($extra_details) && is_array($extra_details))
            $error_array = $error_array + $extra_details;
        
        $this->ajaxDie(json_encode($error_array));
    }

    /**
    * Triggered via an AJAX call, Retrieves the products vehicle fitment given a particular make
    *
    * $_POST['make_id'] int - Used to filter product vehicle fitments by make
    * $_POST['product_id'] int - Specifies which product to retrieve vehicle fitment for
    * $_POST['attribute_id'] int- Specifies whether to retrieve fitment for a product combination
    */ 
    public function displayAjaxDownloadInvoice()
    {
        error_log(print_r($_POST, true));
        
        $path_to_save =  $this->module->getLocalPath().'invoices/';
        if (Tools::isSubmit('order_id'))
        {
            $order_id = Tools::getValue('order_id');
            $file_name = $order_id.'.pdf';
            
            $path_to_file = $path_to_save.$file_name;
            error_log('path: '.$path_to_file);
            if($order_id && file_exists($path_to_file))
            {
                error_log('path-after: '.$path_to_file);
                /*if (ob_get_level() && ob_get_length() > 0) {
                    ob_end_clean();
                }*/
                
                header('Content-Transfer-Encoding: binary');
                header('Content-Type: application/pdf');
                header('Content-Length: '.filesize($path_to_file));
                header('Content-Disposition: attachment; filename="'.utf8_decode($file_name).'"');
                //ob_clean();
                //flush();
                //readfile($path_to_file);
                $this->ajaxDie(file_get_contents($path_to_file));
            }
        }
        $this->setErrorHeaders('Could not find/load invoice, please try again later.');
    }
}
