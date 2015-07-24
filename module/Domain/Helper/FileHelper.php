<?php
namespace Domain\Helper;

class FileHelper {
	
	/**
	 * @return String
	 */
	public function formatSize($bytes) { 
	    $units = [
					'B', 'KB', 'MB', 'GB', 'TB'
				 ]; 
		
	    $bytes = max($bytes, 0);
					 		 
	    $pow = floor(($bytes ? log($bytes) : 0) / log(1024)); 
	    $pow = min($pow, count($units) - 1); 
	
		$bytes /= pow(1024, $pow);
		
	    return round($bytes, 1) . ' ' . ($units[$pow]); 
	} 
	
}
?>