<?php
/**
 * HUBzero CMS
 *
 * Copyright 2005-2011 Purdue University. All rights reserved.
 *
 * This file is part of: The HUBzero(R) Platform for Scientific Collaboration
 *
 * The HUBzero(R) Platform for Scientific Collaboration (HUBzero) is free
 * software: you can redistribute it and/or modify it under the terms of
 * the GNU Lesser General Public License as published by the Free Software
 * Foundation, either version 3 of the License, or (at your option) any
 * later version.
 *
 * HUBzero is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * HUBzero is a registered trademark of Purdue University.
 *
 * @package   hubzero-cms
 * @author    Alissa Nedossekina <alisa@purdue.edu>
 * @copyright Copyright 2005-2011 Purdue University. All rights reserved.
 * @license   http://www.gnu.org/licenses/lgpl-3.0.html LGPLv3
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die('Restricted access');

/**
 * Manage projects
 */
class ProjectsControllerProjects extends \Hubzero\Component\AdminController
{
	/**
	 * Executes a task
	 * 
	 * @return     void
	 */
	public function execute()
	{
		// Load the component config
		$config = JComponentHelper::getParams( $this->_option );
		$this->_config = $config;
				
		// Publishing enabled?
		$this->_publishing = 
			is_file(JPATH_ROOT . DS . 'administrator' . DS . 'components'.DS
				.'com_publications' . DS . 'tables' . DS . 'publication.php')
			&& JPluginHelper::isEnabled('projects', 'publications')
			? 1 : 0;
					
		// Enable publication management
		if ($this->_publishing)
		{
			require_once( JPATH_ROOT . DS . 'administrator' . DS . 'components'.DS
				.'com_publications' . DS . 'tables' . DS . 'publication.php');
			require_once( JPATH_ROOT . DS . 'administrator' . DS . 'components'.DS
				.'com_publications' . DS . 'tables' . DS . 'version.php');
			require_once( JPATH_ROOT . DS . 'administrator' . DS . 'components'.DS
				.'com_publications' . DS . 'tables' . DS . 'access.php');
			require_once( JPATH_ROOT . DS . 'administrator' . DS . 'components'.DS
				.'com_publications' . DS . 'tables' . DS . 'audience.level.php');
			require_once( JPATH_ROOT . DS . 'administrator' . DS . 'components'.DS
				.'com_publications' . DS . 'tables' . DS . 'audience.php');
			require_once( JPATH_ROOT . DS . 'administrator' . DS . 'components'.DS
				.'com_publications' . DS . 'tables' . DS . 'author.php');
			require_once( JPATH_ROOT . DS . 'administrator' . DS . 'components'.DS
				.'com_publications' . DS . 'tables' . DS . 'license.php');
			require_once( JPATH_ROOT . DS . 'administrator' . DS . 'components'.DS
				.'com_publications' . DS . 'tables' . DS . 'category.php');
			require_once( JPATH_ROOT . DS . 'administrator' . DS . 'components'.DS
				.'com_publications' . DS . 'tables' . DS . DS.'master.type.php');
			require_once( JPATH_ROOT . DS . 'administrator' . DS . 'components'.DS
				.'com_publications' . DS . 'tables' . DS . DS.'screenshot.php');
			require_once( JPATH_ROOT . DS . 'administrator' . DS . 'components'.DS
				.'com_publications' . DS . 'tables' . DS . DS.'attachment.php');
			require_once( JPATH_ROOT . DS . 'components'.DS
				. 'com_publications' . DS . 'helpers' . DS . 'helper.php');	
		}
		
		$this->_task = strtolower(JRequest::getVar('task', '','request'));
		parent::execute();
	}

