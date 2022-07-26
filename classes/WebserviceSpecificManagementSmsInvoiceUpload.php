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
class WebserviceSpecificManagementSmsInvoiceUpload implements WebserviceSpecificManagementInterface {
     
    /**
     * @var WebserviceRequest
     */
    protected $wsObject;

    /**
     * @var string
     */
    protected $output;
    
    /**
     * @var WebserviceOutputBuilder
     */
    protected $objOutput;
 
    /**
     * @var array The list of supported mime types
     */
    protected $acceptedMimeTypes = [
        'application/pdf'
    ];
 
     /**
     * @param WebserviceOutputBuilder $obj
     *
     * @return WebserviceSpecificManagementInterface
     */
    public function setObjectOutput(WebserviceOutputBuilderCore $obj)
    {
        $this->objOutput = $obj;

        return $this;
    }
    
    /**
     * Get Object Output
     */
    public function getObjectOutput()
    {
        return $this->objOutput;
    }

    public function setWsObject(WebserviceRequestCore $obj)
    {
        $this->wsObject = $obj;
        
        return $this;
    }

    public function getWsObject()
    {
        return $this->wsObject;
    }
    /**
     * Gets the mime file type for the given file
     *
     * @param $_FILES array $arry
     *
     * @return string
     */
    protected function GetMimeType($file = null)
    {
        if (!isset($file['tmp_name']))
        {
            $file = $_FILES['file'];
        }
     
        // Get mime content type
        $mime_type = false;
        if (Tools::isCallable('finfo_open')) {
            $const = defined('FILEINFO_MIME_TYPE') ? FILEINFO_MIME_TYPE : FILEINFO_MIME;
            $finfo = finfo_open($const);
            $mime_type = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);
        } elseif (Tools::isCallable('mime_content_type')) {
            $mime_type = mime_content_type($file['tmp_name']);
        } elseif (Tools::isCallable('exec')) {
            $mime_type = trim(exec('file -b --mime-type ' . escapeshellarg($file['tmp_name'])));
        }
        if (empty($mime_type) || $mime_type == 'regular file') {
            $mime_type = $file['type'];
        }
        if (($pos = strpos($mime_type, ';')) !== false) {
            $mime_type = substr($mime_type, 0, $pos);
        }
        
        return $mime_type;
    }
        /**
    * Check the given mime type to see if it is part of the acceptedMimeTypes
    *
    * $mime_type string - the mime type to be checked
    *
    * return boolean - Whether the given mim type is value true/false
    */
    protected function isValidMimeType($mime_type = null)
    {
        if (!isset($mime_type))
        {
            return false;
        }
        
        if (!$mime_type || !in_array($mime_type, $this->acceptedMimeTypes)) {
            throw new WebserviceException('This type of image format is not recognized, allowed formats are: ' . implode('", "', $this->acceptedMimeTypes), [73, 400]);
        } elseif ($file['error']) {
            // Check error while uploading
            throw new WebserviceException('Error while uploading image. Please change your server\'s settings', [74, 400]);
        }
        
        return true;
    }
    public function manage()
    {
        $method = $this->wsObject->method;
        
        if(isset($method) && $method == 'POST')
        {
            //download the pdf file and save to disk similar to bike_model filter
            //if not found return the default prestashop invoice
            $order_id = $this->getWsObject()->urlSegment[1];
            if ($order_id && isset($_FILES['file']['tmp_name']) && $_FILES['file']['tmp_name']) 
            {
                $file = $_FILES['file'];
                if ($file['size'] > $this->maximumSize) {
                    throw new WebserviceException(sprintf('The image size is too large (maximum allowed is %d KB)', ($this->maximumSize / 1000)), [72, 400]);
                }
                
                // Get mime content type
                $mime_type = $this->GetMimeType($file);
                //Move file from tmp to module location, will overwrite file if exists
                $path_to_save = __PS_BASE_URI__.'modules/shoplync_sms_invoicing/invoices/';
                $file_name = $order_id.'.pdf';
                $full_path = _PS_CORE_DIR_.$path_to_save.$file_name;
                
                error_log('full path: '.$full_path);
                if ($this->isValidMimeType($mime_type) && move_uploaded_file($_FILES['file']['tmp_name'], $full_path))
                {
                    //save path/link inside of db for ps_ca_garage cust id + garage_ id
                    error_log('relative_path: '.$path_to_save.$file_name);
                }
            }
        }
        return $this->getWsObject()->getOutputEnabled();
    }
    
    /**
     * This must be return a string with specific values as WebserviceRequest expects.
     *
     * @return string
     */
    public function getContent()
    {
        $contentOutput = array('<?xml version="1.0" encoding="UTF-8"?>'.'<prestashop xmlns:xlink="http://www.w3.org/1999/xlink">', '</prestashop>');
        
        if ($this->output != '') {
            return $this->objOutput->getObjectRender()->overrideContent($this->output);
        }

        return '';
    }
}