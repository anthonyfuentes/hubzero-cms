<?php
/**
 * @package    hubzero-cms
 * @copyright  Copyright 2005-2019 HUBzero Foundation, LLC.
 * @license    http://opensource.org/licenses/MIT MIT
 */

// No direct access.
defined('_HZEXEC_') or die();

$toolbarElements = [
	'title' => [Lang::txt('COM_SEARCH_HEADING_BOOST_NEW')],
	'apply' => ['create'],
	'cancel' => ['list'],
	'spacer' => [],
	'help' => ['boost']
];

$this->view('_toolbar', 'shared')
	->set('elements', $toolbarElements)
	->display();

$action = "index.php?option=$this->option&controller=$this->controller";
$boost = $this->boost;
$typeOptions = $this->typeOptions;
sort($typeOptions);

$this->view('_boost_form')
	->set('action', $action)
	->set('boost', $boost)
	->set('task', 'new')
	->set('typeOptions', $typeOptions)
	->display();