	/**
	 * Lists projects
	 * 
	 * @return     void
	 */
	public function displayTask()
	{
		// Instantiate a new view
		$view = new JView( array('name'=>'projects') );
		$view->option 	= $this->_option;
		$view->task 	= $this->_task;
		$view->config 	= $this->_config;
		
		// Get configuration
		$config = JFactory::getConfig();
		$app = JFactory::getApplication();
		
		// Push some styles to the template
		$document = JFactory::getDocument();
		$document->addStyleSheet(DS .'components' . DS . $this->_option . DS . 'assets' . DS . 'css' . DS . 'projects.css');
			
		// Get filters
		$view->filters = array();
		$view->filters['search'] 		= urldecode($app->getUserStateFromRequest($this->_option 
										. '.search', 'search', ''));
		$view->filters['search_field'] 	= urldecode($app->getUserStateFromRequest($this->_option 
										. '.search_field', 'search_field', 'title'));
		$view->filters['sortby']  		= trim($app->getUserStateFromRequest($this->_option 
										. '.sort', 'filter_order', 'id'));
		$view->filters['sortdir'] 		= trim($app->getUserStateFromRequest($this->_option
										. '.sortdir', 'filter_order_Dir', 'DESC'));
		$view->filters['authorized'] 	= true;
		$view->filters['getowner'] 		= 1;
		$view->filters['activity'] 		= 1;
		
		// Get paging variables
		$view->filters['limit'] = $app->getUserStateFromRequest($this->_option.'.limit', 'limit', 
								  $config->getValue('config.list_limit'), 'int');
		$view->filters['start'] = JRequest::getInt('limitstart', 0);

		$obj = new Project( $this->database );

		// Get a record count
		$view->total = $obj->getCount( $view->filters, true, 0, 1 );
		
		// Get records
		$view->rows = $obj->getRecords( $view->filters, true, 0, 1 );

		// Initiate paging
		jimport('joomla.html.pagination');
		$view->pageNav = new JPagination( $view->total, $view->filters['start'], $view->filters['limit'] );
		
		// Set any errors
		if ($this->getError()) 
		{
			$view->setError( $this->getError() );
		}
		
		// Check that master path is there
		if ($this->_config->get('offroot') && !is_dir($this->_config->get('webpath')))
		{			
			$view->setError( JText::_('Master directory does not exist. Administrator must fix this! ')  
				. $this->_config->get('webpath') );	
		}		
		
		// Output the HTML
		$view->display();
	}
	
