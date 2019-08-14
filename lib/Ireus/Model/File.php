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
 * @package    Ireus_File
 * @copyright  Copyright (c) 2010 prudsys AG (http://www.prudsys.com)
 * @author     Silvio Steiger, Germany
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * 
 */


class Ireus_Model_File extends Ireus_Object
{	
	/**
	 * Deletes directory recursively
	 * 
	 * @param string $dir
	 * @return void
	 */
    public static function deleteDirectory ($dir)
    {
        if (is_dir($dir)
            && substr($dir, -1) !== '/'
        ) {
            $dir .= '/';
        }
        
        $files = glob($dir . '*', GLOB_MARK);
        
        if (count($files) > 0
        ) {
	        foreach ($files as $file)
	        {
	            if (is_dir($file)
	            ) {
	                self::deleteDirectory($file);
	            }
	            else
	            {
	                unlink($file);
	            }
	        }
        }
        
        if (is_dir($dir)
        ) { 
            rmdir($dir);
        }
    }

    
    /**
     * Write file to disk
     *
     * @param string $file
     * @param string $content
     * @return array
     */
    public static function writeFile ($file, $content)
    {
        $path = dirname($file);
        if (!is_writable($path)
        ) {
            if (!mkdir($path, 0777, true)
            ) {
                trigger_error('Can\t create or write to directory: '.$path, E_USER_ERROR);
                return false;
            };
        }
            
        if ($handle = fopen($file, 'w')
        ) {
            if (fwrite($handle, $content) === false
            ) {
                trigger_error('Can\'t write to file: '.$file, E_USER_ERROR);
                return;
            }
            fclose($handle);
        }
        else 
        {
            trigger_error('Can\'t create or open file: '.$file, E_USER_ERROR);
        }
    }
}