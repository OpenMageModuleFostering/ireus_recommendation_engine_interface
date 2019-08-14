<?php
/**
 * IREUS
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0).
 * It is available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you are unable to obtain the license through the world-wide-web,
 * please send an email to support@ireus.net so we can send you a copy.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Ireus to newer
 * versions in the future. If you wish to customize Ireus for your
 * needs please refer to http://www.ireus.net for more information.
 *
 * @category   IREUS Recommendation Engine
 * @package    Ireus
 * @copyright  Copyright (c) 2010 prudsys AG (http://www.prudsys.com)
 * @author     Stephan Hoyer, Germany
 * @author     Silvio Steiger, Germany
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * 
 * $Id$
 */


// Register IREUS PHP Framework 
require_once('IreusAutoloader.php');
IreusAutoloader::getInstance()->register();


class Netresearch_Ireus_ExportController extends Mage_Core_Controller_Front_Action
{
    const CONFIG_SECURITY_CODE = 'ireus/settings/security_code';
    const CONFIG_EXPORT_DISABLED_CATEGORIES = 'ireus/settings/export_disabled_categories';
    const CONFIG_IMAGE_WIDTH = 'ireus/settings/image_width';
    const CONFIG_IMAGE_HEIGHT = 'ireus/settings/image_height';
    const PAGE_SIZE = 1;

    var $_storeId ='admin';

    var $_ordersColumns = array(
        'tid' => 'order_id',
        'pid' => 'product_id'
    );

    var $_productsColumns = array(
        'pid' => 'entity_id',
        'sku' => 'sku',
        'name' => 'name',
        'brand' => 'brand',
        'manufacturer' => 'manufacturer',
        'description' => 'short_description',
        'param1' => 'add_to_cart_url',
        'URL' => 'url',
        'imageURL' => 'images_url',
        'netUnitPrice' => 'price',
        'onlineFlag' => 'is_salable',
        'quantity' => 'qty',
        'strikeOutPrice' => 'special_price'
    );
    
    static $_sessionId;
    
    static $_sessionIdQueryParam;

