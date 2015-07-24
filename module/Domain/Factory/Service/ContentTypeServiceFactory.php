<?php
namespace Domain\Factory\Service;

require_once('autoload_classmap.php');

use Domain\Service\FileService;
use Domain\Service\WebsiteService;

class ContentTypeServiceFactory {
	
	//using a factory to determine what type of content and for injection (url)
    public function create($type = null, $data = null) {
		//checking if the url is set, and isn't empty
		if ((isset($data)) && (!empty($data)))
		{
			switch ($type)
			{
				//returning the service depending on the content type and injecting the data
				case 'file':
					return new FileService($data);
				break;
				case 'website':
					return new WebsiteService($data);
				break;
			}			
		}
    }
	
}	
?>