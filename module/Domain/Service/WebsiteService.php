<?php
namespace Domain\Service;

require_once('autoload_classmap.php');

use Domain\Helper\FileHelper;
use Domain\Service\UrlService;

class WebsiteService 
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
		//used to store all the requests
		$requests = [];
			
		$domDocument = new \DOMDocument();
		
		//suppressing warnings on syntax and loading body (html)
		@$domDocument->loadHTML($this->body);
		
		$xpath = new \DOMXPath($domDocument);						
		$sources = $xpath->evaluate("//@src|//link[@rel='stylesheet']/@href|//link[@rel='icon']/@href");
			
		echo "Loading...\n\n";
		
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
					$request = new \stdClass;
					
					$request->type = $sources->item($i)->parentNode->localName;
					$request->location = $source;
					$request->size = $typeService->getFileSize();
					$request->loadTime = $typeService->getLoadTime();
											
					$requests[] = $request;																	
				} 
				
				//WebsiteService
				else if ($typeService instanceof WebsiteService)		
				{
					$request = new \stdClass;
					
					$request->type = 'document';
					$request->location = $source;
					$request->size = $typeService->getFileSize();
					$request->loadTime = $typeService->getLoadTime();
					
					$requests[] = $request;	
				}
			}														
		}
		//array to categorize the types of requests and to format the return to the CLI
		$requestDraft = [];				
		
		//types
		$requestDraft['images'] = [];
		$requestDraft['styles'] = [];
		$requestDraft['scripts'] = [];
		$requestDraft['documents'] = [];
		$requestDraft['others'] = [];		
		$requestDraft['requests'] = "";
		
		//size for all requests
		$totalSize = 0;
		
		//loading time for all requests
		$loadTime = 0;
		
		//FileHelper 
		$fileHelper = new FileHelper();
		
		//putting requests into categories and formatting
		foreach ($requests as $key => $request)
		{
			switch ($request->type)
			{
				case 'img':
					$requestDraft['images'][] = "- Location: " . $request->location . 
												"\n- - Size: " . $fileHelper->formatSize($request->size) . 
												"\n- - Finish: " . $request->loadTime . "\n";
				break;
				case 'link':
					$requestDraft['styles'][] = "- Location: " . $request->location . 
												"\n- - Size: " . $fileHelper->formatSize($request->size) . 
												"\n- - Finish: " . $request->loadTime . "\n";	
				break;
				case 'script':
					$requestDraft['scripts'][] = "- Location: " . $request->location . 
												 "\n- - Size: " . $fileHelper->formatSize($request->size) . 
												 "\n- - Finish: " . $request->loadTime . "\n";	
				break;
				case 'document':
				    $requestDraft['documents'][] = "- Location: " . $request->location . 
												   "\n- - Size: " . $fileHelper->formatSize($request->size) . 
												   "\n- - Finish: " . $request->loadTime . "\n";
				break;	
				default:
					$requestDraft['others'][] = "- Location: " . $request->location . 
												"\n- - Size: " . $fileHelper->formatSize($request->size) . 
												"\n- - Finish: " . $request->loadTime . "\n";
				break;
			}
	
			$totalSize = ($totalSize + $request->size);
			$loadTime = (floatval($loadTime) + floatval($request->loadTime));
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
		$loadTime = (floatval($loadTime) + floatval($this->totalTime));		
		$loadTime = ($loadTime > 1) ? $loadTime . 's' : $loadTime . 'ms';
		
		$requestDraft['loadTime'] = "Total load time: $loadTime";		
		
		//return draft of the requests
		return $requestDraft;    
	}									
}
?>