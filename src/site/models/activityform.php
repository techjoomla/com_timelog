<?php
/**
 * @package    Com_Timelog
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (C) 2009 - 2020 Techjoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access.
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\Registry\Registry;
use Joomla\Utilities\ArrayHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Table\Table;

JLoader::import('components.com_timelog.models.activity', JPATH_ADMINISTRATOR);

/**
 * TimelogModelActivityForm model.
 *
 * @since  1.0.0
 */
class TimelogModelActivityForm extends TimelogModelActivity
{
	public $item = null;

	/**
	 * Method to auto-populate the model state.
	 *
	 * Note. Calling getState in this method will result in recursion.
	 *
	 * @return void
	 *
	 * @since  1.0.0
	 */
	protected function populateState()
	{
		$app = Factory::getApplication();

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
		$params       = $app->getParams();
		$params_array = $params->toArray();

		if (isset($params_array['item_id']))
		{
				$this->setState('activity.id', $params_array['item_id']);
		}

		$this->setState('params', $params);
	}

	/**
	 * Method to get the data that should be injected in the form.
	 *
	 * @return	mixed	$data  The data for the form.
	 *
	 * @since	1.0.0
	 */
	protected function loadFormData()
	{
		$data = $this->getItem();

		$explodeTime = explode(':', $data->timelog);

		if (!empty($data->id))
		{
			// Include media library models
			BaseDatabaseModel::addIncludePath(JPATH_SITE . '/libraries/techjoomla/media/models');

			// Create TJMediaXref class object
			$modelMediaXref = BaseDatabaseModel::getInstance('Xref', 'TJMediaModel', array('ignore_request' => true));
			$modelMediaXref->setState('filter.clientId', $data->id);
			$modelMediaXref->setState('filter.client', 'com_timelog.activity');
			$mediaData = $modelMediaXref->getItems();

			$attachmentArray = [];

			for ($i = 0; $i < count($mediaData); $i++)
			{
				$attachmentArray['attachment' . $i] = [
					'title' => $mediaData[$i]->title,
					'media_id' => $mediaData[$i]->id
				];
			}

			$data->oldAttachment = $attachmentArray;
		}

		$data->hours = $explodeTime[0];
		$data->min = $explodeTime[1];

		return $data;
	}

	/**
	 * Method to get an ojbect.
	 *
	 * @param   integer  $id  The id of the object to get.
	 *
	 * @return   Object|boolean  Object  on success, false on failure.
	 *
	 * @throws Exception
	 */
	public function getItem($id = null)
	{
		if ($this->item === null)
		{
			$this->item = false;

			if (empty($id))
			{
				$id = $this->getState('activity.id');
			}

			// Get a level row instance.
			$table = $this->getTable();

			if ($table !== false && $table->load($id))
			{
				$user = Factory::getUser();
				$id   = $table->id;

				$canEdit = $user->authorise('core.edit', 'com_timelog') || $user->authorise('core.create', 'com_timelog');

				if (!$canEdit && $user->authorise('core.edit.own', 'com_timelog'))
				{
					$canEdit = $user->id == $table->created_by;
				}

				if (!$canEdit)
				{
					throw new Exception(Text::_('JERROR_ALERTNOAUTHOR'), 403);
				}

				// Check published state.
				if ($published = $this->getState('filter.published'))
				{
					if (isset($table->state) && $table->state != $published)
					{
						return $this->item;
					}
				}

				// Convert the JTable to a clean JObject.
				$properties = $table->getProperties(1);
				$this->item = ArrayHelper::toObject($properties, 'JObject');
			}
		}

		return $this->item;
	}

	/**
	 * Method to delete data
	 *
	 * @param   int  &$pk  Item primary key
	 *
	 * @return  int  The id of the deleted item
	 *
	 * @throws Exception
	 *
	 * @since 1.0.0
	 */
	public function delete(&$pk)
	{
		$user = Factory::getUser();

		if (empty($pk))
		{
			$pk = (int) $this->getState('activity.id');
		}

		if ($pk == 0 || $this->getItem($pk) == null)
		{
			throw new Exception(Text::_('COM_TIMELOG_ITEM_DOESNT_EXIST'), 404);
		}

		if ($user->authorise('core.delete', 'com_timelog') !== true)
		{
			throw new Exception(Text::_('JERROR_ALERTNOAUTHOR'), 403);
		}

		$timelog = TimelogActivity::getInstance($id);

		if ($timelog->delete($pk) !== true)
		{
			throw new Exception(Text::_('JERROR_FAILED'), 501);
		}

		// Include media library models
		BaseDatabaseModel::addIncludePath(JPATH_SITE . '/libraries/techjoomla/media/models');

		// Create TJMediaXref class object
		$modelMediaXref = BaseDatabaseModel::getInstance('Xref', 'TJMediaModel', array('ignore_request' => true));
		$modelMediaXref->setState('filter.clientId', $pk);
		$modelMediaXref->setState('filter.client', 'com_timelog.activity');

		$mediaData = $modelMediaXref->getItems();
		$params = ComponentHelper::getParams('com_timelog');
		$filePath = $params->get('file_path', 'media/com_timelog/uploads');

		if (!empty($mediaData))
		{
			foreach ($mediaData as $media)
			{
				$this->deleteMedia($media->media_id, $filePath, 'com_timelog.activity', $pk);
			}
		}

		return $pk;
	}

