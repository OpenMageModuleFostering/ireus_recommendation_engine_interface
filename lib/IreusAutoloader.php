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
 * @package    IreusAutoloader
 * @copyright  Copyright (c) 2010 prudsys AG (http://www.prudsys.com)
 * @author     Silvio Steiger, Germany
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */


// Activate IREUS PHP Framework autoloading
// via SPL autoload stack.
// Zend compatible autoloaders load IREUS automatically
// if includepath to IREUS is correctly set.
#IreusAutoloader::getInstance()->register();

// load manually if no autoloading (loads classes if used or not)
#if (!IreusAutoloader::getInstance()->_autoload) {
#	IreusAutoloader::getInstance()->autoload('Ireus_Object');
#}


final class IreusAutoloader
{
	/**
     * Class instance
     *
     * @var object
     */
	public static $_instance;
	
	
	/**
     * Use autoload marker
     *
     * @var bool
     */
    public $_autoload;
    
	
    /**
     * Singleton pattern implementation
     *
     * @return IreusAutoloader
     */
    public static function getInstance()
    {
        if (!self::$_instance) {
            self::$_instance = new self;
        }
        return self::$_instance;
    }

    
    /**
     * Register SPL autoload function
     * 
     * @return Null
     */
    public function register()
    {
   		spl_autoload_register(array(self::getInstance(), 'autoload'));
        if (strpos(get_include_path(), dirname(__FILE__)) === false
        ) {
           set_include_path(get_include_path() . PATH_SEPARATOR . dirname(__FILE__));
        }
        $this->_autoload = true;
   		
   		// for compatibility reasons
        if (function_exists('__autoload')) {
            spl_autoload_register('__autoload');          
        }
    }
   
    
	/**
     * Load class source code
     *
     * @param string $class
     * @return included file or false
     */
    public function autoload($class)
    {
    	$classFile = str_replace(' ', DIRECTORY_SEPARATOR, ucwords(str_replace('_', ' ', $class))).'.php';
    	
    	foreach (explode(PATH_SEPARATOR, get_include_path()) as $path) {
        	if (file_exists($path . DIRECTORY_SEPARATOR . $classFile)) {
        		return include_once $classFile;
        	}
        }
       	return false;
    }
}