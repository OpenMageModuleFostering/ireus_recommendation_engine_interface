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
 * @package    Ireus_Client
 * @copyright  Copyright (c) 2010 prudsys AG (http://www.prudsys.com)
 * @author     Silvio Steiger, Germany
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * 
 * $Id: Client.php 1555 2010-02-05 09:28:52Z sist $
 */


class Ireus_View_Client extends Ireus_Object
{

    /**
     * Returns an html chunk with Ireus event definitions
     *
     * @param string $event
     * @param array $params
     * @return string
     */
    public static function getEvent($event, $params, $cachedrecomms='')
    {
        switch ($event) {
        	case 'home':
        	case 'user':
        		$output = self::getView($event);
        		$output .= self::getView('event');
        		break;
        		
            case 'product':
            	$output = self::getView('product');
                $output .= self::getView('event');
                break;                
                
            case 'basket':
            case 'order':
            default:
            	$output = self::getView('event');       	            	
        }
        
        $placeholdervalues = array(
            'IreusEventParams' => json_encode($params),
            'IreusCachedRecomms' => $cachedrecomms
        );
         
        return self::fillView($output, $placeholdervalues);
    }
    
    
    /**
     * Returns an html chunk with Ireus call definition
     *
     * @return string
     */
    public static function getCall()
    {
    	return self::getView('call');
    }

    
    /**
     * Loads view template
     * 
     * @param string $tpl
     * @return string
     */
    protected static function getView($tpl)
    {
    	$filename = dirname(__FILE__). DIRECTORY_SEPARATOR . 'Client' . DIRECTORY_SEPARATOR . $tpl . '.tpl.php';
    	if (!is_file($filename)) {
        	echo 'Errortest: '.trigger_error($filename.' not found.', E_USER_WARNING);
        	return false;
        }

        try {
            $contents = file_get_contents($filename);
        } catch (Exception $e) {
        	trigger_error('Can\'t read file: '.$filename, E_USER_WARNING);
        	return false;
        }
        
        return $contents;
    }
    
    
    /**
     * Fills an html chunk with values
     *
     * @param string $view (html chunk with placeholders)
     * @param array $params (array of 'placeholdername' => 'value')
     * @return string
     */
    protected static function fillView($view, $params)
    {
    	foreach ($params as $key => $val) {
    		$view = str_replace('[[+'.$key.']]', $val, $view);
    	}
    	
        // Clean
        $view = preg_replace('/\[\[\+.+?\]\]/', '', $view);
    	
    	return $view;
    }
}