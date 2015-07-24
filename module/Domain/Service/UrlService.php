<?php
namespace Domain\Service;

require_once('autoload_classmap.php');

use Domain\Factory\Service\ContentTypeServiceFactory;

class UrlService 
{
    /**
	 * @var array 
	 */
	private $fileContentTypes = [
									'image',
									'application',
									'video',
									'audio',
									'text',
								];
	/**
	 * @var String 
	 */								
	private $url;

	/**
	 * @var String 
	 */	
	private $data;
	
	/**
	 * @var String 
	 */	
	private $header;
	
	/**
	 * @var String 
	 */	
	private $body;
	
	/**
	 * @var String 
	 */	
	private $contentType;
	
	/**
	 * @var String
	 */
	private $contentFileType;
	
	/**
	 * @var Integer
	 */
	private $totalTime;
	
	/**
	 * @var Integer
	 */
	private $size;
	
		
	/**
	 * @param String
	 */
	public function __construct($url = null)
	{	
		//checking if isset and not empty, and that it matches an http/https or ftp link.
		if (
			(isset($url)) && 
			(!empty($url))
		   )
		{
			$this->url = (string) trim(
					                    strip_tags(
					                        str_replace(" ", '+', $url)
					                    )
						              );			
	    }
	}	
	
	/** 
	 * @return String
	 */		
	public function getUrl() 
	{
		return $this->url;
	}
	
	/** 
	 * @return String
	 */		
	public function getHeader()
	{
		return $this->header;
	}	
	
	/** 
	 * @return String
	 */		
	public function getData()
	{
		return $this->data;
	}
	
	/** 
	 * @return String
	 */		
	public function getContentFileType()
	{
		return $this->contentFileType;
	}
	
	/** 
	 * @return String
	 */		
	public function getContentType()
	{
		return $this->contentType;
	}
	
	/** 
	 * @return Service
	 */				
	public function getContentTypeService()
	{
		//getting content type
		$contentType = $this->getContentType();
		$contentFileType = $this->getContentFileType();
		
		//checking if the content type is of a file type
		if ($contentType == 'file')
		{
			$fileData = new \stdClass();
			$fileData->totalTime = $this->totalTime;			
			$fileData->size = $this->size; 
			
			//getting the service for the content type and returning the service
			$contentTypeServiceFactory = new ContentTypeServiceFactory();
			$contentTypeService = $contentTypeServiceFactory->create('file', $fileData);				
						
			return $contentTypeService;
		} 
		else if (($contentType == 'website') && ($this->getContentFileType() == 'html'))
		{
			//object with details needed for the website content type
			$websiteData = new \stdClass();
			
			$websiteData->url = $this->getUrl();
			$websiteData->totalTime = $this->totalTime;
			$websiteData->size = $this->size;			 				
			$websiteData->body = $this->body;
									
			//getting the service for the content type and returning the service
			$contentTypeServiceFactory = new ContentTypeServiceFactory();
			$contentTypeService = $contentTypeServiceFactory->create('website', $websiteData);
							
			return $contentTypeService;
		}
	}				
			
	/**
	 * cURL Initialization
	 * @return Boolean
	 */
	public function curlInitialize()
	{
		 $curlInitialization = curl_init($this->getUrl());
		 
		 curl_setopt($curlInitialization, CURLOPT_CUSTOMREQUEST, 'GET');
		 curl_setopt($curlInitialization, CURLOPT_HEADER, 1);
		 curl_setopt($curlInitialization, CURLOPT_RETURNTRANSFER, 1);
		 curl_setopt($curlInitialization, CURLOPT_FOLLOWLOCATION, TRUE);	
		 	
		 //SSL
		 curl_setopt($curlInitialization, CURLOPT_SSL_VERIFYPEER, FALSE);	
		 
		 //setting the data received to the data property
		 $this->data = curl_exec($curlInitialization);
		 
		 		 
		 //checking for errors
		 if (!curl_error($curlInitialization))
		 {
			 /**
			  * Getting the data set before error checking
			  */
			 $data = $this->getData();
			 
			 /**
			  * Setting settings needed
			  */			 		
			 $headerSize = curl_getinfo($curlInitialization, CURLINFO_HEADER_SIZE);
			 
			 $this->header = substr($data, 0, $headerSize);
			 $this->body = substr($data, $headerSize);	
			 $this->totalTime = curl_getinfo($curlInitialization, CURLINFO_TOTAL_TIME);	
			 $this->size = curl_getinfo($curlInitialization, CURLINFO_SIZE_DOWNLOAD);			 
			 
			 /** 
			  * Closing cURL
			  */
			 curl_close($curlInitialization);		 		 		
			 
			 /**
			  * Setting content type, and checking the return
			  */
			 if ($this->setContentType())
			 {
				 return true;
			 } else
			 {
				 return false;
			 }
			 			 
		 } else
		 {
			 return false;
		 }
		 
	}		
	
	private function setContentType()
	{
		 /**
		  * Setting content type
		  */
 		 if (preg_match('/Content-Type: ([A-Z]+)\/([\dA-Z.\-]+)/i', $this->getHeader(), $contentTypeMatches)) 	     
		 {	
			 if ((in_array($contentTypeMatches[1], $this->fileContentTypes)) && ($contentTypeMatches[2] != 'html'))
			 {
				 $this->contentType = 'file';
			 } else if ((in_array($contentTypeMatches[1], $this->fileContentTypes)) && ($contentTypeMatches[2] == 'html'))
			 {
				 $this->contentType = 'website';
			 }		  	
			
			// @todo implement further validation on types, gifs, zips, html etc.
			$this->contentFileType = $contentTypeMatches[2];
			
			return true;
		 } else
		 {
			return false;
		 }		
	}
	
}
?>