	/**
	 * Edit project info
	 * 
	 * @return     void 
	 */
	public function editTask()
	{
		// Incoming project ID
		$id = JRequest::getVar( 'id', array(0) );
		if (is_array( $id )) 
		{
			$id = $id[0];
		}
		
		// Push some styles to the template
		$document = JFactory::getDocument();
		$document->addStyleSheet(DS . 'components' . DS . $this->_option . DS . 'assets' . DS . 'css' . DS . 'projects.css');
		$document->addStyleSheet(DS . 'plugins' . DS . 'projects' . DS . 'files' . DS . 'css' . DS . 'diskspace.css');
		$document->addScript(DS . 'plugins' . DS . 'projects' . DS . 'files' . DS . 'js' . DS . 'diskspace.js');		
						
		// Do we need to incule extra scripts?
		$plugin 		= JPluginHelper::getPlugin( 'system', 'jquery' );
		$p_params 		= $plugin ? new JParameter($plugin->params) : NULL;
		
		if (!$plugin || !$p_params->get('activateAdmin'))
		{
			$document->addScript(DS . 'plugins' . DS . 'projects' . DS . 'files' . DS . 'files.js');
		}
		else
		{
			$document->addScript(DS . 'plugins' . DS . 'projects' . DS . 'files' . DS . 'files.jquery.js');
		}
		
		// Instantiate a new view
		$view = new JView( array('name'=>'edit') );
		$view->option = $this->_option;
		$view->task = $this->_task;
		$view->config = $this->_config;
		
		$obj = new Project( $this->database );
		$objAC = new ProjectActivity( $this->database );
				
		if ($id) 
		{
			if (!$obj->loadProject($id)) 
			{
				$this->setRedirect('index.php?option=' . $this->_option, 
					JText::_('COM_PROJECTS_NOTICE_ID_NOT_FOUND'), 
					'error');
				return;
			}
		}
		if (!$id) 
		{
			$this->setRedirect('index.php?option=' . $this->_option, 
				JText::_('COM_PROJECTS_NOTICE_NEW_PROJECT_FRONT_END'), 
				'error');
			return;
		}
		
		// Get project types
		$objT = new ProjectType( $this->database );
		$view->types = $objT->getTypes();
				
		// Get plugin
		JPluginHelper::importPlugin( 'projects');
		$dispatcher = JDispatcher::getInstance();
		
		// Get activity counts
		$dispatcher->trigger( 'onProjectCount', array( $obj, &$counts, 1) );
		$counts['activity'] = $objAC->getActivityCount( $obj->id, $this->juser->get('id'));
		$view->counts = $counts;
		
		// Get team
		$objO = new ProjectOwner( $this->database );
		
		// Sync with system group
		$objO->sysGroup($obj->alias, $this->_config->get('group_prefix', 'pr-'));
		
		// Get members and managers
		$view->managers = $objO->getOwnerNames($id, 0, '1', 1);	
		$view->members = $objO->getOwnerNames($id, 0, '0', 1);
		$view->authors = $objO->getOwnerNames($id, 0, '2', 1);
			
		// Get last activity
		$afilters = array('limit' => 1);
		$last_activity = $objAC->getActivities ($id, $afilters);	
		$view->last_activity = count($last_activity) > 0 ? $last_activity[0] : '';
		
		// Was project suspended?
		$view->suspended = false;
		$setup_complete = $this->_config->get('confirm_step', 0) ? 3 : 2;
		if ($obj->state == 0 && $obj->setup_stage >= $setup_complete) 
		{
			$view->suspended = $objAC->checkActivity( $id, JText::_('COM_PROJECTS_ACTIVITY_PROJECT_SUSPENDED'));
		}
		
		// Get project params
		$view->params = new JParameter( $obj->params );
		
		// Get Disk Usage
		JPluginHelper::importPlugin( 'projects', 'files' );
		$dispatcher = JDispatcher::getInstance();
		$project = $obj->getProject($id, $this->juser->get('id'));	
		$content = $dispatcher->trigger( 'diskspace', array( $this->_option, $project, 
			'files', 'admin', '', $this->_config, NULL));
		$view->diskusage = isset($content[0])  ? $content[0]: '';
						
		// Set any errors
		if ($this->getError()) 
		{
			$view->setError( $this->getError() );
		}
		
		// Get tags on this item
		$tagsHelper = new ProjectTags( $this->database);
		$view->tags = $tagsHelper->get_tag_string($id, 0, 0, NULL, 0, 1);
		
		// Output the HTML
		$view->obj = $obj;
		$view->publishing	= $this->_publishing;
		$view->display();	
	}
	
	/**
	 * Save a project and fall through to edit view
	 *
	 * @return void
	 */
	public function applyTask()
	{
		$this->saveTask(true);
	}
	
