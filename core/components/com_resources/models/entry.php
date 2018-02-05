<?php
/**
 * HUBzero CMS
 *
 * Copyright 2005-2015 HUBzero Foundation, LLC.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 *
 * HUBzero is a registered trademark of Purdue University.
 *
 * @package   hubzero-cms
 * @author    Kevin Wojkovich <kevinw@purdue.edu>
 * @copyright Copyright 2005-2015 HUBzero Foundation, LLC.
 * @license   http://opensource.org/licenses/MIT MIT
 * @since     Class available since release 1.3.2
 */

namespace Components\Resources\Models;

use Components\Resources\Helpers\Tags;
use Hubzero\Database\Relational;
use Hubzero\Config\Registry;
use Hubzero\Utility\Str;
use Component;
use Date;
use Lang;
use User;
use App;
use stdClass;
require_once __DIR__ . DS . 'association.php';
require_once __DIR__ . DS . 'type.php';
require_once __DIR__ . DS . 'author.php';
require_once __DIR__ . DS . 'license.php';
require_once __DIR__ . DS . 'screenshot.php';
require_once dirname(__DIR__) . DS . 'helpers' . DS . 'tags.php';

/**
 * Resource entry model
 *
 * @uses \Hubzero\Database\Relational
 */
class Entry extends Relational implements \Hubzero\Search\Searchable
{
	/**
	 * State constants
	 **/
	const STATE_ARCHIVED = -1;
	const STATE_DRAFT    = 2;
	const STATE_PENDING  = 3;
	const STATE_TRASHED  = 4;
	const STATE_DRAFT_INTERNAL = 5;

	/**
	 * The table namespace
	 *
	 * @var  string
	 */
	protected $namespace = '';

	/**
	 * The table name, non-standard naming 
	 *
	 * @var  string
	 */
	protected $table = '#__resources';

	/**
	 * Default order by for model
	 *
	 * @var  string
	 */
	public $orderBy = 'id';

	/**
	 * Fields and their validation criteria
	 *
	 * @var  array
	 */
	protected $rules = array(
		'title' => 'notempty'
	);

	/**
	 * Automatic fields to populate every time a row is created
	 *
	 * @var  array
	 */
	public $initiate = array(
		'created',
		'created_by'
	);

	/**
	 * Automatically fillable fields
	 *
	 * @var  array
	 **/
	public $always = array(
		'alias',
		'modified',
		'modified_by',
		'fulltxt',
		'introtext'
	);

	/**
	 * Path to filespace
	 *
	 * @var  string
	 */
	protected $filespace = null;

	/**
	 * Params Registry
	 *
	 * @var  object
	 */
	protected $paramsRegistry = null;

	/**
	 * Attribs Registry
	 *
	 * @var  object
	 */
	protected $attribsRegistry = null;

	/**
	 * Authorization checks flag
	 *
	 * @var  bool
	 */
	protected $_authorized = false;

	/**
	 * Tool
	 *
	 * @var  object
	 */
	public $thistool = null;

	/**
	 * Tool
	 *
	 * @var  object
	 */
	public $curtool = null;

	/**
	 * Tool revision
	 *
	 * @var  string
	 */
	public $revision = null;

	/**
	 * Generates automatic alias field value
	 *
	 * @param   array   $data  the data being saved
	 * @return  string
	 */
	public function automaticAlias($data)
	{
		if (!isset($data['alias']))
		{
			$data['alias'] = '';
		}
		$alias = str_replace(' ', '-', $data['alias']);
		return preg_replace("/[^a-zA-Z0-9\-]/", '', strtolower($alias));
	}

	/**
	 * Generates automatic created field value
	 *
	 * @return  string
	 */
	public function automaticModified()
	{
		return Date::of('now')->toSql();
	}

	/**
	 * Generates automatic created by field value
	 *
	 * @return  int
	 */
	public function automaticModifiedBy()
	{
		return User::get('id');
	}

	/**
	 * Generates automatic fulltxt field value
	 *
	 * @param   array   $data  the data being saved
	 * @return  string
	 */
	public function automaticFulltxt($data)
	{
		if (!isset($data['fulltxt']))
		{
			$data['fulltxt'] = '';
		}
		return str_replace('<br>', '<br />', $data['fulltxt']);
	}

	/**
	 * Generates automatic introtext field value
	 *
	 * @param   array   $data  the data being saved
	 * @return  string
	 */
	public function automaticIntrotext($data)
	{
		if (!isset($data['introtext']))
		{
			$data['introtext'] = $data['fulltxt'];
		}
		$data['introtext'] = \Hubzero\Utility\Str::truncate(strip_tags($data['introtext']), 300);

		return str_replace('<br>', '<br />', $data['introtext']);
	}

	/**
	 * Get parent type
	 *
	 * @return  object
	 */
	public function transformType()
	{
		//return $this->belongsToOne(__NAMESPACE__ . '\\Type', 'type_id')->row();
		return Type::oneOrNew($this->get('type'));
	}