	/**
	 * Check if data can be saved
	 *
	 * @return bool
	 */
	public function getCanSave()
	{
		$table = $this->getTable();

		return $table !== false;
	}

	/**
	 * Method to upload file for timelog activity
	 *
	 * @param   Array  $file  File field array
	 *
	 * @param   array  $data  The form data
	 *
	 * @return array
	 *
	 * @since __DEPLOY_VERSION__
	 */
	public function uploadMedia($file, $data)
	{
		$user = Factory::getUser();
		$canEdit    = $user->authorise('core.edit', 'com_timelog');
		$canEditOwn = $user->authorise('core.edit.own', 'com_timelog');

		$uploadedMediaIds = $errorFiles = array();
		$params = ComponentHelper::getParams('com_timelog');

		if (!empty($file['attachment']))
		{
			$filePath = $params->get('file_path', 'media/com_timelog/uploads');
			$uploadedFileExtension = strtolower($params->get('upload_extensions', 'image/png,image/jpg,image/jpeg', 'STRING'));
			$fileExtensionType     = explode(',', $uploadedFileExtension);

			$config               = array();

			if (!empty($fileExtensionType))
			{
				$config['type']       = $fileExtensionType;
			}

			$config['size']       = $params->get('upload_maxsize', '10');

			if ($canEdit || $canEditOwn)
			{
				$config['auth']   = true;
			}

			foreach ($file['attachment'] as $key => $attachments)
			{
				if (!empty($attachments['media_file']['name']))
				{
					$fileType   = explode("/", $attachments['media_file']['type']);
					$config['title']      = $attachments['media_file']['name'];
					$config['uploadPath'] = JPATH_SITE . '/' . $filePath . '/' . strtolower($fileType[0] . 's');

					$media     = TJMediaStorageLocal::getInstance($config);
					$mediaData = $media->upload(array($attachments['media_file']));

					if (!empty($media->getError()))
					{
						$errorFiles[] = $media->getError() . ' (' . $attachments['media_file']['name'] . ')';
					}
					elseif ($mediaData[0]['id'])
					{
						$uploadedMediaIds[$key] = $mediaData[0]['id'];

						if (!empty($data['old_media_ids']))
						{
							if ($data['old_media_ids'][$key] != $mediaId)
							{
								$this->deleteMedia($data['old_media_ids'][$key], $filePath, 'com_timelog.activity', $data['id']);
							}
						}
					}
				}
			}

			// Check error exist in file
			if (!empty($errorFiles))
			{
				$this->setError($errorFiles);
			}
		}

		return $uploadedMediaIds;
	}

	/**
	 * Method to delete media record
	 *
	 * @param   Integer  $mediaId     media Id of files table
	 * @param   STRING   $deletePath  file path from params in config
	 * @param   STRING   $client      client(example -'com_timelog.activity')
	 * @param   Integer  $clientId    clientId(example - Timelog activity id)
	 *
	 * @return	boolean  True if successful, false if an error occurs.
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public function deleteMedia($mediaId, $deletePath, $client, $clientId)
	{
		JLoader::import("/techjoomla/media/tables/xref", JPATH_LIBRARIES);
		JLoader::import("/techjoomla/media/tables/files", JPATH_LIBRARIES);
		$tableXref = Table::getInstance('Xref', 'TJMediaTable');
		$filetable = Table::getInstance('Files', 'TJMediaTable');

		// CheckMediaDataExist will return 1 when media is present clientId is Report Id
		$checkMediaDataExist = $tableXref->load(array('media_id' => $mediaId, 'client_id' => $clientId));

		// Making file delete path
		$mediaPresent = $filetable->load($mediaId);

		$mediaType    = explode(".", $filetable->type);

		// If Media is present
		if ($checkMediaDataExist)
		{
			// Get Object which include Media xref + Media File data of provided Media xref id
			$mediaXrefLib = TJMediaXref::getInstance(array('id' => $tableXref->id));

			// If media is not deleted it will return false here
			if ($mediaXrefLib->delete())
			{
				// If media xref delete then delete main entry from media_files

				$mediaLib = TJMediaStorageLocal::getInstance(array('id' => $mediaId, 'uploadPath' => $deletePath));

				// Checking Media is present or not
				if ($mediaLib->id)
				{
					// If Media is not deleted
					if (!$mediaLib->delete())
					{
						return false;
					}
				}

				return true;
			}
			else
			{
				return false;
			}
		}
		elseif ($mediaPresent)
		{
			$mediaLib = TJMediaStorageLocal::getInstance(array('id' => $mediaId, 'uploadPath' => $deletePath));

			if ($mediaLib->id)
			{
				if ($mediaLib->delete())
				{
					return true;
				}
				else
				{
					return false;
				}
			}
		}

		return true;
	}
}