	/**
	 * Saves a project
	 * Redirects to main listing
	 * 
	 * @return     void
	 */
	public function saveTask($redirect = false)
	{
		// Check for request forgeries
		JRequest::checkToken() or jexit( 'Invalid Token' );
		
		// Config
		$setup_complete = $this->_config->get('confirm_step', 0) ? 3 : 2;
		
		// Get some needed classes
		$objAA = new ProjectActivity ( $this->database );
		
		// Incoming
		$formdata 	= $_POST;
		$id 		= JRequest::getVar( 'id', 0 );
		$action 	= JRequest::getVar( 'admin_action', '' );
		$message 	= rtrim(\Hubzero\Utility\Sanitize::clean(JRequest::getVar( 'message', '' )));
		
		// Initiate extended database class
		$obj = new Project( $this->database );
		if (!$id or !$obj->loadProject($id)) 
		{
			$this->setRedirect('index.php?option=' . $this->_option, 
				JText::_('COM_PROJECTS_NOTICE_ID_NOT_FOUND'), 
				'error');
			return;
		}
		
		$obj->title 		= $formdata['title'] ? rtrim($formdata['title']) : $obj->title;
		$obj->about 		= rtrim(\Hubzero\Utility\Sanitize::clean($formdata['about']));
		$obj->type 			= isset($formdata['type']) ? $formdata['type'] : 1;
		$obj->modified 		= JFactory::getDate()->toSql();
		$obj->modified_by 	= $this->juser->get('id');
		$obj->private 		= JRequest::getVar( 'private', 0 );
		
		$this->_message = JText::_('COM_PROJECTS_SUCCESS_SAVED');
		
		// Was project suspended?
		$suspended = false;
		if ($obj->state == 0 && $obj->setup_stage >= $setup_complete) 
		{
			$suspended = $objAA->checkActivity( $id, JText::_('COM_PROJECTS_ACTIVITY_PROJECT_SUSPENDED'));
		}
				
		$subject 		= JText::_('COM_PROJECTS_PROJECT').' "'.$obj->alias.'" ';
		$sendmail 		= 0;
		$project 		= $obj->getProject($id, $this->juser->get('id'));
		
		// Get project managers
		$objO = new ProjectOwner( $this->database );
		$managers = $objO->getIds( $id, 1, 1 );

		// Admin actions
		if ($action) 
		{
			switch ($action) 
			{
				case 'delete':   	 
					$obj->state = 2;  
				 	$what = JText::_('COM_PROJECTS_ACTIVITY_PROJECT_DELETED');
					$subject .= JText::_('COM_PROJECTS_MSG_ADMIN_DELETED');
					$this->_message = JText::_('COM_PROJECTS_SUCCESS_DELETED');
				break;
				
				case 'suspend':      
					$obj->state = 0;   
					$what = JText::_('COM_PROJECTS_ACTIVITY_PROJECT_SUSPENDED');   
					$subject .= JText::_('COM_PROJECTS_MSG_ADMIN_SUSPENDED'); 
					$this->_message = JText::_('COM_PROJECTS_SUCCESS_SUSPENDED'); 
				break;
				
				case 'reinstate':    
					$obj->state = 1; 
					$what = $suspended 
						? JText::_('COM_PROJECTS_ACTIVITY_PROJECT_REINSTATED') 
						: JText::_('COM_PROJECTS_ACTIVITY_PROJECT_ACTIVATED');  
					$subject .= $suspended 
						? JText::_('COM_PROJECTS_MSG_ADMIN_REINSTATED') 
						: JText::_('COM_PROJECTS_MSG_ADMIN_ACTIVATED') ;  
						
					$this->_message = $suspended 
						? JText::_('COM_PROJECTS_SUCCESS_REINSTATED')
						: JText::_('COM_PROJECTS_SUCCESS_ACTIVATED');   
				break;
			}
			
			// Add activity
			$objAA->recordActivity( $obj->id, $this->juser->get('id'), $what, 0, '', '', 'project', 0, $admin = 1 );
			$sendmail = 1;
		}
		elseif ($message) 
		{
			$subject .= ' - '.JText::_('COM_PROJECTS_MSG_ADMIN_NEW_MESSAGE');
			$sendmail = 1; 
			$this->_message = JText::_('COM_PROJECTS_SUCCESS_MESSAGE_SENT'); 
		}
		
		// Save changes
		if (!$obj->store()) 
		{
			$this->setError( $obj->getError() );
			return false;
		}
		
		// Incoming tags
		$tags = JRequest::getVar('tags', '', 'post');

		// Save the tags
		$rt = new ProjectTags($this->database);
		$rt->tag_object($this->juser->get('id'), $obj->id, $tags, 1, 1);
		
		// Save params
		$incoming   = JRequest::getVar( 'params', array() );
		if (!empty($incoming)) 
		{
			foreach($incoming as $key=>$value) 
			{
				if ($key == 'quota' || $key == 'pubQuota') 
				{
					// convert GB to bytes
					$value = ProjectsHtml::convertSize( floatval($value), 'GB', 'b');
				}	
						
				$obj->saveParam($id, $key, htmlentities($value));
			}
		}
		
		// Add members if specified
		$this->_saveMember();
		
		// Send message
		if ($this->_config->get('messaging', 0) && $sendmail && count($managers) > 0) 
		{
			// Email config
			$jconfig 		= JFactory::getConfig();
			$from 			= array();
			$from['name']  	= $jconfig->getValue('config.sitename').' '.JText::_('COM_PROJECTS');
			$from['email'] 	= $jconfig->getValue('config.mailfrom');

			// Html email
			$from['multipart'] = md5(date('U'));
			
			// Get message body
			$eview 					= new JView( array('name'=>'emails', 'layout' => 'admin_plain' ) );
			$eview->option 			= $this->_option;
			$eview->subject 		= $subject;
			$eview->action 			= $action;
			$eview->project 		= $project;
			$eview->message			= $message; 
						
			$body = array();
			$body['plaintext'] 	= $eview->loadTemplate();
			$body['plaintext'] 	= str_replace("\n", "\r\n", $body['plaintext']);

			// HTML email
			$eview->setLayout('admin_html');
			$body['multipart'] = $eview->loadTemplate();
			$body['multipart'] = str_replace("\n", "\r\n", $body['multipart']);		
		
			// Send HUB message
			JPluginHelper::importPlugin( 'xmessage' );
			$dispatcher = JDispatcher::getInstance();
			$dispatcher->trigger( 'onSendMessage', 
				array( 'projects_admin_notice', $subject, $body, $from, $managers, $this->_option ));
		}
				
		// Redirect to edit view?
		if ($redirect)
		{
			$this->_redirect = 'index.php?option=' . $this->_option . '&task=edit&id=' . $id;
		}
		else
		{
			$this->_redirect = 'index.php?option=' . $this->_option;
		}
	}
	