	/**
	 * Get logical type
	 *
	 * @return  object
	 */
	public function transformLogicaltype()
	{
		//return $this->belongsToOne(__NAMESPACE__ . '\\Type', 'logicaltype_id')->row();
		return Type::oneOrNew($this->get('logicaltype'));
	}

	/**
	 * Get associated license
	 *
	 * @return  object
	 */
	public function license()
	{
		//return $this->oneToOne(__NAMESPACE__ . '\\License', 'license_id');
		return License::oneByName($this->params->get('license'));
	}

	/**
	 * Get owning group
	 *
	 * @return  object
	 */
	public function transformGroupOwner()
	{
		//return $this->belongsToOne('Hubzero\User\Group', 'group_owner');
		$group = \Hubzero\User\Group::getInstance($this->get('group_owner'));

		if (!$group)
		{
			$group = new \Hubzero\User\Group();
		}

		return $group;
	}

	/**
	 * Get all the groups allowed to access a resource
	 *
	 * @return  array
	 */
	public function transformGroups()
	{
		$allowedgroups = array();

		if ($group_access = $this->get('group_access'))
		{
			$group_access = trim($group_access);
			$group_access = trim($group_access, ';');
			$group_access = explode(';', $group_access);

			$allowedgroups += $group_access;
		}

		if ($this->get('group_owner'))
		{
			$allowedgroups[] = $this->get('group_owner');
		}

		return $allowedgroups;
	}

	/**
	 * Generates a list of screenshots
	 *
	 * @return  object
	 */
	public function screenshots()
	{
		return $this->oneToMany(__NAMESPACE__ . '\\Screenshot', 'resourceid');
	}

	/**
	 * Generates a list of authors
	 *
	 * @return  object
	 */
	public function authors()
	{
		return $this->oneToMany(__NAMESPACE__ . '\\Author', 'subid')->whereEquals('subtable', 'resources');
	}

	/**
	 * Get a list of authors
	 *
	 * @return  string
	 */
	public function authorsList()
	{
		$names = array();

		foreach ($this->authors as $contributor)
		{
			if (strtolower($contributor->get('role')) == 'submitter')
			{
				continue;
			}

			// Build the user's name and link to their profile
			$name = htmlentities($contributor->name);
			if ($contributor->get('authorid') > 0)
			{
				$name = '<a href="' . Route::url('index.php?option=com_members&id=' . $contributor->get('authorid')) . '" data-rel="contributor" class="resource-contributor" title="View the profile of ' . $name . '">' . $name . '</a>';
			}
			if ($contributor->get('role'))
			{
				$name .= ' (' . $contributor->get('role') . ')';
			}

			$names[] = $name;
		}

		return implode(', ', $names);
	}

	/**
	 * Get a list of contributors on this resource by role
	 *
	 * @param   mixed  $idx  Index value
	 * @return  array
	 */
	public function contributors($idx=null)
	{
		$contributors = array();

		if ($idx == 'tool')
		{
			// UUUGGGGHHHHHHHHHH
			// @TODO: Rewrite this
			if ($this->isTool())
			{
				$db = App::get('db');
				$sql = "SELECT n.id, t.name AS name, n.name AS xname, NULL AS xorg, n.givenName, n.givenName AS firstname, n.middleName, n.middleName AS middlename, n.surname, n.surname AS lastname, t.organization AS org, t.*, a.role"
					 . " FROM `#__tool_authors` AS t LEFT JOIN `#__users` AS n ON n.id=t.uid JOIN `#__tool_version` AS v ON v.id=t.version_id"
					 . " LEFT JOIN `#__author_assoc` AS a ON a.authorid=t.uid AND a.subtable='resources' AND a.subid=" . $db->quote($this->get('id'))
					 . " WHERE t.toolname=" . $db->quote($this->get('alias')) . " AND v.state<>3"
					 . " AND t.revision=" . $db->quote($this->get('revision'))
					 . " ORDER BY t.ordering";
				$db->setQuery($sql);
				if ($cons = $db->loadObjectList())
				{
					foreach ($cons as $k => $c)
					{
						if (!$cons[$k]->name)
						{
							$cons[$k]->name = $cons[$k]->xname;
						}
						if (trim($cons[$k]->org) == '')
						{
							$cons[$k]->org = $cons[$k]->xorg;
						}
					}
					$contributors = $cons;
				}
			}
		}
		else
		{
			$contributors = $this->authors()
				->ordered()
				->rows();

			if (!$idx)
			{
				return $contributors;
			}

			// Roles
			$op = 'is';
			if (substr($idx, 0, 1) == '!')
			{
				$op = 'not';
				$idx = ltrim($idx, '!');
			}

			$res = array();
			foreach ($contributors as $contributor)
			{
				if ($op == 'is')
				{
					if ($contributor->get('role') == $idx)
					{
						$res[] = $contributor;
					}
				}

				if ($op == 'not')
				{
					if ($contributor->get('role') != $idx)
					{
						$res[] = $contributor;
					}
				}
			}

			$contributors = $res;
		}

		return $contributors;
	}

