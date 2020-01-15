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
use Joomla\CMS\Router\Route;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\MVC\View\HtmlView;

/**
 * View class for a list of Timelog.
 *
 * @since  1.0.0
 */
class TimelogViewActivities extends HtmlView
{
	/**
	 * An array of items
	 *
	 * @var  array
	 */
	protected $items;

	/**
	 * The pagination object
	 *
	 * @var  Pagination
	 */
	protected $pagination;

	/**
	 * The model state
	 *
	 * @var  object
	 */
	protected $state;

	/**
	 * Form object for search filters
	 *
	 * @var  Form
	 */
	public $filterForm;

	/**
	 * Logged in User
	 *
	 * @var  Object
	 */
	protected $user;

	/**
	 * The active search filters
	 *
	 * @var  array
	 */
	public $activeFilters;

	/**
	 * Params
	 *
	 * @var  object|array
	 */
	protected $params;

	/**
	 * @var  boolean
	 *
	 * @since __DEPLOY_VERSION__
	 */
	protected $canCreate;

	/**
	 * @var  boolean
	 *
	 * @since __DEPLOY_VERSION__
	 */
	protected $canEdit;

	/**
	 * @var  boolean
	 *
	 * @since __DEPLOY_VERSION__
	 */
	protected $canEditOwn;

	/**
	 * @var  boolean
	 *
	 * @since __DEPLOY_VERSION__
	 */
	protected $canCheckin;

	/**
	 * @var  boolean
	 *
	 * @since __DEPLOY_VERSION__
	 */
	protected $canChange;

	/**
	 * @var  boolean
	 *
	 * @since __DEPLOY_VERSION__
	 */
	protected $canDelete;

	/**
	 * @var  boolean
	 *
	 * @since __DEPLOY_VERSION__
	 */
	protected $canManageLogs;

	/**
	 * @var  boolean
	 *
	 * @since __DEPLOY_VERSION__
	 */
	protected $canManageOwnLogs;

	/**
	 * Display the view
	 *
	 * @param   string  $tpl  Template name
	 *
	 * @return void
	 *
	 * @throws Exception
	 */
	public function display($tpl = null)
	{
		$app = Factory::getApplication();

		$this->user = Factory::getUser();

		// Get ACL actions
		$currentUserSuperUser   = $this->user->authorise('core.admin');
		$this->canManageLogs    = $this->user->authorise('core.manage.logs', 'com_timelog');
		$this->canManageOwnLogs = $this->user->authorise('core.own.manage.logs', 'com_timelog');
		$this->canViewLogs      = $this->user->authorise('core.view.logs', 'com_timelog');

		// Validate user login.
		if (empty($this->user->id))
		{
			$return = base64_encode((string) Uri::getInstance());
			$login_url_with_return = Route::_('index.php?option=com_users&return=' . $return);
			$app->enqueueMessage(Text::_('JERROR_ALERTNOAUTHOR'), 'notice');
			$app->redirect($login_url_with_return, 403);
		}

		if (!$currentUserSuperUser)
		{
			if (!$this->canManageLogs && !$this->canManageOwnLogs && !$this->canViewLogs)
			{
				$app->enqueueMessage(Text::_('JERROR_ALERTNOAUTHOR'), 'error');
				$app->setHeader('status', 403, true);

				return;
			}
		}

		$this->state = $this->get('State');
		$this->items = $this->get('Items');
		$this->pagination = $this->get('Pagination');

		$this->filterForm         = $this->get('FilterForm');
		$this->activeFilters      = $this->get('ActiveFilters');

		$this->params = $app->getParams('com_timelog');

		// Check for errors.
		if (count($errors = $this->get('Errors')))
		{
			throw new Exception(implode("\n", $errors));
		}

		// Get ACL actions
		$this->canCreate  = $this->user->authorise('core.create', 'com_timelog');
		$this->canEdit    = $this->user->authorise('core.edit', 'com_timelog');
		$this->canEditOwn = $this->user->authorise('core.edit.own', 'com_timelog');
		$this->canCheckin = $this->user->authorise('core.manage', 'com_timelog');
		$this->canChange  = $this->user->authorise('core.edit.state', 'com_timelog');
		$this->canDelete  = $this->user->authorise('core.delete', 'com_timelog');

		parent::display($tpl);
	}
}
