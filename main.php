<?php
//Set timezone
date_default_timezone_set('Europe/Malta');
	
//Loading classmap and autoloading function	
require_once('autoload_classmap.php');

//Dependencies
use Domain\Service\UrlService;
use Domain\Service\FileService;
use Domain\Service\WebsiteService;
use Domain\Service\ReportService;

//available CLI Arguments
$availableArguments  = [
				"url:",
				"report::",
			 ];    
	
//get CLI command arguments
$arguments = getopt(null,$availableArguments);

if ((isset($arguments['url'])) && (!empty($arguments['url'])))
{	
	//Getting an instance of the url service
	$urlService = new UrlService($arguments['url']);		
	
	//Initializing cURL
	if ($urlService->curlInitialize())
	{		
		//getting the service depending on the link type
		$typeService = $urlService->getContentTypeService();
		
		if ($typeService instanceof FileService)
		{
			//true = format size into kb/mb/gb etc.
			$fileSize = $typeService->getFileSize(true);
			echo "Total file size: $fileSize";
		} 
		else if ($typeService instanceof WebsiteService)		
		{
			//Get requests data
			$websiteRequests = $typeService->getWebsiteRequest();
			
			//echo required data
			echo $websiteRequests['requests'], $websiteRequests['totalSize'], $websiteRequests['loadTime'];	
			
			//checking if we should generate a report
			if ((isset($arguments['report'])) && (!empty($arguments['report'])))
			{
				$reportService = new ReportService();				
				$reportService->generateReport($websiteRequests, $arguments['report']);
			}					
		}
	}
	else
	{
		echo "\nError initializing, make sure you're inserting a correct website";
	}			
}
else
{
	echo "Please make sure you're inserting a URL by using --url=YOUR_URL";	
}			
?>