	/**
	 * Generates a list of parents
	 *
	 * @return  object
	 */
	public function parents()
	{
		$model = new Association();
		return $this->manyToMany(__NAMESPACE__ . '\\Entry', $model->getTableName(), 'child_id', 'parent_id');
	}

	/**
	 * Generates a list of children
	 *
	 * @return  object
	 * @since   2.0.0
	 */
	public function children()
	{
		$model = new Association();
		return $this->manyToMany(__NAMESPACE__ . '\\Entry', $model->getTableName(), 'parent_id', 'child_id');
	}

	/**
	 * Check if a resource has an attachment with the specified path
	 *
	 * @param   string   $path  File path
	 * @return  boolean
	 */
	public function hasChild($path)
	{
		$row = $this->children()
			->whereEquals('standalone', 0)
			->whereEquals('path', $path, 1)
			->orWhere('path', 'LIKE', '%/' . $path, 1)
			->row();

		return $row->get('id') > 0;
	}

	/**
	 * Make this resource a child of another
	 *
	 * @param   mixed    $id  Resource object or ID
	 * @return  boolean
	 */
	public function makeChildOf($id)
	{
		if ($id instanceof Entry)
		{
			$id = $id->get('id');
		}

		if (!$id)
		{
			return false;
		}

		$model = new Association();
		$model->set('parent_id', (int)$id);
		$model->set('child_id', $this->get('id'));
		$model->set('grouping', 0);

		if (!$model->save())
		{
			$this->addError($model->getError());
			return false;
		}

		return true;
	}

	/**
	 * Make this resource a parent of another
	 *
	 * @param   mixed    $id  Resource object or ID
	 * @return  boolean
	 */
	public function makeParentOf($id)
	{
		if ($id instanceof Entry)
		{
			$id = $id->get('id');
		}

		if (!$id)
		{
			return false;
		}

		$model = new Association();
		$model->set('parent_id', $this->get('id'));
		$model->set('child_id', (int)$id);
		$model->set('grouping', 0);

		if (!$model->save())
		{
			$this->addError($model->getError());
			return false;
		}

		return true;
	}

	/**
	 * Is this a tool?
	 *
	 * @return  bool
	 */
	public function isTool()
	{
		return $this->type->isForTools();
	}

	/**
	 * Check if the resource was deleted
	 *
	 * @return  bool
	 */
	public function isDeleted()
	{
		return ($this->get('published') == 4);
	}

	/**
	 * Check if the resource is published
	 *
	 * @return  bool
	 */
	public function isPublished()
	{
		if ($this->isNew())
		{
			return false;
		}

		// Make sure the resource is published and standalone
		if (in_array($this->get('published'), array(0, 2, 4, 5)))
		{
			return false;
		}

		if ($this->get('publish_up')
		 && $this->get('publish_up') != '0000-00-00 00:00:00'
		 && $this->get('publish_up') >= Date::toSql())
		{
			return false;
		}

		if ($this->get('publish_down')
		 && $this->get('publish_down') != '0000-00-00 00:00:00'
		 && $this->get('publish_down') <= Date::toSql())
		{
			return false;
		}

		return true;
	}

	/**
	 * Check if the resource is owned by a group
	 *
	 * @param   mixed  $group
	 * @return  bool
	 */
	public function inGroup($group=null)
	{
		if ($group)
		{
			if (!is_array($group))
			{
				$group = array($group);
			}

			if (in_array($this->get('group_owner'), $group))
			{
				return true;
			}
		}
		else
		{
			if ($this->get('group_owner'))
			{
				return true;
			}
		}

		return false;
	}

	/**
	 * Do some work on the path to make sure it's kosher
	 *
	 * @return  string
	 */
	public function transformPath()
	{
		$path = stripslashes($this->get('path'));

		if (!preg_match("/(?:https?:|mailto:|ftp:|gopher:|news:|file:)/", $path))
		{
			$path = DS . ltrim($path, DS);

			if (substr($path, 0, strlen($this->params->get('uploadpath'))) != $this->params->get('uploadpath'))
			{
				$path = DS . trim($this->params->get('uploadpath'), DS) . $path;
			}
		}

		return $path;
	}

	/**
	 * Transform attribs
	 *
	 * @return  object
	 */
	public function transformAttribs()
	{
		if (!is_object($this->attribsRegistry))
		{
			$this->attribsRegistry = new Registry($this->get('attribs'));
		}

		return $this->attribsRegistry;
	}

	/**
	 * Transform params
	 *
	 * @return  object
	 */
	public function transformParams()
	{
		if (!is_object($this->paramsRegistry))
		{
			$params = new Registry($this->get('params'));

			$p = Component::params('com_resources');
			$p->merge($params);

			$this->paramsRegistry = $p;
		}

		return $this->paramsRegistry;
	}

