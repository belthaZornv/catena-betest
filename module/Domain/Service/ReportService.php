<?php
namespace Domain\Service;

class ReportService 
{
	/**
	 * @return Boolean
	 */
	public function generateReport($data, $reportName = "My Report")
	{
		try
		{
			if ((isset($data)) && (!empty($data)))
			{
				//getting data needed before unsetting
				$totalSize = $data['totalSize'];
				$loadTime = $data['loadTime'];
				
				//unsetting unneeded indexes
				unset($data['totalSize']);
				unset($data['loadTime']);
				unset($data['requests']);			
				
				//getting total requests
				$totalRequests = (
									count($data['images']) + 
									count($data['styles']) + 
									count($data['scripts']) + 
									count($data['documents']) + 
									count($data['others'])
								 );
						
				$html =	'<!DOCTYPE html>';
				$html .= '<html lang="en">';
				
				$html .= '<head>';
				$html .= '<meta charset="UTF-8">';
				$html .= '<title>BE Test - Auto Generated Reports</title>';
				$html .= '<link rel="stylesheet" href="../public/css/style.css">';
				$html .= '</head>';				
				
				$html .= '<body>';
				
				$html .= '<div class="title">';	
				$html .= "<span>$reportName</span>";
				$html .= '</div>';						
	
				$html .= '<div class="details">';
				$html .= '<p>Main Details:</p>';
							
				$html .= "<p>$loadTime</p>";
				$html .= "<p>$totalSize</p>";
				$html .= "<p>Total requests: $totalRequests</p>";						
				$html .= '</div>';								
				
				$html .= '<div class="statistics">';								
				$html .= '<table>';
				
				$html .= '<thead>';
				
				$html .= '<tr>';
				$html .= '<th>Location</th>';
				$html .= '<th>Type</th>';
				$html .= '<th>Size</th>';
				$html .= '<th>Load Time</th>';
				$html .= '</tr>';
				
				$html .= '</thead>';						
				
				$html .= '<tbody>';
				
				//Format for report (Loading into table)		
				foreach (array_keys($data) as $key => $type)
				{					
					for ($i = 0; $i < count($data[$type]); $i++)
					{
						//splitting data into the format we need					
						$value = explode('- -', $data[$type][$i]);
						
						//trimming and removing extra data
						$location = trim(str_replace('- Location: ', '', $value[0]));
						$size = trim(str_replace(' Size: ', '', $value[1]));
						$loadTime = trim(str_replace(' Finish: ', '', $value[2]));
						
						//loading into table
						$html .= '<tr>';
						
						$html .= "<td>$location</td>";
						$html .= "<td>$type</td>";
						$html .= "<td>$size</td>";
						$html .= "<td>$loadTime</td>";
						
						$html .= '</tr>';	
					}																	
				}										
							
				 
				$html .= '</tbody>';
				
				$html .= '</table>';	
				$html .= '</div>';
				$html .= '</body>';
				$html .= '</html>';
				
				$reportDirectory = realpath(__DIR__ . '/../../../report');
				$report = fopen($reportDirectory . "/Report - " . date('y-m-d_H-i-s') . ".html", 'w');
				fwrite($report, $html);
				
				echo "\n\nReport generated....";
			}
		}	
		catch (Exception $e) {
    		echo "\n\nError generating report....";
		}
	}	
}
?>