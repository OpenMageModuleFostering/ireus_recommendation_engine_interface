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
 * @package    Ireus_Csv
 * @copyright  Copyright (c) 2010 prudsys AG (http://www.prudsys.com)
 * @author     Silvio Steiger, Germany
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * 
 * $Id: Csv.php 1555 2010-02-05 09:28:52Z sist $
 */


class Ireus_Model_Csv extends Ireus_Object
{	
	/**
     * Read CSV from file
     *
     * @param string $filename
     * @return array
     */
    public static function readFile ($file, $quoting = '"', $delimiter = '|', $lineending = "\r\n")
    {
        # Check file
        if (!file_exists($file)) {
            trigger_error('File does not exist: '.$file, E_USER_ERROR);
            return;	
        }
		
		# open the file for reading
		$handle = fopen($file, "r");

		# Read first line of csv file to catch the header
		$header = fgetcsv($handle, 0, $delimiter);
		$headercount = count($header);
		
		# Read the rest of the csv file to catch the data
		while (!feof($handle)) {
			$line = fgetcsv($handle, 0, $delimiter, $quoting);
			
			if (count($line) == $headercount
			) {
				# built array
				$data[] = array_combine($header, $line);
			}
		}
		
		# Close the handle
		fclose($handle);
		
		return $data;
	}
	
	
    /**
     * Write array to file in Csv format
     *
     * @param string $file
     * @return array
     */
    public static function writeFile ($lines, $file, $quoting = '"', $delimiter = '|', $lineending = "\r\n")
    {
        # Check, if file is writable first.
        if ($handle = fopen($file, 'w')) {

            # get content as string
	        $content = self::writeString($lines, $quoting, $delimiter, $lineending, true);

            if (fwrite($handle, $content) === FALSE) {
            	trigger_error('Can\'t write to file: '.$file, E_USER_ERROR);
                return;
            }
            fclose($handle);
        }
        else {
        	trigger_error('Can\'t create or open file: '.$file, E_USER_ERROR);
        }
    }
	
	
	/**
     * Write array to string in Csv format
     *
     * @param string $file
     * @return array
     */
    public static function writeString ($lines, $quoting = '"', $delimiter = '|', $lineending = "\r\n", $sendCsvHeader = true)
    {
        # Check parameter
        if (!is_array(reset($lines))
        ){
            return false;
        }
        
        $string = '';

        # write header
        if ($sendCsvHeader
        ) {
            $string = implode(array_keys(reset($lines)), $delimiter) . $lineending;
        }

        # write data
        foreach ($lines as $line
        ) {
            foreach ($line as $key => $value
            ) {
            	if (is_string($value)
            	) { 
            		$string .= $quoting . $value . $quoting . $delimiter;
            	}
            	else 
            	{
            		$string .= $value . $delimiter;
            	}
            }
            $string = rtrim($string, $delimiter);
            $string .= $lineending;
        }
        
        return $string;
    }
}