	/**
	 * Transform display date
	 *
	 * @return  string
	 */
	public function transformDate()
	{
		if (!$this->get('modified') || $this->get('modified') == '0000-00-00 00:00:00')
		{
			$this->set('modified', $this->get('created'));
		}

		if (!$this->get('publish_up') || $this->get('publish_up') == '0000-00-00 00:00:00')
		{
			$this->set('publish_up', $this->get('created'));
		}

		// Set the display date
		switch ($this->params->get('show_date'))
		{
			case 1:
				$thedate = Date::of($this->get('created'))->toLocal(Lang::txt('DATE_FORMAT_HZ1'));
				break;
			case 2:
				$thedate = Date::of($this->get('modified'))->toLocal(Lang::txt('DATE_FORMAT_HZ1'));
				break;
			case 3:
				$thedate = Date::of($this->get('publish_up'))->toLocal(Lang::txt('DATE_FORMAT_HZ1'));
				break;
			case 0:
			default:
				$thedate = '';
				break;
		}

		return $thedate;
	}

	/**
	 * Transform rating float into text
	 *
	 * @return  string
	 */
	public function transformRating()
	{
		switch ($this->get('rating'))
		{
			case 0.5:
				$cls = 'half-stars';
				break;
			case 1:
				$cls = 'one-stars';
				break;
			case 1.5:
				$cls = 'onehalf-stars';
				break;
			case 2:
				$cls = 'two-stars';
				break;
			case 2.5:
				$cls = 'twohalf-stars';
				break;
			case 3:
				$cls = 'three-stars';
				break;
			case 3.5:
				$cls = 'threehalf-stars';
				break;
			case 4:
				$cls = 'four-stars';
				break;
			case 4.5:
				$cls = 'fourhalf-stars';
				break;
			case 5:
				$cls = 'five-stars';
				break;
			case 0:
			default:
				$cls = 'no-stars';
				break;
		}

		return $cls;
	}

	/**
	 * Transform description
	 *
	 * @return  string
	 */
	public function transformDescription()
	{
		$content = stripslashes($this->get('fulltxt'));
		$content = preg_replace("#<nb:(.*?)>(.*?)</nb:(.*?)>#s", '', $content);
		$content = str_replace(array('="/site/', '="site/'), '="' . str_replace(PATH_ROOT, '', PATH_APP) . '/site/', $content);

		$content = \Html::content('prepare', $content);

		$content = preg_replace('/^(<!-- \{FORMAT:.*\} -->)/i', '', $content);

		return $content;
	}

	/**
	 * Transform description
	 *
	 * @return  string
	 */
	public function fields()
	{
		$data = array();

		preg_match_all("#<nb:(.*?)>(.*?)</nb:(.*?)>#s", $this->get('fulltxt'), $matches, PREG_SET_ORDER);

		if (count($matches) > 0)
		{
			foreach ($matches as $match)
			{
				$data[$match[1]] = stripslashes($match[2]);
			}
		}

		return $data;
	}

	/**
	 * Delete a record and any associated data
	 *
	 * @return  bool
	 */
	public function destroy()
	{
		// Remove children
		foreach ($this->children()->rows() as $child)
		{
			if ($child->get('standalone'))
			{
				continue;
			}

			if (!$child->destroy())
			{
				$this->addError($child->getError());
				return false;
			}
		}

		// Remove parent associations
		$parents = Association::all()
			->whereEquals('child_id', $this->get('id'))
			->rows();

		foreach ($parents as $parent)
		{
			if (!$parent->destroy())
			{
				$this->addError($parent->getError());
				return false;
			}
		}

		// Remove any associated files
		$path = $this->filespace();

		if (is_dir($path))
		{
			if (!Filesystem::deleteDirectory($path))
			{
				$this->addError('Unable to delete file(s).');

				return false;
			}
		}

		return parent::destroy();
	}

	/**
	 * Build and return the base path to resource file storage
	 *
	 * @return  string
	 */
	public function basepath()
	{
		static $base;

		if (!$base)
		{
			$base = PATH_APP . DS . trim(Component::params('com_resources')->get('webpath', '/site/resources'), '/');
		}

		return $base;
	}

	/**
	 * Build and return the relative path to resource file storage
	 *
	 * @return  string
	 */
	public function relativepath()
	{
		$date = $this->get('created');

		if ($date && preg_match("/([0-9]{4})-([0-9]{2})-([0-9]{2})[ ]([0-9]{2}):([0-9]{2}):([0-9]{2})/", $date, $regs))
		{
			$date = mktime($regs[4], $regs[5], $regs[6], $regs[2], $regs[3], $regs[1]);
		}
		if ($date)
		{
			$dir_year  = Date::of($date)->format('Y');
			$dir_month = Date::of($date)->format('m');

			if (!is_dir($this->basepath() . DS . $dir_year . DS . $dir_month . DS . Str::pad($this->get('id')))
			 && intval($dir_year) <= 2013
			 && intval($dir_month) <= 11)
			{
				$dir_year  = Date::of($date)->toLocal('Y');
				$dir_month = Date::of($date)->toLocal('m');
			}
		}
		else
		{
			$dir_year  = Date::of('now')->format('Y');
			$dir_month = Date::of('now')->format('m');
		}

		return DS . $dir_year . DS . $dir_month . DS . Str::pad($this->get('id'));
	}

