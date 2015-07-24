<?php
namespace Domain\Service;

interface ServiceInterface {		
	public function getFileSize($format = null);
	public function getLoadTime();	
}
?>