	/**
	 * Save member
	 * 
	 * @return     void
	 */
	protected function _saveMember()
	{
		// New member added?
		$members 	= urldecode(trim(JRequest::getVar( 'newmember', '', 'post'  )));
		$role 		= JRequest::getInt( 'role', 0 );
		$id 		= JRequest::getVar( 'id', 0 );
		
		// Get owner class		
		$objO = new ProjectOwner($this->database);
		
		$mbrs = explode(',', $members);

		jimport('joomla.user.helper');
		
		foreach ($mbrs as $mbr)
		{
			// Retrieve user's account info
			$mbr = trim($mbr);
			$uid = JUserHelper::getUserId($mbr);

			// Ensure we found an account
			if ($uid)
			{
				$objO->saveOwners ( $id, $this->juser->get('id'), $uid, 0, $role, $status = 1, 0);
			}
		}
	}
	
	/**
	 * Redirects
	 * 
	 * @return     void
	 */
	public function cancelTask()
	{
		$this->_redirect = 'index.php?option=' . $this->_option;
		return;
	}
	
	/**
	 * Erases all project information (to be used for test projects only)
	 * 
	 * @return     void
	 */
	public function eraseTask() 
	{
		$id = JRequest::getVar( 'id', 0 );
		$permanent = 1;
		jimport('joomla.filesystem.folder');
		
		// Initiate extended database class
		$obj = new Project( $this->database );
		if (!$id or !$obj->loadProject($id)) 
		{
			$this->setRedirect('index.php?option=' . $this->_option, 
				JText::_('COM_PROJECTS_NOTICE_ID_NOT_FOUND'), 
				'error');
			return;
		}
		
		// Get project group
		$group_prefix = $this->_config->get('group_prefix', 'pr-');
		$prgroup = $group_prefix.$obj->alias;
			
		// Store project info
		$alias = $obj->alias;
		$identifier = $alias;
		
		// Delete project
		$obj->delete();
		
		// Erase all owners
		$objO = new ProjectOwner ($this->database );
		$objO->removeOwners ( $id, '', 0, $permanent, '', $all = 1 );
		
		// Erase owner group
		$group = new \Hubzero\User\Group();
		$group->read( $prgroup );
		if ($group) 
		{
			$group->delete();	
		}		
				
		// Erase all comments
		$objC = new ProjectComment ($this->database );
		$objC->deleteProjectComments ( $id, $permanent );
		
		// Erase all activities
		$objA = new ProjectActivity( $this->database );
		$objA->deleteActivities( $id, $permanent );
		
		// Erase all todos
		$objTD = new ProjectTodo( $this->database );
		$objTD->deleteTodos( $id, '', $permanent );
		
		// Erase all blog entries
		$objB = new ProjectMicroblog( $this->database );
		$objB->deletePosts( $id, $permanent );
		
		// Erase all notes
		include_once(JPATH_ROOT.DS.'components' . DS . 'com_wiki' . DS . 'tables' . DS . 'attachment.php');
		include_once(JPATH_ROOT.DS.'components' . DS . 'com_wiki' . DS . 'tables' . DS . 'author.php');
		include_once(JPATH_ROOT.DS.'components' . DS . 'com_wiki' . DS . 'tables' . DS . 'comment.php');
		include_once(JPATH_ROOT.DS.'components' . DS . 'com_wiki' . DS . 'tables' . DS . 'log.php');
		include_once(JPATH_ROOT.DS.'components' . DS . 'com_wiki' . DS . 'tables' . DS . 'page.php');
		include_once(JPATH_ROOT.DS.'components' . DS . 'com_wiki' . DS . 'tables' . DS . 'revision.php');
		
		if (is_file(JPATH_ROOT.DS.'components' . DS . 'com_wiki' . DS . 'helpers' . DS . 'config.php')) 
		{
			include_once(JPATH_ROOT.DS.'components' . DS . 'com_wiki' . DS . 'helpers' . DS . 'config.php');
		}
		$masterscope = 'projects' . DS . $alias . DS . 'notes';
		
		// Get all notes
		$this->database->setQuery( "SELECT DISTINCT p.id FROM #__wiki_page AS p 
			WHERE p.group_cn='".$prgroup."' AND p.scope LIKE '".$masterscope."%' " );
		$notes = $this->database->loadObjectList();
		
		if ($notes) 
		{
			foreach ($notes as $note) 
			{
				$page = new WikiTablePage( $this->database );
						
				// Delete the page's history, tags, comments, etc.
				$page->deleteBits( $note->id );

				// Finally, delete the page itself
				$page->delete( $note->id );
			}
		}
					
		// Erase all files, remove files repository
		JPluginHelper::importPlugin( 'projects', 'files' );
		$dispatcher = JDispatcher::getInstance();
		$dispatcher->trigger( 'eraseRepo', array($identifier) );
		
		// Delete base dir for .git repos
		$dir 		= $alias;
		$prefix 	= $this->_config->get('offroot', 0) ? '' : JPATH_ROOT ;				
		$repodir 	= DS . trim($this->_config->get('webpath'), DS);		
		$path 		= $prefix . $repodir . DS . $dir;
		
		if (is_dir($path)) 
		{
			JFolder::delete( $path);			
		}
		
		// Delete images/preview directories
		$webdir = DS . trim($this->_config->get('imagepath', '/site/projects'), DS);
		$webpath = JPATH_ROOT . $webdir . DS . $dir;
		
		if (is_dir($webpath)) 
		{
			JFolder::delete( $webpath);
		}		
		
		// Erase all publications
		if ($this->_publishing)
		{
			// TBD
		}
		
		// Redirect
		$this->_redirect = 'index.php?option='.$this->_option;
		$this->_message = JText::_('COM_PROJECTS_PROJECT').' #'.$id.' ('.$alias.') '.JText::_('COM_PROJECTS_PROJECT_ERASED');		
	}
	
	/**
	 * Add and commit untracked/changed files
	 * 
	 * This is helpful in case git add/commit failed during file upload
	 *
	 * @return     void
	 */
	public function gitaddTask() 
	{
		$id   = JRequest::getVar( 'id', 0 );
		$file = JRequest::getVar( 'file', '' );
		
		// Initiate extended database class
		$obj = new Project( $this->database );
		if (!$id or !$obj->loadProject($id)) 
		{
			$this->setRedirect('index.php?option=' . $this->_option, 
				JText::_('COM_PROJECTS_NOTICE_ID_NOT_FOUND'), 
				'error');
			return;
		}
		
		$url = 'index.php?option=' . $this->_option . '&task=edit&id=' . $id;
		
		if (!$file)
		{			
			$this->setRedirect($url, 
				JText::_('Please specify a file/directory path to add and commit into project'), 
				'error');
			return;
		}
		
		// Delete base dir for .git repos
		$prefix  = $this->_config->get('offroot', 0) ? '' : JPATH_ROOT ;				
		$repodir = trim($this->_config->get('webpath'), DS);
		$path 	 = $prefix . DS . $repodir . DS . $obj->alias . DS . 'files';
		
		if (!is_file($path . DS . $file))
		{
			$this->setRedirect($url, 
				JText::_('Error: File not found in the project, cannot add and commit'), 
				'error');
			return;
		}
				
		// Git helper
		include_once( JPATH_ROOT . DS . 'components' . DS .'com_projects' 
			. DS . 'helpers' . DS . 'githelper.php' );
		$gitHelper = new ProjectsGitHelper(
			$this->_config->get('gitpath', '/opt/local/bin/git'), 
			$obj->owned_by_user,
			$this->_config->get('offroot', 0) ? '' : JPATH_ROOT
		);
		
		$commitMsg = '';
		
		// Git add & commit
		$gitHelper->gitAdd($path, $file, $commitMsg);
		$gitHelper->gitCommit($path, $commitMsg);
		
		// Redirect
		$this->_redirect = 'index.php?option=' . $this->_option . '&task=edit&id=' . $id;
		$this->_message = JText::_('File checked into project Git repo');
	}
	
	/**
	 * Optimize git repo
	 * 
	 * @return     void
	 */
	public function gitgcTask() 
	{
		$id = JRequest::getVar( 'id', 0 );
		
		// Initiate extended database class
		$obj = new Project( $this->database );
		if (!$id or !$obj->loadProject($id)) 
		{
			$this->setRedirect('index.php?option=' . $this->_option, 
				JText::_('COM_PROJECTS_NOTICE_ID_NOT_FOUND'), 
				'error');
			return;
		}
		
		// Get Disk Usage
		JPluginHelper::importPlugin( 'projects', 'files' );
		$dispatcher = JDispatcher::getInstance();
		$project = $obj->getProject($id, $this->juser->get('id'));	
		
		$content = $dispatcher->trigger( 'diskspace', array( $this->_option, $project, 
			'files', 'admin', 'advoptimize', $this->_config, NULL));
				
		// Redirect
		$this->_redirect = 'index.php?option='.$this->_option.'&task=edit&id='.$id;
		$this->_message = JText::_('Git repo optimized');
	}
	
	/**
	 * Unlock sync and view sync log for project
	 * 
	 * @return     void
	 */
	public function fixsyncTask() 
	{
		$id = JRequest::getVar( 'id', 0 );
		$service = 'google';
				
		// Initiate extended database class
		$obj = new Project( $this->database );
		if (!$id or !$obj->loadProject($id)) 
		{
			$this->setRedirect('index.php?option=' . $this->_option, 
				JText::_('COM_PROJECTS_NOTICE_ID_NOT_FOUND'), 
				'error');
			return;
		}
		
		// Unlock sync
		$obj->saveParam($id, $service . '_sync_lock', '');

		// Get log file
		$prefix = $this->_config->get('offroot', 0) ? '' : JPATH_ROOT ;				
		$repodir = trim($this->_config->get('webpath'), DS);		
		$sfile 	 = $prefix . DS . $repodir . DS . $obj->alias . DS . 'logs' 
			. DS . 'sync.' . JFactory::getDate()->format('Y-m') . '.log';
		
		if (file_exists($sfile))
		{
			// Serve up file
			$xserver = new \Hubzero\Content\Server();
			$xserver->filename($sfile);
			$xserver->disposition('attachment');
			$xserver->acceptranges(false);
			$xserver->saveas('sync.' . JFactory::getDate()->format('Y-m') . '.txt');
			$result = $xserver->serve_attachment($sfile, 'sync.' . JFactory::getDate()->format('Y-m') . '.txt', false);
			exit;
		}
		
		// Redirect
		$this->_redirect = 'index.php?option='.$this->_option.'&task=edit&id='.$id;
		$this->_message = JText::_('Sync log unavailable');
	}
}
