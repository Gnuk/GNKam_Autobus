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
use Gnkam\Base\Formalizer as BaseFormalizer;
use DateTime;

/**
 * Formalizer class
 * @author Anthony
 * @since 05/10/2013
 */
class Formalizer extends BaseFormalizer
{

	/**
	* Call service for Schedules
	* @param integer $id Line id
	* @param integer $sens 1 or 2
	* @param DateTime $date Schedules date
	* @return array Schedules data
	*/
	public function serviceSchedules($id, $sens = 1, $date = null)
	{
		# Initialize date to today
		if(!$date instanceof DateTime)
		{
			$date = new DateTime('now');
		}
		
		# Check if empty
		$id = intval($id);
		$sens = intval($sens);
		if(empty($id) OR empty($sens))
		{
			return null;
		}
		
		return $this->service(
			'schedules',
			$id . '-' . $sens . '-' . $date->format('Ymd'),
			array(
				$id,
				$sens,
				$date
			)
		);
	}
	
	/**
	* Data recuperation for menu
	* @param integer $id Line id
	* @param integer $sens 1 or 2
	* @param DateTime $date Schedule date
	* @return array Schedule in array representation
	*/
	protected function schedulesData($id, $sens, $date)
	{
		$receiver = new ScheduleReceiver();
		return $receiver->getArrayData($id, $sens, $date);
	}
}
