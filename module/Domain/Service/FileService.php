<?php
namespace Domain\Service;

require_once('autoload_classmap.php');
use Domain\Helper\FileHelper;

class FileService implements ServiceInterface
{	
	/**
	 * @var Integer
	 */
	private $totalTime;
	 
	/**
	 * @var Integer
	 */
	private $size;	 
	
	public function __construct($data = null)
	{
		if ((isset($data)) && (!empty($data)))
		{
			$this->totalTime = $data->totalTime;
			$this->size = $data->size;
		}
	}
	
	/** 
	 * @return String
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
			$fileHelper = new FileHelper();
			$fileSize = $fileHelper->formatSize($this->size);
			
			return $fileSize;
		}	
		return $this->size;									
	}	
}
?>