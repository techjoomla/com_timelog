<?php
/**
 * @package    Com_Timelog
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (C) 2009 - 2018 Techjoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */
// No direct access.
defined('_JEXEC') or die;

jimport('joomla.event.dispatcher');

use Joomla\CMS\Factory;
use Joomla\Utilities\ArrayHelper;
use Joomla\CMS\Table\Table;
use Joomla\CMS\MVC\Model\ItemModel;
/**
 * Timelog model.
 *
 * @since  1.0.0
 */
class TimelogModelActivity extends ItemModel
{
	/**
	 * Method to auto-populate the model state.
	 *
	 * Note. Calling getState in this method will result in recursion.
	 *
	 * @return void
	 *
	 * @since    1.0.0
	 *
	 */
	protected function populateState()
	{
		$app  = Factory::getApplication();
		$user = Factory::getUser();

		// Check published state
		if ((!$user->authorise('core.edit.state', 'com_timelog')) && (!$user->authorise('core.edit', 'com_timelog')))
		{
			$this->setState('filter.published', 1);
			$this->setState('filter.archived', 2);
		}

		// Load state from the request userState on edit or from the passed variable on default
		if ($app->input->get('layout') == 'edit')
		{
			$id = $app->getUserState('com_timelog.edit.activity.id');
		}
		else
		{
			$id = $app->input->get('id');
			$app->setUserState('com_timelog.edit.activity.id', $id);
		}

		$this->setState('activity.id', $id);

		// Load the parameters.
		$app  = Factory::getApplication('com_timelog');

		$params       = $app->getParams();
		$params_array = $params->toArray();

		if (isset($params_array['item_id']))
		{
			$this->setState('activity.id', $params_array['item_id']);
		}

		$this->setState('params', $params);
	}

	/**
	 * Method to get an object.
	 *
	 * @param   integer  $id  The id of the object to get.
	 *
	 * @return  mixed    Object on success, false on failure.
	 *
	 * @throws Exception
	 */
	public function getItem($id = null)
	{
		if ($this->_item === null)
		{
			$this->_item = false;

			if (empty($id))
			{
				$id = $this->getState('activity.id');
			}

			// Get a level row instance.
			$table = $this->getTable();

			// Attempt to load the row.
			if ($table->load($id))
			{
				// Check published state.
				if ($published = $this->getState('filter.published'))
				{
					if (isset($table->state) && $table->state != $published)
					{
						throw new Exception(JText::_('COM_TIMELOG_ITEM_NOT_LOADED'), 403);
					}
				}

				// Convert the JTable to a clean JObject.
				$properties  = $table->getProperties(1);
				$this->_item = ArrayHelper::toObject($properties, 'JObject');
			}
		}

		if (!empty($this->_item->activity_type_id))
		{
			JModelLegacy::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_timelog/models');
			$activityTypeModel = JModelLegacy::getInstance('Activitytype', 'TimelogModel', array('ignore_request' => true));
			$activityTypes = $activityTypeModel->getItem($this->_item->activity_type_id);
			$this->_item->activity_type_id = $activityTypes->title;
		}

		if (!empty($this->_item->client_id))
		{
			JModelLegacy::addIncludePath(JPATH_SITE . '/components/com_jlike/models');
			$jlikeTodoModel = JModelLegacy::getInstance('Todo', 'JLikeModel', array('ignore_request' => true));
			$jlikeTodo = $jlikeTodoModel->getContent($this->_item->client_id);
			$this->_item->client_id = $jlikeTodo->content_title;
		}

		if (isset($this->_item->created_by))
		{
			$this->_item->created_by_name = Factory::getUser($this->_item->created_by)->name;
		}

		if (isset($this->_item->modified_by))
		{
			$this->_item->modified_by_name = Factory::getUser($this->_item->modified_by)->name;
		}

		return $this->_item;
	}

	/**
	 * Get an instance of Table class
	 *
	 * @param   string  $type    Name of the JTable class to get an instance of.
	 * @param   string  $prefix  Prefix for the table class name. Optional.
	 * @param   array   $config  Array of configuration values for the JTable object. Optional.
	 *
	 * @return  Table|bool Table if success, false on failure.
	 */
	public function getTable($type = 'Activity', $prefix = 'TimelogTable', $config = array())
	{
		Table::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_timelog/tables');

		return Table::getInstance($type, $prefix, $config);
	}
}
