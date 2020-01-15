<?php
/**
 * @package    Com_Timelog
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (C) 2009 - 2018 Techjoomla. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */
// No direct access
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView;

/**
 * View to edit
 *
 * @since  1.0.0
 */
class TimelogViewActivityform extends HtmlView
{
	/**
	 * The JForm object
	 *
	 * @var  Form
	 */
	protected $form;

	/**
	 * The active item
	 *
	 * @var  object
	 */
	protected $item;

	/**
	 * The model state
	 *
	 * @var  object
	 */
	protected $state;

	/**
	 * The model state
	 *
	 * @var  object|array
	 */
	protected $params;

	/**
	 * @var  boolean
	 *
	 * @since __DEPLOY_VERSION__
	 */
	protected $canSave;

	/**
	 * The user object
	 *
	 * @var  \JUser|null
	 */
	protected $user;

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
		$app  = Factory::getApplication();
		$this->user = Factory::getUser();

		$this->state   = $this->get('State');
		$this->item    = $this->get('Item');
		$this->params  = $app->getParams('com_timelog');
		$this->canSave = $this->get('CanSave');
		$this->form		= $this->get('Form');

		// Get ACL actions
		$currentUserSuperUser = $this->user->authorise('core.admin');
		$this->canManageLogs      = $this->user->authorise('core.manage.logs', 'com_timelog');
		$this->canManageOwnLogs   = $this->user->authorise('core.own.manage.logs', 'com_timelog');

		$this->canEdit    = $this->user->authorise('core.edit', 'com_timelog');
		$this->canEditOwn = $this->user->authorise('core.edit.own', 'com_timelog');

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
			if (!$this->canManageLogs && !$this->canManageOwnLogs)
			{
				$app->enqueueMessage(Text::_('JERROR_ALERTNOAUTHOR'), 'error');
				$app->setHeader('status', 403, true);

				return;
			}
		}

		// Check for errors.
		if (count($errors = $this->get('Errors')))
		{
			throw new Exception(implode("\n", $errors));
		}

		$this->_prepareDocument();

		parent::display($tpl);
	}

	/**
	 * Prepares the document
	 *
	 * @return void
	 *
	 * @throws Exception
	 */
	protected function _prepareDocument()
	{
		$app   = Factory::getApplication();
		$menus = $app->getMenu();
		$title = null;

		// Because the application sets a default page title,
		// we need to get it from the menu item itself
		$menu = $menus->getActive();

		if ($menu)
		{
			$this->params->def('page_heading', $this->params->get('page_title', $menu->title));
		}
		else
		{
			$this->params->def('page_heading', Text::_('COM_TIMELOG_DEFAULT_PAGE_TITLE'));
		}

		$title = $this->params->get('page_title', '');

		if (empty($title))
		{
			$title = $app->get('sitename');
		}
		elseif ($app->get('sitename_pagetitles', 0) == 1)
		{
			$title = Text::sprintf('JPAGETITLE', $app->get('sitename'), $title);
		}
		elseif ($app->get('sitename_pagetitles', 0) == 2)
		{
			$title = Text::sprintf('JPAGETITLE', $title, $app->get('sitename'));
		}

		$this->document->setTitle($title);

		if ($this->params->get('menu-meta_description'))
		{
			$this->document->setDescription($this->params->get('menu-meta_description'));
		}

		if ($this->params->get('menu-meta_keywords'))
		{
			$this->document->setMetadata('keywords', $this->params->get('menu-meta_keywords'));
		}

		if ($this->params->get('robots'))
		{
			$this->document->setMetadata('robots', $this->params->get('robots'));
		}
	}
}
