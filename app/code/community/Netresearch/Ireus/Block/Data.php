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


class Netresearch_Ireus_Block_Data extends Mage_Core_Block_Template
{
    var $isIreusCallActive;
    var $lastIreusEvent;
    var $latestAddedProduct;
    var $latestOrder;
    var $latestOrderedProducts;
    var $latestMultishippingOrderIds;


    /**
     * returns current product id
     *
     * @return int
     */
    protected function getProductId()
    {
        return Mage::registry('product')->getId();
    }


    /**
     * returns debug mode
     *
     * @return bool
     */
    protected function isDebugMode()
    {
        return Mage::getStoreConfig('ireus/settings/debug');
    }


    /**
     * returns ireus-customer related Recommendation Engine ID
     *
     * @return string
     */
    protected function getEngineId()
    {
        return Mage::getStoreConfig('ireus/settings/re_id');
    }


    /**
     * returns current session id
     *
     * @return string
     */
    protected function getSessionId()
    {
        return Mage::getSingleton("customer/session")->getSessionId();
    }


    /**
     * returns if session cookies are enabled
     *
     * @return string
     */
    protected function getEnableSessionCookies()
    {
        return (Mage::getStoreConfig('ireus/settings/enable_session_cookies') ? true : false);
    }


    /**
     * returns if user permitted cookies according to the EU cookie law
     *
     * @return string
     */
    protected function getEnablePersistentCookies()
    {
        if (class_exists('Mage::helper(\'core/cookie\')->isUserNotAllowSaveCookie()')
        ) {
            return (Mage::helper('core/cookie')->isUserNotAllowSaveCookie() ? false : true);
        }
        else
        {    
            return true;
        }
    }


    /**
     * returns current user id
     *
     * @return string
     */
    protected function getUserId()
    {
        return Mage::getSingleton("customer/session")->getCustomer()->getId();
    }


    /**
     * returns current category id
     *
     * @return int
     */
    protected function getCategoryId()
    {
        return Mage::registry('current_category')->getId();
    }


    /**
     * determines, whether it has been ordered something by current visitor
     *
     * @return bool
     */
    protected function hasRecentlyOrdered()
    {
        $this->latestMultishippingOrderIds = Mage::getSingleton('core/session')->getOrderIds(true);

        //Multishipping
        if(count($this->latestMultishippingOrderIds)) {
            return true;
        }

        //OnePageCheckout
        if(Mage::getSingleton('checkout/session')->getLastQuoteId()) {
            return true;
        }

        return false;
    }


    /**
     * returns last ordered quote
     *
     * @return Mage_Sales_Quote
     */
    protected function getLatestQuote()
    {
        if(!isset($this->latestQuote)) {
            $this->latestQuote =
                Mage::getModel('sales/quote')
                ->load(Mage::getSingleton('checkout/session')->getLastQuoteId());
        }
        return $this->latestQuote;
    }


    /**
     * returns an array of products and theirs ordered qtys like
     *
     * array(
     *      'ids' => array(123, 321, 435),
     *      'qtys'=> array(1,2,2)
     * )
     *
     * @return array
     */
    protected function getLatestOrderedProducts()
    {
        if(!isset($this->latestOrderedProducts)) {
            $ids = $qtys = array();
            $items = array();
            if(isset($this->latestMultishippingOrderIds)) {
                foreach($this->latestMultishippingOrderIds as $orderId=>$orderNumber) {
                    $items = array_merge($items, Mage::getModel('sales/order')->load($orderId)->getAllItems());
                }
            }
            else {
                $items = $this->getLatestQuote()->getAllItems();
            }
            foreach($items as $item) {

                // Grouped product?
                $parentIds = Mage::getModel('catalog/product_type_grouped')
                             ->getParentIdsByChild($item->getProductId());
                if (isset($parentIds[0])
                ) {
                    $ids[] = $parentIds[0];
                }
                else
                {
                    $ids[] = $item->getProductId();
                }

                $qtys[] = !is_null($item->getQty()) ? $item->getQty() : $item->getQtyOrdered();
            }
            $this->latestOrderedProducts = array('ids' => $ids, 'qtys' => $qtys);
        }
        return $this->latestOrderedProducts;
    }


    /**
     * returns array of last ordered product ids
     *
     * @return array
     */
    protected function getLatestOrderedProductIds()
    {
        $products = $this->getLatestOrderedProducts();
        return $products['ids'];
    }


    /**
     * returns array of last ordered product qtys
     *
     * @return array
     */
    protected function getLatestOrderedQtys()
    {
        $products = $this->getLatestOrderedProducts();
        return $products['qtys'];
    }


    /**
     * returns id of latest product added to cart
     *
     * @return int
     */
    protected function getLatestAddedProductId()
    {
        if(!isset($this->latestAddedProduct)) {
            $this->latestAddedProduct = Mage::getSingleton('checkout/session')->getData('latestAddedProductsId');
            Mage::getSingleton('checkout/session')->unsetData('latestAddedProductsId');
        }
        return $this->latestAddedProduct;
    }


