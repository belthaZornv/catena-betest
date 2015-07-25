<?php
namespace Domain\Service;

require_once('autoload_classmap.php');

use Domain\Helper\FileHelper;
use Domain\Service\UrlService;
use Domain\Service\ServiceInterface;

class WebsiteService implements ServiceInterface
{	
	/**
	 * @var String 
	 */	
	private $body;
	
	/**
	 * @var Integer
	 */
	private $size;
	
	/**
	 * @var Integer
	 */
	private $totalTime;
	
	/** 
	 * @var String
	 */
	private $url;

	
	public function __construct($data = null)
	{						
		if ((isset($data)) && (!empty($data)))
		{
			$this->body = $data->body;
			$this->size = $data->size;
			$this->totalTime = $data->totalTime;
			$this->url = $data->url;
		}							
	}
	
	/**
	 * @return String
	 *
	 */
	public function getLoadTime()
	{
		return ($this->totalTime >= 1) ? $this->totalTime . 's' : $this->totalTime . 'ms';
	}
	
	/** 
	 * @return String
	 */
	public function getFileSize($format = null)
	{		
	
		if ($format)
		{	
			//Calling file helper to convert the bytes into KB/MB etc
			$fileHelper = new FileHelper();						
			return $fileHelper->formatSize($this->size);
		}
		
		return $this->size;		    				    		
	}
	
	/** 
	 * @return String
	 */	
	private function getBody()
	{
		return $this->body;
	}

	/** 
	 * @return String
	 */	
	private function getHeader()
	{
		return $this->header;
	}
			
	public function getWebsiteRequest()
	{	
		//print out that the script is in process	
		echo "Loading...\n\n";		
							
		//size for all requests
		$totalSize = 0;
		
		//loading time for all requests
		$totalTime = 0;					
		
		//array to categorize the types of requests and to format the return to the CLI
		$requestDraft = [];				
		
		//types
		$requestDraft['images'] = [];
		$requestDraft['styles'] = [];
		$requestDraft['scripts'] = [];
		$requestDraft['documents'] = [];
		$requestDraft['others'] = [];		
		$requestDraft['requests'] = "";
		
		//Initializing a dom document
		$domDocument = new \DOMDocument();
		
		//suppressing warnings on syntax and loading body (html)
		@$domDocument->loadHTML($this->body);
		
		$xpath = new \DOMXPath($domDocument);						
		$sources = $xpath->evaluate("//@src|//link[@rel='stylesheet']/@href|//link[@rel='icon']/@href");
		
		//FileHelper
		$fileHelper = new FileHelper();
		
		//properties needed inside the for loop
		$type = null;				
		$location = null; 
		$size = 0; 
		$loadTime = 0;
		
		//loop through src attributes (img, script, iframes etc)
		for ($i = 0; $i < $sources->length; $i++) 
		{
			//get value (src)
			$source = $sources->item($i)->nodeValue;		

			//parsing the source
			$url = parse_url($source);
								
			//if host is set then it's an absolute path
			if (isset($url['host']))
			{
				//if the src is absolute but starts with // (remove the //)
				$source = preg_replace('/^\/\/(.*)$/i', '${1}', $source);								
			} 
			else
			{					
				//if a relative path starts with / (go up into directory)
				if (preg_match('/^\/(?!\/)/im', $source)) 
				{		
					//formatting url - if it doesn't have a scheme			
					$inputUrl = (preg_match('/((?:http|https|ftp):\/{2}[0-9a-z_-]+\.+[A-Z.]+)/i', $this->url)) ? $this->url : 'http://' . $this->url;
					
					//parsing the initial URL inserted
					$parsedUrl = parse_url($inputUrl);
					
					//getting the hostname of the URL inserted	
								
					$host = $parsedUrl['host'];	
					
					//setting the source with the url			
					$source = $host . $source;
				} 
				else 
				{					
					//returning the URL (without params/args)  
					$url = dirname($inputUrl);						
					
					//foramtting the URL
					$source = $url . '/' . $source;
				}				
								
			}				
			
			//create url service with the source		
			$urlService = new UrlService($source);
			
			//Initializing cURL
			if ($urlService->curlInitialize())
			{
				//getting content type service (FileService or WebsiteService)
				$typeService = $urlService->getContentTypeService();				
				
				//FileService
				if ($typeService instanceof FileService)
				{
					$type = $sources->item($i)->parentNode->localName;
					$location = $source;
					$size = $typeService->getFileSize();
					$loadTime = $typeService->getLoadTime();																																
				} 
				
				//WebsiteService
				else if ($typeService instanceof WebsiteService)		
				{
					$request = new \stdClass;
					
					$type = 'document';
					$location = $source;
					$size = $typeService->getFileSize();
					$loadTime = $typeService->getLoadTime();					
				}
				
				//Categorizing request
				switch ($type)
				{
					case 'img':
						$requestDraft['images'][] = "- Location: " . $location . 
													"\n- - Size: " . $fileHelper->formatSize($size) . 
													"\n- - Finish: " . $loadTime . "\n";
					break;
					case 'link':
						$requestDraft['styles'][] = "- Location: " . $location . 
													"\n- - Size: " . $fileHelper->formatSize($size) . 
													"\n- - Finish: " . $loadTime . "\n";	
					break;
					case 'script':
						$requestDraft['scripts'][] = "- Location: " . $location . 
													 "\n- - Size: " . $fileHelper->formatSize($size) . 
													 "\n- - Finish: " . $loadTime . "\n";	
					break;
					case 'document':
					    $requestDraft['documents'][] = "- Location: " . $location . 
													   "\n- - Size: " . $fileHelper->formatSize($size) . 
													   "\n- - Finish: " . $loadTime . "\n";
					break;	
					default:
						$requestDraft['others'][] = "- Location: " . $location . 
													"\n- - Size: " . $fileHelper->formatSize($size) . 
													"\n- - Finish: " . $loadTime . "\n";
					break;
				}
				
				//Incrementing the totals
				$totalSize = ($totalSize + $size);
				$totalTime = (floatval($totalTime) + floatval($loadTime));				
			}														
		}		

		//Format for CLI into sections including size.		
		foreach (array_keys($requestDraft) as $key => $type)
		{				
			if ($type != 'requests')
			{
				$requestDraft['requests'] .= "\nRequests: " . count($requestDraft[$type]) . " Type: $type \n";
				
				for ($i = 0; $i < count($requestDraft[$type]); $i++)
				{
					$requestDraft['requests'] .= $requestDraft[$type][$i];
				}							
			}											
		}										
		
		//getting the total size of the requests 		
		$totalSize = $fileHelper->formatSize($totalSize);
		$requestDraft['totalSize'] = "\nTotal requests download size: $totalSize\n";		
		
		//getting the total time to load the website + requests
		$totalTime = (floatval($totalTime) + floatval($this->totalTime));		
		$totalTime = ($totalTime > 1) ? $totalTime . 's' : $totalTime . 'ms';
		
		$requestDraft['loadTime'] = "Total load time: $totalTime";		
		
		//return draft of the requests
		return $requestDraft;    
	}									
}
?>