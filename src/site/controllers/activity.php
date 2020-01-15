<?php
/**
 * @package    Com_Timelog
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (C) 2009 - 2020 Techjoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */
// No direct access
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Router\Route;

JLoader::import("/techjoomla/media/storage/local", JPATH_LIBRARIES);
JLoader::import("/techjoomla/media/xref", JPATH_LIBRARIES);

/**
 * Activity controller class.
 *
 * @since  __DEPLOY_VERSION__
 */
class TimelogControllerActivity extends BaseController
{
	/**
	 * Downloads the file requested by user
	 *
	 * @return  boolean|void
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	public function downloadAttachment()
	{
		// CSRF token check
		Session::checkToken('get') or jexit(Text::_('JINVALID_TOKEN'));

		$user = Factory::getUser();
		$app  = Factory::getApplication();

		// Validate user login.
		if (empty($user->id))
		{
			$return = base64_encode((string) Uri::getInstance());
			$login_url_with_return = Route::_('index.php?option=com_users&return=' . $return);
			$app->enqueueMessage(Text::_('JERROR_ALERTNOAUTHOR'), 'notice');
			$app->redirect($login_url_with_return, 403);
		}

		$canView = $user->authorise('core.view.logs', 'com_timelog');

		if (!$canView)
		{
			$app->enqueueMessage(Text::_('JERROR_ALERTNOAUTHOR'), 'error');
			$app->setHeader('status', 403, true);

			return;
		}

		$clientId = $app->input->get('activityId', '', 'INT');
		$mediaId  = $app->input->get('mediaId', '', 'INT');

		$params = ComponentHelper::getParams('com_timelog');

		if (!$mediaId && !$clientId)
		{
			return false;
		}

		$config              = array();
		$config['mediaId']   = $mediaId;

		// Assign client id as Campaign Id or Report Id or Giveback Id
		$config['client_id'] = $clientId;
		$config['client']    = 'com_timelog.activity';
		$mediaAttachmentData = TJMediaXref::getInstance($config);

		$folderName          = explode('.', $mediaAttachmentData->media->type);
		$downloadPath        = JPATH_SITE . '/' . $params->get('file_path', 'media/com_timelog/uploads');

		// Making File Download path For e.g /file mime type + 's'/text.pdf Here mime type like application + s this is folder name
		$downloadPath        = $downloadPath . '/' . $folderName[0] . 's' . '/' . $mediaAttachmentData->media->source;

		$media               = TJMediaStorageLocal::getInstance();
		$media->downloadMedia($downloadPath);
	}

	/**
	 * Function to delete the timelog activity attachment
	 *
	 * @return  void
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	public function deleteAttachment()
	{
		// Prevent CSRF attack
		Session::checkToken('get') or jexit(Text::_('JINVALID_TOKEN'));

		// Get the current user id
		$user = Factory::getuser();
		$app  = Factory::getApplication();

		if (!$user->id)
		{
			return false;
		}

		$canDelete = $user->authorise('core.delete', 'com_timelog');

		if (!$canDelete)
		{
			$app->enqueueMessage(Text::_('JERROR_ALERTNOAUTHOR'), 'error');
			$app->setHeader('status', 403, true);

			return;
		}

		$params = ComponentHelper::getParams('com_timelog');
		$filePath = $params->get('file_path', 'media/com_timelog/uploads');
		$clientId = $app->input->get('activityId', '', 'INT');
		$mediaId  = $app->input->get('mediaId', '', 'INT');

		$params = ComponentHelper::getParams('com_timelog');

		if (!$mediaId && !$clientId)
		{
			return false;
		}

		$model  = $this->getModel('ActivityForm', 'TimelogModel');
		$return = $model->deleteMedia($mediaId, $filePath, 'com_timelog.activity', $clientId);

		$result = array();
		$result['success'] = true;
		$result['message'] = JText::_('COM_TIMELOG_ATTACHMENT_DELETED_SUCCESSFULLY');

		if ($return == false)
		{
			$result['success'] = false;
			$result['message'] = JText::_('COM_TIMELOG_ATTACHMENT_DELETED_FAILED');
		}

		echo json_encode($result);
		jexit();
	}
}