    /**
     * determines whether the current visitor has puted something to cart
     *
     * @return bool
     */
    protected function hasRecentlyAddedSomethingToCart()
    {
        if(!isset($this->latestAddedProduct)) {
            $this->getLatestAddedProductId();
        }
        return isset($this->latestAddedProduct);
    }


    /**
     * determines where Ireus should display something on the current page
     *
     * @return bool
     */
    protected function isActive()
    {
        if(!Mage::getStoreConfig('ireus/settings/active')) {
            return false;
        }

        switch(Mage::app()->getRequest()->getControllerName())
        {
            case 'product': // show product
            	if ($this->getNameInLayout() == 'ireus_databasket'
                    && !$this->hasRecentlyAddedSomethingToCart()
            	) {
              	    return false;
            	}
            	else
            	{
            		return true;
            	}
            case 'category': // show category
                return true;
            case 'cart': // added product to cart
                if (Mage::app()->getRequest()->getActionName() !== 'index'
                    || (
                        $this->getNameInLayout() == 'ireus_databasket'
                        && !$this->hasRecentlyAddedSomethingToCart()
                        )
                ) {
                    return false;
                }
                else
                {
                    return true;
                }
            case 'multishipping': // order successfull
            case 'onepage': // order successfull
                if(
                    Mage::app()->getRequest()->getActionName() === 'success' &&
                    $this->hasRecentlyOrdered()
                ) {
                    return true;
                }
            case 'index': // show homepage
                if(
                    Mage::app()->getRequest()->getActionName() === 'index'
                ) {
                    return true;
                }
        }
        return false;
    }


    /**
     * generates JSON data of parameters needed by Ireus
     *
     * @return string
     */
    protected function getJsonData()
    {
        if(!Mage::getStoreConfig('ireus/settings/active')) {
            return "";
        }

        $return = array (
            'reid' => $this->getEngineId(),
            'sid' => $this->getSessionId(),
            'uid' => $this->getUserId(),
            'session' => $this->getEnableSessionCookies(),
            'cookie' => $this->getEnablePersistentCookies()
        );

        if($this->isDebugMode()) {
            $return['debug'] = 1;
        }

        switch(Mage::app()->getRequest()->getControllerName())
        {
            case 'product':
            	if ($this->getNameInLayout() == 'ireus_databasket'
                    && $this->hasRecentlyAddedSomethingToCart()
                ) {
                    $return['pid'] = $this->getLatestAddedProductId();
                    $return['e'] = 'basket';
                }
                else
                {
                	$return['pid'] = $this->getProductId();
                    $return['e'] = 'product';
                }
                break;
            case 'category':
                $return['cid'] = $this->getCategoryId();
                $return['e'] = 'user';
                break;
            case 'index':
                $return['cid'] = "";
                $return['e'] = 'home';
                break;
            case 'cart':
                if ($this->getNameInLayout() == 'ireus_databasket'
                    && $this->hasRecentlyAddedSomethingToCart()
                ) {
                    $return['pid'] = $this->getLatestAddedProductId();
                    $return['e'] = 'basket';
                }
                else
                {
                    $return['cid'] = '';
                    $return['e'] = 'home';
                }
                break;
            case 'multishipping':
            case 'onepage':
                if ($this->getNameInLayout() == 'ireus_datauser'
                ) {
                    $return['cid'] = '';
                    $return['e'] = 'home';
                }
                else
                {
                    $return['pids'] = implode(',', $this->getLatestOrderedProductIds());
                    $return['qty'] = implode(',', $this->getLatestOrderedQtys());
                    $return['e'] = 'order';
                }
                break;
        }
        return Zend_Json::encode($return);
    }


    /**
     * Gets cached data for noscript fallback
     *
     * @return string
     */
    protected function getCachedRecomms()
    {
        if(!Mage::getStoreConfig('ireus/settings/active')) {
            return '';
        }

        $sidparam = Mage::getSingleton('core/session')->getSessionIdQueryParam();

        switch(Mage::app()->getRequest()->getControllerName())
        {
            case 'product':
                $event = 'product';
                $id = $this->getProductId();
                break;

            case 'category':
                $event = 'user';
                $id = $this->getCategoryId();
                break;

            case 'index':
            default:
                $event = 'home';
                $id = '';
                break;
        }

        return Ireus_Controller_Cache::getContent(
            $this->getEngineId(),
            $this->getSessionId(),
            $event,
            $id,
            Mage::getBaseDir('cache') . DIRECTORY_SEPARATOR . 'ireus',
            array(
                '&'.$sidparam.'=%SID%',
                $sidparam.'=%SID%'
            ),
            'UTF-8'
        );
    }
}