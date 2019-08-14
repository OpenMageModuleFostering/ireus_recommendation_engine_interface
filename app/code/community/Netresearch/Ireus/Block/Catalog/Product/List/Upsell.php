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
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * 
 * $Id$
 */


class Netresearch_Ireus_Block_Catalog_Product_List_Upsell
    extends Mage_Catalog_Block_Product_List_Upsell
{
    public function getItemCollection()
    {
        if (
            Mage::getStoreConfig('ireus/settings/active') &&
            Mage::getStoreConfig('ireus/settings/disable_magento_upsell')
        ) {
            return new Varien_Object();
        }
        return parent::getItemCollection();
    }
}