	/**
	 * Build and return the path to resource file storage
	 *
	 * @return  string
	 */
	public function filespace()
	{
		if (!$this->filespace)
		{
			$this->filespace = $this->basepath() . $this->relativepath();
		}

		return $this->filespace;
	}

	/**
	 * Build and return the url
	 *
	 * @return  string
	 */
	public function link()
	{
		return 'index.php?option=com_resources&' . ($this->get('alias') ? 'alias=' . $this->get('alias') : 'id=' . $this->get('id'));
	}

	/**
	 * Build and return the url
	 *
	 * @param   string  $as
	 * @return  string
	 */
	public function tags($as = 'list')
	{
		$cloud = new Tags($this->get('id'));

		if ($as == 'list')
		{
			$tags = array();
			foreach ($cloud->tags() as $tag)
			{
				array_push($tags, $tag->tag);
			}

			return $tags;
		}

		return $cloud->tags();
	}

	/**
	 * Check a user's authorization
	 *
	 * @param   string   $action  Action to check
	 * @return  boolean  True if authorized, false if not
	 */
	public function access($action='view')
	{
		if (!$this->_authorized)
		{
			$this->_authorize();
		}
		return $this->params->get('access-' . strtolower($action) . '-resource');
	}

	/**
	 * Authorize current user
	 *
	 * @return  void
	 */
	private function _authorize()
	{
		// NOT logged in
		if (User::isGuest())
		{
			// If the resource is published and public
			if ($this->isPublished() && ($this->get('access') == 0 || $this->get('access') == 3))
			{
				// Allow view access
				$this->params->set('access-view-resource', true);
				if ($this->get('access') == 0)
				{
					$this->params->set('access-view-all-resource', true);
				}
			}
			$this->_authorized = true;
			return;
		}

		if ($this->isTool())
		{
			$tconfig = Component::params('com_tools');

			if ($admingroup = trim($tconfig->get('admingroup', '')))
			{
				// Check if they're a member of admin group
				$ugs = \Hubzero\User\Helper::getGroups(User::get('id'));
				if ($ugs && count($ugs) > 0)
				{
					$admingroup = strtolower($admingroup);
					foreach ($ugs as $ug)
					{
						if (strtolower($ug->cn) == $admingroup)
						{
							$this->params->set('access-view-resource', true);
							$this->params->set('access-view-all-resource', true);

							$this->params->set('access-admin-resource', true);
							$this->params->set('access-manage-resource', true);

							$this->params->set('access-create-resource', true);
							$this->params->set('access-delete-resource', true);
							$this->params->set('access-edit-resource', true);
							$this->params->set('access-edit-state-resource', true);
							$this->params->set('access-edit-own-resource', true);
							break;
						}
					}
				}
			}

			if (!$this->params->get('access-admin-resource')
			 && !$this->params->get('access-manage-resource'))
			{
				// If logged in and resource is published and public or registered
				if ($this->isPublished() && ($this->get('access') == 0 || $this->get('access') == 1))
				{
					// Allow view access
					$this->params->set('access-view-resource', true);
					$this->params->set('access-view-all-resource', true);
				}

				if ($this->get('group_owner'))
				{
					// For protected resources, make sure users can see abstract
					if ($this->get('access') < 3)
					{
						$this->params->set('access-view-resource', true);
						$this->params->set('access-view-all-resource', true);
					}
					else if ($this->get('access') == 3)
					{
						$this->params->set('access-view-resource', true);
					}

					// Get the groups the user has access to
					$xgroups = \Hubzero\User\Helper::getGroups(User::get('id'), 'all');
					$usersgroups = array();
					if (!empty($xgroups))
					{
						foreach ($xgroups as $group)
						{
							if ($group->regconfirmed)
							{
								$usersgroups[] = $group->cn;
							}
						}
					}

					// Get the groups that can access this resource
					$allowedgroups = $this->groups;

					// Find what groups the user has in common with the resource, if any
					$common = array_intersect($usersgroups, $allowedgroups);

					// Check if the user is apart of the group that owns the resource
					// or if they have any groups in common
					if (in_array($this->get('group_owner'), $usersgroups) || count($common) > 0)
					{
						$this->params->set('access-view-resource', true);
						$this->params->set('access-view-all-resource', true);
					}
				}

				require_once Component::path('com_tools') . '/tables/tool.php';

				$db = App::get('db');
				$obj = new \Components\Tools\Tables\Tool($db);
				$obj->loadFromName($this->get('alias'));

				// check if user in tool dev team
				if ($developers = $obj->getToolDevelopers($obj->id))
				{
					foreach ($developers as $dv)
					{
						if ($dv->uidNumber == User::get('id'))
						{
							$this->params->set('access-view-resource', true);
							$this->params->set('access-view-all-resource', true);
							$this->params->set('access-create-resource', true);
							$this->params->set('access-delete-resource', true);
							$this->params->set('access-edit-resource', true);
							$this->params->set('access-edit-state-resource', true);
							$this->params->set('access-edit-own-resource', true);
						}
					}
				}
			}

			$this->_authorized = true;
			return;
		}
		else
		{
			// Check if they're a site admin (from Joomla)
			$this->params->set('access-admin-resource', User::authorise('core.admin', null));
			$this->params->set('access-manage-resource', User::authorise('core.manage', null));
			if ($this->params->get('access-admin-resource')
			 || $this->params->get('access-manage-resource'))
			{
				$this->params->set('access-view-resource', true);
				$this->params->set('access-view-all-resource', true);

				$this->params->set('access-create-resource', true);
				$this->params->set('access-delete-resource', true);
				$this->params->set('access-edit-resource', true);
				$this->params->set('access-edit-state-resource', true);
				$this->params->set('access-edit-own-resource', true);

				$this->_authorized = true;
				return;
			}

			$author_ids = array();
			foreach ($this->authors as $author)
			{
				$author_ids[] = $author->get('authorid');
			}

			// If they're not an admin

			// If logged in and resource is published and public or registered
			if ($this->isPublished() && ($this->get('access') == 0 || $this->get('access') == 1))
			{
				// Allow view access
				$this->params->set('access-view-resource', true);
				$this->params->set('access-view-all-resource', true);
			}

			// Check if they're the resource creator
			if ($this->get('created_by') == User::get('id'))
			{
				// Give full access
				$this->params->set('access-view-resource', true);
				$this->params->set('access-view-all-resource', true);

				$this->params->set('access-create-resource', true);
				$this->params->set('access-delete-resource', true);
				$this->params->set('access-edit-resource', true);
				$this->params->set('access-edit-state-resource', true);
				$this->params->set('access-edit-own-resource', true);
			}
			// Listed as a contributor
			else if (in_array(User::get('id'), $author_ids))
			{
				// Give full access
				$this->params->set('access-view-resource', true);
				$this->params->set('access-view-all-resource', true);

				$this->params->set('access-create-resource', true);
				$this->params->set('access-delete-resource', true);
				$this->params->set('access-edit-resource', true);
				$this->params->set('access-edit-state-resource', true);
				$this->params->set('access-edit-own-resource', true);
			}
			// Check group access
			else if ($this->get('group_owner')) // && ($this->get('access') == 3 || $this->get('access') == 4))
			{
				// For protected resources, make sure users can see abstract
				if ($this->get('access') < 3)
				{
					$this->params->set('access-view-resource', true);
					$this->params->set('access-view-all-resource', true);
				}
				else if ($this->get('access') == 3)
				{
					$this->params->set('access-view-resource', true);
				}

				// Get the groups the user has access to
				$xgroups = \Hubzero\User\Helper::getGroups(User::get('id'), 'all');

				$usersgroups = array();
				if (!empty($xgroups))
				{
					foreach ($xgroups as $group)
					{
						if ($group->regconfirmed)
						{
							$usersgroups[] = $group->cn;
						}
					}
				}

				// Get the groups that can access this resource
				$allowedgroups = $this->groups;

				// Find what groups the user has in common with the resource, if any
				$common = array_intersect($usersgroups, $allowedgroups);

				// Check if the user is apart of the group that owns the resource
				// or if they have any groups in common
				if (in_array($this->get('group_owner'), $usersgroups) || count($common) > 0)
				{
					$this->params->set('access-view-resource', true);
					$this->params->set('access-view-all-resource', true);
				}
			}
			else
			{
				$this->params->set('access-view-resource', true);
				$this->params->set('access-view-all-resource', true);
			}
		}

		$this->_authorized = true;
	}

