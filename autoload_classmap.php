<?php
	$autoloadClassmap = array(	
			//Helper								
			'Domain\Helper\FileHelper'       				   => __DIR__ . '\module\Domain\Helper\FileHelper.php',			

			//Service
			'Domain\Service\FileService'   					   => __DIR__ . '\module\Domain\Service\FileService.php',
			'Domain\Service\ReportService'  				   => __DIR__ . '\module\Domain\Service\ReportService.php',
			'Domain\Service\UrlService'     				   => __DIR__ . '\module\Domain\Service\UrlService.php',
			'Domain\Service\WebsiteService' 				   => __DIR__ . '\module\Domain\Service\WebsiteService.php',			

			//Factory
			'Domain\Factory\Service\ContentTypeServiceFactory' => __DIR__ . '\module\Domain\Factory\Service\ContentTypeServiceFactory.php',												
	);	

	spl_autoload_register(
		function($name) use (&$autoloadClassmap)
		{
			if ((isset($autoloadClassmap[$name])) && (!empty($autoloadClassmap[$name])))
			{
				include_once($autoloadClassmap[$name]);
			}
		}
	);				
?>