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
 * @package    Ireus_Cache
 * @copyright  Copyright (c) 2010 prudsys AG (http://www.prudsys.com)
 * @author     Silvio Steiger, Germany
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  
 *  
 * INFORMATION ABOUT CACHING IREUS CONTENT
 * 
 * Caching of IREUS Recommendations may used ONLY for presenting content for SEARCH ENGINE CRAWLERS 
 * and as backup for users without JavaScript enabled. DO NOT USE THIS AS YOUR DEFAULT METHOD!
 * 
 * If you use caching as proposed (recommended cache lifetime of some hours) you indeed don't have 
 * personalization features on cached content but you don't increase the response time of your shop server. 
 * If you decrease the cache lifetime to 0 seconds for enabling personalization features you'll 
 * increase the response time of your shop server because then your shop script needs to wait for response 
 * from ireus.net with each request for a shop page.
 * 
 * Therefore for preseting personalized IREUS Recommendations the prefered method is client side JavaScript 
 * with backup from cached static IREUS Recommendations (cache lifetime = 7200 seconds is recommended).
 * 
 */


class Ireus_Controller_Cache extends Ireus_Object
{
	/**
     * Cache lifetime (seconds) 
     * Recommended: 86400, Minimum: 7200 (not to decrease shop performance)
     *
     * @var int
     */
    protected static $_cachelifetime = 86400;

    
    /**
     * Complete cache deletion interval (days)
     * 
     * Deleting the cache may take some seconds.
     * Don't do it too often.
     *
     * @var int
     */
    protected static $_deletioninterval = 30;
    
    
    /**
     * Base URL for IREUS Recommendation Service 
     * 
     * @var string
     */
    protected static $_servleturl = 'http://www.ireus.net/ireus-server'; //http://salieri.prudsys.com:8180/rde_server
    
    
    /**
     * Gets recommendations either from cache or the Ireus Online Service
     * 
     * @param string $reid
     * @param string $event
     * @param string $id
     * @param string $cachepath
     * @return string
     */
    public static function getContent ($reid, $sid, $event, $id='', $cachepath, $sidparam, $charset='UTF-8')
    {   
        $displayerrors = ini_set("display_errors", false);
        
        $output = '';
        
        if (!$reid
            || !$event
        ) {
            trigger_error('No reid/event given for caching.', E_USER_ERROR);
            return false;
        }
        
        $filename = md5($event.$id).'.txt';
        $file = $cachepath 
              . DIRECTORY_SEPARATOR 
              . implode(
                    DIRECTORY_SEPARATOR, 
                    str_split(
                        substr(
                            $filename,
                            0,
                            3
                        )
                    )
                )
              . DIRECTORY_SEPARATOR 
              . $filename;

        if (!is_readable($file)
            || (time() - filemtime($file)) > self::$_cachelifetime
            || !($output = file_get_contents($file))
        ) {
            // Get content from IREUS
            $output = self::requestRecomms($reid, $sid, $event, $id, $output, $file, $charset);
            $output = str_replace($sidparam, '', $output);
            
            // Cache content to disk
            Ireus_Model_File::writeFile($file, $output);            
        }
        
        ini_set("display_errors", $displayerrors);

        return $output;
    }
    
    
    /**
     * Deletes cache completely
     * 
     * @param string $cachepath
     * @return void
     */
    public static function deleteCache ($cachepath)
    {
        $file = $cachepath 
              . DIRECTORY_SEPARATOR 
              . 'lastdeletion.txt';
        
        if (!is_readable($file)
            || (time() - filemtime($file)) > (self::$_deletioninterval * 86400)
        ) {
            // Delete cache
            Ireus_Model_File::deleteDirectory($cachepath);

            // Create lastdeletion info
            Ireus_Model_File::writeFile($file, date('r', time()));
        }
    }
    
    
    /**
     * Get recommendations from IREUS Online Service or use cached recomms on error
     *
     * @param string $reid
     * @param string $event
     * @param string $id
     * @param string $output
     * @param string $file
     * @return string
     */
    protected static function requestRecomms($reid, $sid, $event, $id, $output='', $file='', $charset='UTF-8')
    {
        $sid = md5($sid);
        
        $requesturlpart = '/res/'.trim($reid)
                        . '/recomm/'.$event.'_cache'
                        . '/sid/'.$sid
                        . '';
        
        $requestParameters = 'userID='.$sid.'&';
        if ($event == 'product'
        ) {
            $requestParameters .= 'itemid='.$id.'&';
        }
        elseif ($event == 'user'
                && $id != false
        ) {
            $requestParameters .= 'categoryid='.$id.'&';
        }
        
        $uri = self::$_servleturl
             . $requesturlpart
             . '?'
             . $requestParameters
             . '';

        $context = stream_context_create(
            array(
                'http' => array(
            		'method' => 'GET',
                    'timeout' => 1.5   // seconds
                )
            )
        );
        
        // Get and check recomms from ireus.net
        try {
            $request = file_get_contents($uri, false, $context);
        } catch (Exception $e) {
   	        $request = false;
        }
             
        if ($request
        ) {
            if (strpos($request, 'if((typeof IreusHandler') === 0
                && ($start = strpos($request, '(\'') + 2) > 2
                && ($end = strpos($request, '\')', $start)) > $start
            ) {
                $output = str_replace(
                    array('\\\''), 
                    array('\''),
                    iconv(
                    	"UTF-8",
                        $charset, 
                        self::decodeUnicode(
                            substr(
                                $request,
                                $start,
                                $end - $start
                            )
                        )
                    )
                );
            }
        }

        // Use cached output on errors
        if (!$output
        ) {
            try {
                $output = file_get_contents($file);
            } catch (Exception $e) {
                $output = false;
            }
            
            if (!$output
            ) {
                $output = '<!-- No Recommendations -->';
            }
        }

        return $output;
    }
    
    
    /**
     * Decodes an unicode (\uXXXX) string to utf8
     * 
     * @param string $value
     * @return string
     */
    protected static function decodeUnicode ($value)
    {
        return preg_replace("/\\\u([0-9A-F]{4})/ie", "utf8_encode(chr(hexdec('\\1')))", $value);
    }
}