	/**
	 * Compile tool information
	 *
	 * @param   string  $revision
	 * @return  void
	 */
	private function compileToolInfo($revision=null)
	{
		if (!Component::isEnabled('com_tools'))
		{
			return;
		}

		if ($this->isTool())
		{
			require_once Component::path('com_tools') . '/tables/version.php';

			$this->thistool = null;
			$this->curtool  = null;
			$this->revision = null;

			$db = App::get('db');

			$revisions = array();
			$tv = new \Components\Tools\Tables\Version($db);
			$tv->getToolVersions('', $revisions, $this->get('alias'));

			if ($revisions)
			{
				foreach ($revisions as $tool)
				{
					// Archive version, if requested
					if (($revision && $tool->revision == $revision && $revision != 'dev')
					 or ($revision == 'dev' and $tool->state==3))
					{
						$this->thistool = $tool;
					}
					// Current version
					if ($tool->state == 1 && (count($revisions) == 1 || (count($revisions) > 1 && $revisions[1]->version == $tool->version)))
					{
						$this->curtool = $tool;
						$revision = $revision ? $revision : $tool->revision;
					}
					// Dev version
					if (!$revision && count($revisions) == 1 && $tool->state == 3)
					{
						$this->thistool = $tool;
						$revision = 'dev';
					}
				}

				if (!$this->thistool && !$this->curtool && count($revisions) > 1)
				{
					// Tool is retired, display latest unpublished version
					$this->thistool = $revisions[1];
					$revision = $this->thistool->revision;
				}

				// If the revision is the same as the current version
				if ($this->curtool && $this->thistool && $this->thistool == $this->curtool)
				{
					// Display default resource page for current version
					$this->thistool = null;
				}
			}

			$tconfig = Component::params('com_tools');

			// Replace resource info with requested version
			$resource = $this->toObject();
			$tv->compileResource($this->thistool, $this->curtool, $resource, $revision, $tconfig);
			$this->set($resource);

			$this->revision = $revision;
		}
	}

