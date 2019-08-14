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
 * @package    Ireus_Export
 * @copyright  Copyright (c) 2010 prudsys AG (http://www.prudsys.com)
 * @author     Silvio Steiger, Germany
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * 
 * $Id: Export.php 1555 2010-02-05 09:28:52Z sist $
 */


class Ireus_Controller_Export extends Ireus_Object
{
	/**
     * Class constants
     * 
     * @var mixed
     */
    const _QUOTING = '"';
	const _COLUMNSEPARATOR = '|';
	const _LINESEPARATOR = "\r\n";
	
	
   /**
     * Class instance
     *
     * @var object
     */
    protected static $_instance;
    
	
    /**
     * Key map for renaming product array keys
     * 
     * @var array
     */
    protected $_productColumns;
    
    
    /**
     * Key map for renaming category array keys
     * 
     * @var array
     */
    protected $_categoryColumns;
    
    
    /**
     * Key map for renaming order array keys
     * 
     * @var array
     */
    protected $_orderColumns;

    
    /**
     * Key map for renaming order array keys
     * 
     * @var array
     */
    protected $_sendCsvHeader = true;

    
    /**
     * Length map for Ireus product array keys
     * 
     * @var array
     */
    protected $_productLengths = array(
        'pid' => array(0, 'string'),
        'netUnitPrice' => array(0, 'float'),
        'sku' => array(0, 'string'),
        'name' => array(400, 'string'),
        'masterUID' => array(0, 'string'),
        'brand' => array(60, 'string'),
        'manufacturer' => array(60, 'string'),
        'quantityUnit' => array(10, 'string'),
        'quantity' => array(0, 'integer'),
        'netPurchasePrice' => array(0, 'float'),
        'strikeOutPrice' => array(0, 'float'),
        'reward' => array(0, 'float'),
        'description' => array(2000, 'string'),
        'URL' => array(0, 'string'),
        'imageURL' => array(0, 'string'),
        'onlineFlag' => array(0, 'string'),
        'param1' => array(2000, 'string'),
        'param2' => array(2000, 'string'),
        'param3' => array(2000, 'string'),
        'param4' => array(2000, 'string'),
        'param5' => array(2000, 'string'),
        'stock' => array(0, 'integer'),
        //'type' => array(0, 'string'),
        //'rank' => array(0, 'float'),
        //'initstock' => array(0, 'float')    
    );

    
    /**
     * Length map for Ireus category array keys
     * 
     * @var array
     */
    protected $_categoryLengths = array(
        'pid' => array(0, 'string'), 
        'cid' => array(0, 'string'),
        'cidparent' => array(0, 'string'),
        'cname' => array(200, 'string')
    );
    
    
    /**
     * Base directory set in <base> header tag
     * 
     * @var string
     */
    protected $_base;
    
    
    /**
     * reduce urls to url path
     * 
     * @var string
     */
    protected $_removehostname = true;
    
    
    /**
     * reduce urls to url path
     * 
     * @var string
     */
    private $_setimagedimensions = false;
    
    
    /**
     * Singleton pattern implementation
     *
     * @return Ireus_Controller_Export
     */
    public static function getInstance()
    {
        if (!self::$_instance) {
            self::$_instance = new self;
        }
        return self::$_instance;
    }
    
    
    /**
     * Make products IDs unique
     * 
     * @param array $lines
     * @param array $keymap
     * @return null
     */
	public function uniqueProductIds(&$lines, $pidname, $pricestring)
    {
    	// sort for pid and price
        foreach($lines as $linekey => $line
        ) {
        	$pids[$linekey] = $line[$pidname];
        	$prices[$linekey] = $line[$pricestring];
        }
        array_multisort($pids, SORT_ASC, $prices, SORT_ASC, $lines);

        $pids = 
    	$newlines = array();
    	
        foreach($lines as $linekey => $line
        ) {
        	if (!in_array($line[$pidname], $pids)
        	) {
	        	$pids[] = $line[$pidname];
        	}
        	else
        	{
        		unset($lines[$linekey]);
        	}
        }
        
        return $lines;
    }
    
	
	/**
	 * Creates CSV string with products data in Ireus format Vers. 1.0
	 *
	 * @param array $products
	 * @param string $baseurl
	 * @return string
	 */
	public function createProductsCsv ($products)
	{
		$this->renameKeys($products, $this->getProductColumns());
        $this->checkReward($products);
        $this->cropStrings($products, $this->_productLengths);
        if ($this->_setimagedimensions
        ) {
            $this->createImageDimensions($products);
        }
                
		$output = Ireus_Model_Csv::writeString($products, self::_QUOTING, self::_COLUMNSEPARATOR, self::_LINESEPARATOR, $this->_sendCsvHeader);
	    $this->_sendCsvHeader = false;

	    return $output;
	}
	
	
   /**
     * Creates CSV string with categories data in Ireus format Vers. 1.0
     *
     * @param array $products
     * @param string $baseurl
     * @return string
     */
    public function createCategoriesCsv ($categories)
    {
        $this->renameKeys($categories, $this->getCategoryColumns());
        $this->cropStrings($categories, $this->_categoryLengths);
        
        $output = Ireus_Model_Csv::writeString($categories, self::_QUOTING, self::_COLUMNSEPARATOR, self::_LINESEPARATOR, $this->_sendCsvHeader);
        $this->_sendCsvHeader = false;
        
        return $output;
    }
    
    
   /**
     * Export CSV file with historic order data
     *
     * @param array $orders
     * @param bool $sendheader
     * @return string
     */
    public function exportOrdersCsv ($orders, $sendheader = false)
    {
        $this->renameKeys($orders, $this->getOrderColumns());
        $this->sortLines($orders, 'tid');
        
        if ($sendheader) {
            // send header to directly output file for download
            header('Content-Type: text/csv');
            header('Content-disposition: attachment; filename=HistoricOrders.csv');
            
            echo Ireus_Model_Csv::writeString($orders, self::_QUOTING, self::_COLUMNSEPARATOR, self::_LINESEPARATOR);
        }
        else {
        	
        	$output = Ireus_Model_Csv::writeString($orders, self::_QUOTING, self::_COLUMNSEPARATOR, self::_LINESEPARATOR, $this->_sendCsvHeader);
            $this->_sendCsvHeader= false;
            
            return $output;
        }
    }
    
    
    /**
     * Set $_base property
     * 
     * @param string $base
     * @return this
     */
    public function setBase($base)
    {
    	$this->_base = $base;
    	return $this;
    }
    
    
    /**
     * Set $_removehostname property, for changing urls into relative urls (default = true)
     *
     * @param bool $value
     * @return this
     */
    public function disableRemoveHostname($value = true)
    {
        $this->_removehostname = !$value;
        return $this;
    }
    
    
    /**
     * Set $_setimagedimensions property, for filling param5 with image dimensions (default = false)
     *
     * @param bool $value
     * @return this
     */
    public function setImageDimensions($value = true)
    {
        $this->_setimagedimensions = $value;
        return $this;
    }
    
    
    /**
     * Creates BaseURL without protocol servername and $_base directory
     * 
     * @param string $shopurl (http://{shopdomain}/{basedir}/otherstuff
     * @param string $basedir
     * @return string
     */
    public function getPath ($shopurl)
    {   
    	if (preg_match('/^((https?)\:\/\/)/', $shopurl)) {
        
            return preg_replace(
                '/^\/*/', 
                '/', 
                preg_replace(
                    array('/^((https?)\:\/\/)(([A-Za-z0-9-.]*)\.([a-z]{2,3})|localhost)/', '/'.$this->_base.'/'),
                    '',
                    $shopurl,
                    1
                )
            );
    	}
    	elseif (preg_match('/^(([A-Za-z0-9-.]*)\.([a-z]{2,3})|localhost)/', $shopurl)) {

            return preg_replace(
                '/^\/*/', 
                '/', 
                preg_replace(
                    array('/^(([A-Za-z0-9-.]*)\.([a-z]{2,3})|localhost)/', '/'.$this->_base.'/'),
                    '',
                    $shopurl,
                    1
                )
            );
    	}
    	else {
    		
            return preg_replace(
                '/^\/*/', 
                '/', 
                str_replace(array($this->_base, 'localhost'), '', $shopurl));    		
    	}
    }
    
	
    /**
     * Get product columns keymap
     *
     * @return array
     */
    protected function getProductColumns()
    {	
    	$basecolumns = array(
            'pid' => 'pid',
            'netUnitPrice' => 'netUnitPrice',    
            'sku' => 'sku',
            'name' => 'name',
            'masterUID' => 'masterUID',
            'brand' => 'brand',
            'manufacturer' => 'manufacturer',
            'quantityUnit' => 'quantityUnit',
            'quantity' => 'quantity',
            'netPurchasePrice' => 'netPurchasePrice',
            'strikeOutPrice' => 'strikeOutPrice',
            'reward' => 'reward',
            'description' => 'description',
            'URL' => 'URL',
            'imageURL' => 'imageURL',
            'onlineFlag' => 'onlineFlag',
            'param1' => 'param1',
            'param2' => 'param2',
            'param3' => 'param3',
            'param4' => 'param4',
            'param5' => 'param5',
            //'stock' => 'stock',
            //'type' => 'type',
            //'rank' => 'rank',
            //'initstock' => 'initstock'
        );
        
        if (!$this->_productColumns) {
            $this->_productColumns = array();
        }
       
        return array_merge($basecolumns, $this->_productColumns);
    }
    
    /**
     * Set $_productColumns array
     *
     * @param array $keymap
     * @return Ireus_Controller_Export
     */
    public function setProductColumns($keymap)
    {
        $this->_productColumns = $keymap;
        
        return $this;
    }

    
    /**
     * Get category columns keymap
     *
     * @return array
     */
    protected function getCategoryColumns()
    {
    	$basecolumns = array(
            'pid' => 'pid',
            'cid' => 'cid',
            'cidparent' => 'cidparent',
            'cname' => 'cname'
        );
        
        if (!$this->_categoryColumns) {
            $this->_categoryColumns = array();
        }

        return array_merge($basecolumns, $this->_categoryColumns);
    }
    
    
    /**
     * Set $_categoryColumns array
     *
     * @param array $keymap
     * @return Ireus_Controller_Export
     */
    public function setCategoryColumns($keymap)
    {
        $this->_categoryColumns = $keymap;
        
        return $this;
    }
    
    
    /**
     * Get order columns keymap
     *
     * @return array
     */
    protected function getOrderColumns()
    {
        $basecolumns = array(
            'tid' => 'tid',
            'pid' => 'pid',
        );
        
        if (!$this->_orderColumns) {
            $this->_orderColumns = array();
        }

        return array_merge($basecolumns, $this->_orderColumns);
    }
    
    
    /**
     * Set $_orderColumns array
     *
     * @param array $keymap
     * @return Ireus_Controller_Export
     */
    public function setOrderColumns($keymap)
    {
        $this->_orderColumns = $keymap;
        
        return $this;
    }
	
	
    /**
     * Renames keys of given data array according to the keymap
     * 
     * @param array $lines
     * @param array $keymap
     * @return null
     */
    protected function renameKeys(&$lines, $keymap)
    {
        foreach($lines as $linekey => $line) {
            $array = array();
            foreach ($keymap as $key => $value) {
                $array[$key] = array_key_exists($value, $line) ? $line[$value] : '';
            }
            $lines[$linekey] = $array;
        }
    }
    
    
    /**
     * Checks reward
     * 
     * @param array $lines
     * @return null
     */
    protected function checkReward (&$lines)
    {
        $reward = false;
        
        foreach ($lines as $key => $line
        ) {
            $lines[$key]['reward'] = floatval($lines[$key]['reward']);
            if (!$reward) {
                $reward = ($lines[$key]['reward'] == 0 ? false : true);
            }
        }

        if (!$reward
        ) {
            foreach ($lines as $key => $line
            ) {
                $lines[$key]['reward'] = $lines[$key]['netUnitPrice'];
            }
        }
    }
    
    
    /**
	 * Crops and encodes given data values according to the lengthsmap
	 * 
	 * @param array $lines
	 * @param array $lengthsmap
	 * @return null
	 */
	protected function cropStrings (&$lines, $lengthsmap)
	{
		foreach ($lines as $linekey => $line) {
			reset($lengthsmap);
			while (list($key, $val) = each($lengthsmap)) {
				$line[$key] = strip_tags($line[$key]);
				
				// cast values
				if (!settype($line[$key], $val[1])
				) {
					trigger_error('Couldn\'t cconvert '.$key.'='.$line[$key].' into '.$val[1].'!');
				}
				
                if (!is_string($line[$key])
                ) {
                    continue;
                }
                else
                {
                	$line[$key] = str_replace(array("\n", "\r\n", "\r"), '', $line[$key]);
                	$line[$key] = str_replace('\'', '&#39;', $line[$key]);
                }
			    
                // reduce urls by servername and basedir
                if ($this->_removehostname 
                    && ($key == 'url' 
                        || $key == 'imageurl'
                        || (strpos($key, 'param') !== false
                            && strpos($line[$key], '/') !== false))
                    && $line[$key]
                ) {
                    $line[$key] = $this->getPath($line[$key]);                      
                }
                    
				// prepare fields and numbers that should be alpanumeric 
			    if ($val[0] == 0) {
                    if (htmlentities($line[$key], ENT_COMPAT, 'UTF-8', false) != NULL) {
                        // $line[$key] = $line[$key];                 
                    }
                    else {
                        $line[$key] = utf8_encode($line[$key]);
                    }
                }
                
                // prepare fields that may contain everything
                else {
                    if (($field = htmlentities($line[$key], ENT_COMPAT, 'UTF-8', false)) != NULL) {
                        $line[$key] = $field;
                    }
                    elseif (($field = htmlentities($line[$key], ENT_COMPAT, 'cp1252', false)) != NULL) {
                        $line[$key] = utf8_encode($field);
                    }
                    else { 
                        $line[$key] = utf8_encode(htmlentities($line[$key], ENT_COMPAT, 'iso-8859-15', false));
                    }
                
                    // crop fields if needed
                    if (strlen($line[$key]) > $val[0]) {
                        if ($val[0] > 100) {
                            $line[$key] = substr($line[$key], 0, strripos(substr($line[$key], 0, $val[0]-4), ' ')).' ...';
                        } 
                        elseif (($amppos = strrpos(substr($line[$key], 0, $val[0]-3), '&')) > ($val[0] - 11)) {
                        	$line[$key] = substr($line[$key], 0, $amppos).'...';
                        }
                        else {
                            $line[$key] = substr($line[$key], 0, $val[0]-3).'...';
                        }
                    }
                }
            }
            $lines[$linekey] = $line;
        }
	}
	
	
    /**
     * Sort 2-dimensional array
     * 
     * @param array $lines (data array)
     * @param string $sortkey (data key to sort)
     * @return void
     */
    protected function sortLines (& $lines, $sortkey)
    {
        if (!empty($lines)
        ) {
            foreach ($lines as $key =>$line
            ) {
                $sort[$key] = $line[$sortkey];
            }
            array_multisort($sort, SORT_ASC, $lines);
        }
    }
	
	
    /**
     * Set param4 and param5 to image dimensions (look at PHP function: getimagesize()))
     *
     * @param array $products
     * @return $products
     */
    protected function createImageDimensions (&$products)
    {
        // Check for free parameter 5
        foreach ($products as $product
        ) {
            if ($product['param4'] != ''
                || $product['param5'] != ''
            ) {
                trigger_error('Parameters 4 and 5 not empty! Can\'t save image dimensions!', E_USER_WARNING);
                return;
            }
        }
        
        // Get image dimensions
        foreach ($products as $key => $product
        ) {
            if ($this->_removehostname
            ) {
                $file = $_SERVER['DOCUMENT_ROOT'].$this->_base.$this->getPath($product['imageURL']);
                if (is_readable($file)
                ) {
                    $is_readable = true;
                }
                else
                {
                    $is_readable = false;
                }
            }
            else
            {            	
                $file = $product['imageURL'];
                $is_readable = true;
            }
            
            if ($is_readable
            ) {
                $imgdimensions = getimagesize($file);
                if ($imgdimensions[3]
                ) {
                    $products[$key]['param4'] = $imgdimensions[0];
                    $products[$key]['param5'] = $imgdimensions[1];
                }
            }
        }
    }
}