<?php
/**
 * @package    Com_Timelog
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (C) 2009 - 2020 Techjoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access to this file
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\MVC\Model\ListModel;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;

/**
 * Methods supporting a list of Timelog Activities records.
 *
 * @since  __DEPLOY_VERSION__
 */
class TimelogModelActivities extends ListModel
{
/**
	* Constructor.
	*
	* @param   array  $config  An optional associative array of configuration settings.
	*
	* @see         \Joomla\CMS\MVC\Model\BaseDatabaseModel
	* @since      __DEPLOY_VERSION__
	*/
	public function __construct($config = array())
	{
		if (empty($config['filter_fields']))
		{
			$config['filter_fields'] = array(
				'id', 'a.id',
				'activity_type_id', 'a.activity_type_id',
				'client', 'a.client',
				'client_id', 'a.client_id',
				'activity_note', 'a.activity_note',
				'created_date', 'a.created_date',
				'spent_time', 'a.spent_time',
				'state', 'a.state',
				'attachment', 'a.attachment',
				'created_by', 'a.created_by',
				'modified_by', 'a.modified_by','todo.title'
			);
		}

		parent::__construct($config);
	}

	/**
	 * Build an SQL query to load the list data.
	 *
	 * @return   \JDatabaseQuery
	 *
	 * @since    __DEPLOY_VERSION__
	 */
	protected function getListQuery()
	{
		// Create a new query object.
		$db = $this->getDbo();
		$query = $db->getQuery(true);

		// Select the required fields from the table.
		$query->select(
			$this->getState(
				'list.select', 'DISTINCT a.*'
			)
		);

		// Get Logtime in hours and minute format

		/*
		$query->select("CONCAT((Floor(FORMAT(SUBSTRING_INDEX(spent_time,'.',-1) / 60, '2')) + FLOOR(spent_time) ), 'hr ',
		Floor(SUBSTRING_INDEX(FORMAT(SUBSTRING_INDEX(spent_time,'.',-1) / 60, '2') ,'.',-1) * 60 /100), 'min') AS spent_time");

		$query->select("SEC_TO_TIME(SUM(time_to_sec(spent_time))) AS spent_time");
		*/

		// Below query shows "20hr 50min" format for "20:50:00" timelog value
		$query->select('TIME_FORMAT(timelog, "%Hhr %imin") AS spent_time');
		$query->from($db->qn('#__timelog_activities', 'a'));

		// Join over the user field 'created_by'
		$query->select('`created_by`.name AS `created_by`');
		$query->join('LEFT', $db->qn('#__users', 'created_by')
		. ' ON (' . $db->qn('created_by.id') . ' = ' . $db->qn('a.created_by') . ')');

		// Join over the user field 'modified_by'
		$query->select('`modified_by`.name AS `modified_by`');
		$query->join('LEFT', $db->qn('#__users', 'modified_by')
		. ' ON (' . $db->qn('modified_by.id') . ' = ' . $db->qn('a.modified_by') . ')');

		// Join over the activity_type field 'activity_type_id'
		$query->select('activity_type.`title`');
		$query->join('LEFT', $db->qn('#__timelog_activity_type', 'activity_type')
		. ' ON (' . $db->qn('activity_type.id') . ' = ' . $db->qn('a.activity_type_id') . ')');
		
		// Filter by published state
		$published = $this->getState('filter.state');

		if (is_numeric($published))
		{
			$query->where('a.state = ' . (int) $published);
		}
		elseif ($published === '')
		{
			$query->where('a.state = 1');
		}

		// Filter by client_id
		$clientId = $this->getState('filter.client_id');

		if (!empty($clientId))
		{
			$query->where('a.client_id = ' . (int) $clientId);
		}

		// Filter by search in title
		$search = $this->getState('filter.search');

		if (!empty($search))
		{
			if (stripos($search, 'id:') === 0)
			{
				$query->where('a.id = ' . (int) substr($search, 3));
			}
			else
			{
				$search = $db->Quote('%' . $db->escape($search, true) . '%');
				$query->where(
				'( activity_type.title LIKE ' . $search .
				' OR  a.client LIKE ' . $search .
				' OR  a.activity_note LIKE ' . $search .
				' OR  created_by.name LIKE ' . $search .
				' OR  modified_by.name LIKE ' . $search .
				')');
			}
		}

		// Filtering by activity type
		$activityType = $this->getState('filter.activity_type');

		if (!empty($activityType))
		{
			$query->where($db->qn('a.activity_type_id') . ' = ' . (int) $activityType);
		}

		// Add the list ordering clause.
		$orderCol  = $this->state->get('list.ordering', "a.id");
		$orderDirn = $this->state->get('list.direction', "DESC");

		if ($orderCol && $orderDirn)
		{
			$query->order($db->escape($orderCol . ' ' . $orderDirn));
		}

		return $query;
	}

	/**
	 * Method to get a list of courses.
	 *
	 * @return  mixed  An array of data items on success, false on failure.
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public function getItems()
	{
		$items = parent::getItems();
		$filter = JFilterInput::getInstance();

		if (!empty($items))
		{
			// Include media library models
			BaseDatabaseModel::addIncludePath(JPATH_SITE . '/libraries/techjoomla/media/models');

			foreach ($items as $item)
			{
				// Create TJMediaXref class object
				$modelMediaXref = BaseDatabaseModel::getInstance('Xref', 'TJMediaModel', array('ignore_request' => true));
				$modelMediaXref->setState('filter.clientId', $item->id);
				$modelMediaXref->setState('filter.client', 'com_timelog.activity');

				$mediaData = $modelMediaXref->getItems();

				if (!empty($mediaData))
				{
					$item->mediaFiles = $mediaData;
				}
			}
		}

		return $items;
	}
}