    static $_ireusplaceholder = '%SID%';

    
    /**
     * Check security code and redirect to 404 if not given or wrong
     * 
     * @return Netresearch_Ireus_ExportController
     */
    public function preDispatch()
    {
        parent::preDispatch();
        $security_code = Mage::App()->getRequest()->getParam('code');
        if(
            is_null($security_code) or
            $security_code != Mage::getStoreConfig(self::CONFIG_SECURITY_CODE)
        ) {
              $this->_redirect('noroute');
              $this->setFlag('',self::FLAG_NO_DISPATCH,true);
        }
               
        if ($this->getRequest()->getParam('store')
        ) {
            $this->_setActiveStore($this->getRequest()->getParam('store'));
        }
        
        $this->_storeId = Mage::App()->getStore()->getCode();
        self::$_sessionId = Mage::getSingleton('core/session')->getSessionId();
        self::$_sessionIdQueryParam = Mage::getSingleton('core/session')->getSessionIdQueryParam();  

        return $this;
    }

    
    /**
     * Output a CSV of all products with their 
     * categories and all parent categories,
     * 
     * @return null
     */
    public function categoriesAction()
    {
        // Change maximum execution time 
        set_time_limit(0);
        
        foreach(Mage::getModel('Catalog/Category')
            ->getCollection()
            ->addFieldToFilter('is_active', 1)
            ->addFieldToFilter('level', array('gt' => 1))
            ->addAttributeToSelect('name') as $category
        ) {
            if($category->getProductCount()
            ) {
                $collection = Mage::getModel('Catalog/Category')
                    ->getCollection()
                    ->addFieldToFilter('entity_id', array(
                        'in' => $category->getPathIds()))
                    ->addAttributeToSelect('entity_id')
                    ->addAttributeToSelect('name');
                
                foreach ($category->getProductCollection()
                    ->joinField('qty', 'cataloginventory/stock_item', '*',
                        'product_id=entity_id', '{{table}}.stock_id=1', 'left')
                    ->addAttributeToFilter('status',
                        Mage_Catalog_Model_Product_Status::STATUS_ENABLED)
                    ->addAttributeToFilter('visibility', array('in'=>array(
                        Mage_Catalog_Model_Product_Visibility::VISIBILITY_BOTH,
                        Mage_Catalog_Model_Product_Visibility::VISIBILITY_IN_CATALOG
                    )))
                    ->addAttributeToFilter('price', array('gt' => 0)) as $product
                ) {
          	        $productsWithCategories = array();
                    $pid = $product->getId();
                    foreach ($collection as $currentcategory                  
                    ) {
                        $cid = $currentcategory->getId();
                        $productsWithCategories[$pid.$cid] = array(
                            'pid' => $pid,
                            'cid' => $cid,
                            'cidparent' => $currentcategory->getParentId(),
                            'cname' => $currentcategory->getName()               
                        );
                    }
                    
                    // Stream output product wise
                    echo Ireus_Controller_Export::getInstance()
                        ->createCategoriesCsv($productsWithCategories);
                    flush();
                }
            }
        }
        
        exit;
    }
    
    
    /**
     * Output a CSV of all orders
     * 
     * @return null
     */
    public function ordersAction()
    {
    	// Change maximum execution time 
        set_time_limit(0);
        
        echo Ireus_Controller_Export::getInstance()
            ->setOrderColumns($this->_ordersColumns)
            ->exportOrdersCsv(Mage::getModel('Sales/Order_Item')->getCollection()->getData());

        exit;
    }

    
    /**
     * Output a CSV of all products with their attributes
     * 
     * @return null
     */
    public function productsAction()
    {   
    	// Change maximum execution time 
        set_time_limit(0);
        
        // Prepare the prudsys IREUS Export Controller
        Ireus_Controller_Export::getInstance()
            ->setProductColumns($this->_productsColumns);
        
        // Get products from database
        $collection = Mage::getModel('Catalog/Product')
            ->getCollection()
            ->joinField('qty', 'cataloginventory/stock_item', '*',
                'product_id=entity_id', '{{table}}.stock_id=1', 'left')
            ->addAttributeToFilter('status',
                Mage_Catalog_Model_Product_Status::STATUS_ENABLED)
            ->addAttributeToFilter('price', array('gt' => 0))
            ->addAttributeToFilter(
                'visibility', 
                    array(
                    'in' => array(
                        Mage_Catalog_Model_Product_Visibility::VISIBILITY_BOTH,
                        Mage_Catalog_Model_Product_Visibility::VISIBILITY_IN_CATALOG
                    )
                )
            )
            ->addAttributeToSelect('small_image');
        foreach($this->_productsColumns as $key) {
            $collection->addAttributeToSelect($key);
        }

        // Add missing information to the collection
        // which cannot be requested directly from database.
        // Here is the most time wasted
        $products = array();
        foreach ($collection as $product)
        {
        	$product_url = $product->getProductUrl();
        	//$product->setManufacturer($product->getAttributeText('manufacturer'));
            $product->setUrl(self::setSidPlaceholder($product_url));
            $product->setImagesUrl(Mage::helper('Catalog/Image')
                ->init($product,'small_image')->resize(
                    Mage::getStoreConfig(self::CONFIG_IMAGE_WIDTH),
                    Mage::getStoreConfig(self::CONFIG_IMAGE_HEIGHT)
                )->__toString());
            $product->setAddToCartUrl(self::getAddToCartUrl($product, $product_url));
            
            // Stream result in parts of 10
            $products[] = $product->getData();
            
            if (count($products) > 9
            ) {
                echo Ireus_Controller_Export::getInstance()
                    ->createProductsCsv($products);
                flush();
                $products = array();
            }
        }

        // Stream remaining items
        if (count($products) > 0
        ) {
            echo Ireus_Controller_Export::getInstance()
                ->createProductsCsv($products);
            flush();
        }
           
        // Delete cached recommendations
        Ireus_Controller_Cache::deleteCache(Mage::getBaseDir('cache') . DIRECTORY_SEPARATOR . 'ireus');
        
        exit;
    }

    
    /**
     * Create "add this product to cart" url
     * 
     * @param object $collection
     * @param array $additional
     * @return array
     */
    protected static function getAddToCartUrl($product, $product_url, $additional = array())
    {
        if ($product->getTypeInstance(true)->hasRequiredOptions($product)
        ) {
            $url = $product_url;
            $link = (strpos($url, '?') !== false) ? '&' : '?';
            $result = $url . $link . 'options=cart';
        } 
        else
        {
        	$result = Mage::helper('checkout/cart')->getAddUrl($product, $additional);
        }
                
        return self::setSidPlaceholder($result);
    }
    
    
    /**
     * Sets SessionID placeholder in urls for dynamic SessionID adjustments done by IREUS
     * 
     * @param string $url
     * @return string
     */
    protected static function setSidPlaceholder ($url)
    {	
        if (strpos($url, self::$_sessionId)
        ) {
        	$url = str_replace(self::$_sessionId, self::$_ireusplaceholder, $url);
        } 
        else
        {
        	$url .= ((strpos($url, '?') !== false) ? '&' : '?')
        	      . self::$_sessionIdQueryParam
        	      . '='
        	      . self::$_ireusplaceholder;
        }
        
  		return $url;
    }
    
    
    /**
     * Sets a different store if needed
     * 
     * @param Mixed $storeCode can be either storeId or storeCode of store to set
     * @return Mage_Core_Controller_Front_Action
     */
    protected function _setActiveStore($storeCode)
    {
        if (!$storeCode
        ) {
            $storeCode = $this->_storeCode;
        }
        
        // if storeId is used convert to storeCode
        if (is_numeric($storeCode)) {
            $storeCode = Mage::app()->getStore($storeCode)->getCode();
        }
        
        // if current store is ok return
        if (Mage::app()->getStore()->getCode() == $storeCode) {
            return $this;
        }
        
        // set active store
        Mage::app()->setCurrentStore($storeCode);
                
        return $this;
    }
}