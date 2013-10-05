<?php
/*
* Copyright (c) 2013 GNKW & Kamsoft.fr
*
* This file is part of Gnkam Autobus.
*
* Gnkam Autobus is free software: you can redistribute it and/or modify
* it under the terms of the GNU Affero General Public License as published by
* the Free Software Foundation, either version 3 of the License, or
* (at your option) any later version.
*
* Gnkam Autobus is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
* GNU Affero General Public License for more details.
*
* You should have received a copy of the GNU Affero General Public License
* along with Gnkam Autobus.  If not, see <http://www.gnu.org/licenses/>.
*/
namespace Gnkam\Autobus\Stac;
use Gnkw\Http\Rest\Client;
use Gnkw\Http\Uri;
use DateTime;
use DomDocument;

/**
 * ScheduleReceiver class
 * @author Anthony
 * @since 05/10/2013
 */
class ScheduleReceiver
{
	private $client;
	private $date;

	/**
	 * ScheduleReceiver constructor
	 */
	public function __construct()
	{
		$this->client = new Client('http://www.bus-stac.fr');
	}
	
	/**
	* Get array for an id
	* @param integer $id Line id
	* @param integer $sens 1 or 2
	* @param DateTime $date Schedule date
	* @return array Schedule in array representation
	*/
	public function getArrayData($id, $sens = 1, $date)
	{
		$this->date = $date;
		
		$this->uri =  new Uri('/horaires_ligne/index.asp');
		$this->uri->addParam('rub_code', 6);
		$this->uri->addParam('thm_id', 2);
		$this->uri->addParam('gpl_id', 0);
		$this->uri->addParam('lign_id', $id);
		$this->uri->addParam('sens', $sens);
		$this->uri->addParam('date', $this->date->format('d/m/Y'));
		
		return $this->allHours();
	}
	
	/**
	* Get all hours in an array
	* @return array Schedules line informations
	*/
	private function allHours()
	{
		$index = 1;
		$same = false;
		$hours = array();
		while(!$same)
		{
			# Get the page
			$this->uri->addParam('index', $index);
			$request = $this->client->get($this->uri);
			$resource = $request->getResource();
			if(!$resource->code(200))
			{
				return array('error' => 'fail to call Stac website');
			}
			$page = $resource->getContent();
			
			# Get page array representation
			$array = $this->parser($page);
			if(isset($previousArray))
			{
				# End condition when same page is called
				$compareId = 0;
				do
				{
					$comparePrevious = $previousArray[$compareId]['hours'][0];
					$compareCurrent = $array[$compareId]['hours'][0];
					$compareId++;
				}
				while($comparePrevious === '-');
				
				if($comparePrevious === $compareCurrent)
				{
					$same = true;
				}
				else
				{
					# Add new array informations
					$previousArray = $array;
					foreach($array as $pKey => $place)
					{
						foreach($place['hours'] as $hour){
							$hours[$pKey]['hours'][] = $hour;
						}
					}
				}
			}
			else
			{
				# First save of informations
				$previousArray = $array;
				$hours = $array;
			}
			$index+=6;
		}
		return $hours;
	}
	
	/**
	* Parse a page
	* @param string $page Page to parse
	* @return array Schedule line informations for a page
	*/
	private function parser($page)
	{
		$templateTime = clone $this->date;
		$hours = array();
		$array = array();
		
		# Get only table
		$page =  preg_replace('#(.+)\<tbody\>(.+)\<\/tbody\>(.+)#siU', '<table>$2</table>', $page);
		
		# save to DomDocument
		$dom = new DomDocument();
		libxml_use_internal_errors(true);
		
		# Load and save informations
		$dom->loadHTML($page);
		$raws = $dom->getElementsByTagName('tr');
		foreach($raws as $cKey => $raw)
		{
			if($cKey > 1)
			{
				$cells = $raw->getElementsByTagName('td');
				foreach($cells as $rKey => $cell)
				{
					$hours[$rKey][] = mb_convert_encoding($cell->nodeValue, mb_internal_encoding(), 'ISO-8859-1');
				}
			}
		}
		
		# Informations restructuration
		foreach($hours[0] as $placeKey => $place)
		{
			$size = count($hours);
			$array[$placeKey] = 
			array(
				'place' => $place,
				'hours' => array()
			);
			for($i = 1; $i<$size; $i++)
			{
				$hourValue = $hours[$i][$placeKey];
				$hourValue = ($hourValue == '-') ? null : $hourValue;
				if(null !== $hourValue)
				{
					$cellHour = explode(':', $hourValue);
					$templateTime->setTime(intval($cellHour[0]), intval($cellHour[1]));
					$hourValue = $templateTime->getTimestamp();
				}
				$array[$placeKey]['hours'][] = $hourValue;
			}
		}
		return $array;
	}
}