	/**
	 * Applies a where clause for published items
	 *
	 * @return  $this
	 **/
	public function wherePublished()
	{
		$r = $this->getTableName();

		// Set state
		$this->whereEquals('published', self::STATE_PUBLISHED);

		// Honor publishing window
		$now = Date::toSql();

		$this->whereEquals($r . '.publish_up', '0000-00-00 00:00:00', 1)
			->orWhere($r . '.publish_up', '<=', $now, 1)
			->resetDepth()
			->whereEquals($r . '.publish_down', '0000-00-00 00:00:00', 1)
			->orWhere($r . '.publish_down', '>=', $now, 1)
			->resetDepth();

		return $this;
	}

	/**
	 * Generates a list the most recent entries
	 *
	 * @param   integer  $limit
	 * @param   string   $dateField
	 * @param   string   $sort
	 * @return  object
	 */
	public static function getLatest($limit = 10, $dateField = 'created', $sort = 'DESC')
	{
		return self::all()
			->whereEquals('standalone', 1)
			->order($dateField, $sort)
			->limit($limit);
	}

	/**
	 * Get a record by alias or ID
	 *
	 * @param   mixed   $id
	 * @param   string  $revision
	 * @return  object
	 */
	public static function getInstance($id, $revision=null)
	{
		if (is_integer($id))
		{
			$result = self::oneOrNew($id);
		}
		else
		{
			$result = self::oneByAlias($id);

			if (!$result)
			{
				$result = self::blank();
			}
		}

		if ($result->isTool())
		{
			$result->compileToolInfo($revision);
		}

		return $result;
	}

	/**
	 * Build a query based on commonly used filters
	 *
	 * @param   array  $filters
	 * @return  object
	 */
	public static function allWithFilters($filters = array())
	{
		$query = self::all();

		$r = $query->getTableName();
		$a = Author::blank()->getTableName();

		$query
			->select($r . '.*');

		if (isset($filters['standalone']))
		{
			$query->whereEquals($r . '.standalone', $filters['standalone']);
		}

		if (isset($filters['published']))
		{
			$query->whereIn($r . '.published', (array) $filters['published']);
		}

		if (isset($filters['group']))
		{
			$query->whereEquals($r . '.group_owner', (string) $filters['group']);
		}

		if (isset($filters['type']))
		{
			if (!is_numeric($filters['type']))
			{
				$filters['type'] = Type::oneByAlias($filters['type'])->get('id');
			}
			$query->whereEquals($r . '.type', $filters['type']);
		}

		if (isset($filters['tag']) && $filters['tag'])
		{
			$to = \Components\Tags\Models\Objct::blank()->getTableName();
			$tg = \Components\Tags\Models\Tag::blank()->getTableName();

			$cloud = new \Components\Resources\Helpers\Tags();
			$tags = $cloud->parse($filters['tag']);

			$query->join($to, $to . '.objectid', $r . '.id');
			$query->join($tg, $tg . '.id', $to . '.tagid', 'inner');
			$query->whereEquals($to . '.tbl', 'resources');
			$query->whereIn($tg . '.tag', $tags);
		}

		if (isset($filters['search']))
		{
			$query->whereLike($r . '.title', $filters['search'], 1)
				->orWhereLike($r . '.fulltxt', $filters['search'], 1)
				->resetDepth();
		}

		if (isset($filters['created_by']))
		{
			$query->whereEquals($r . '.created_by', $filters['created_by']);
		}

		if (isset($filters['author']))
		{
			$query
				->join($a, $a . '.subid', $r . '.id', 'left')
				->whereEquals($a . '.subtable', 'resources')
				->whereEquals($a . '.authorid', $filters['author']);

			if (isset($filters['notauthorrole']))
			{
				$query->where($a . '.role', '!=', $filters['notauthorrole']);
			}
		}

		if (isset($filters['access']) && !empty($filters['access']))
		{
			if (!is_array($filters['access']) && !is_numeric($filters['access']))
			{
				switch ($filters['access'])
				{
					case 'public':
						$filters['access'] = 0;
						break;
					case 'protected':
						$filters['access'] = 3;
						break;
					case 'private':
						$filters['access'] = 4;
						break;
					case 'all':
					default:
						$filters['access'] = array(0, 1, 2, 3, 4);
						break;
				}
			}

			if (isset($filters['usergroups']) && !empty($filters['usergroups']))
			{
				$query->whereIn($r . '.access', (array) $filters['access'], 1)
					->orWhereIn($r . '.group_owner', (array) $filters['usergroups'], 1)
					->resetDepth();
			}
			else
			{
				$query->whereIn($r . '.access', (array) $filters['access']);
			}
		}
		elseif (isset($filters['usergroups']) && !empty($filters['usergroups']))
		{
			$query->whereIn($r . '.group_owner', (array) $filters['usergroups']);
		}

		if (isset($filters['now']))
		{
			$query->whereEquals($r . '.publish_up', '0000-00-00 00:00:00', 1)
				->orWhere($r . '.publish_up', '<=', $filters['now'], 1)
				->resetDepth()
				->whereEquals($r . '.publish_down', '0000-00-00 00:00:00', 1)
				->orWhere($r . '.publish_down', '>=', $filters['now'], 1)
				->resetDepth();
		}

		if (isset($filters['startdate']) && $filters['startdate'])
		{
			$query->where($r . '.publish_up', '>', $filters['startdate']);
		}
		if (isset($filters['enddate']) && $filters['enddate'])
		{
			$query->where($r . '.publish_up', '<', $filters['enddate']);
		}

		$query->group($r . '.id');

		return $query;
	}
	public function searchableDescription()
	{
		$description = $this->fulltxt . ' ' . $this->introtext;
		$description = html_entity_decode($description);
		$description = \Hubzero\Utility\Sanitize::stripAll($description);
		return $description;
	}

	public function transformAccessLevel()
	{
		$accessLevel = 'private';
		if ($this->standalone == 1 && $this->published == 1)
		{
			switch ($this->access)
			{
				case 0:
					$accessLevel = 'public';
				break;
				case 1:
					$accessLevel = 'registered';
				break;
				case 4:
				default:
					$accessLevel = 'private';
			}
		}
		return $accessLevel;
	}

	/**
	 * Get the groups allowed to access a resource
	 *
	 * @return  array
	 */
	public function getGroups()
	{
		if ($this->group_access != '')
		{
			$this->group_access = trim($this->group_access);
			$this->group_access = substr($this->group_access, 1, (strlen($this->group_access)-2));
			$allowedgroups = explode(';', $this->group_access);
		}
		else
		{
			$allowedgroups = array();
		}
		$groupOwner = $this->get('group_owner', '');
		if (!empty($groupOwner))
		{
			$allowedgroups[] = $groupOwner;
		}

		return $allowedgroups;
	}

	public function searchResult()
	{
		$obj = new stdClass;

		$obj->url = Request::root() . $this->link();
		$obj->title = $this->title;
		$id = $this->id;
		$obj->id = 'resource-' . $id;
		$obj->hubtype = 'resource';

		$obj->type = $this->type()->type;

		$obj->description = $this->searchableDescription();
		$obj->author = $this
			->authors()
			->select('name')
			->rows()
			->fieldsbyKey('name');

		$obj->access_level = $this->access_level;
		$groups = $this->getGroups();
		$tags = $this->tags(false);

		if (!empty($tags))
		{
			foreach ($tags as $tag)
			{
				$title = $tag->get('raw_tag', '');
				$obj->tags[] = array(
					'id' => 'tag-' . $tag->id,
					'title' => $title,
					'access_level' => $tag->admin == 0 ? 'public' : 'private',
					'type' => 'tag'
				);
			}
		}

		if (!empty($groups))
		{
			foreach ($groups as $g => $group)
			{
				$grp = \Hubzero\User\Group::getInstance($group);
				if ($grp)
				{
					$groups[$g] = $grp->get('gidNumber');
				}
				// Group not found
				else
				{
					unset($groups[$g]);
				}
			}
			$groups = array_unique($groups);
			$obj->owner_type = 'group';
			$obj->owner = $groups;
		}
		else
		{
			$obj->owner_type = 'user';
			$obj->owner = $this->created_by;
		}
		return (array) $obj;
	}

	public static function searchTotal()
	{
		$total = self::all()->total();
		return $total;
	}

	public static function searchResults($limit, $offset = 0)
	{
		return self::all()->start($offset)->limit($limit)->rows();
	}
}