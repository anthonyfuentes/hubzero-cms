<?php
/**
 * @package    hubzero-cms
 * @copyright  Copyright 2005-2019 HUBzero Foundation, LLC.
 * @license    http://opensource.org/licenses/MIT MIT
 */

// No direct access.
defined('_HZEXEC_') or die();

$this->css('
#noresults {
	margin-right: auto;
	margin-left: auto;
}
');
?>

<table class="adminlist searchDocument">
	<thead>
		<tr>
			<th><?php echo Lang::txt('ID'); ?></th>
			<th><?php echo Lang::txt('Type'); ?></th>
			<th><?php echo Lang::txt('Title'); ?></th>
			<th><?php echo Lang::txt('Access'); ?></th>
			<th><?php echo Lang::txt('Owner'); ?></th>
			<th> </th>
		</tr>
	</thead>
	<tbody>
		<?php foreach ($this->documents as $document): ?>
			<tr>
				<td><?php echo $document['id']; ?></td>
				<td><?php echo $document['hubtype']; ?></td>
				<td><?php echo $document['title'][0]; ?></td>
				<td><?php echo $document['access_level']; ?></td>
				<td>
					<?php 
						if (isset($document['owner']) && $document['owner'] == '')
						{
							if ($document['owner_type'] == 'user')
							{
								$user = \Hubzero\User\User::one($document['owner'][0]);
								if (isset($user) && is_object($user))
								{
									echo $user->get('name');
								}
								else
								{
									echo Lang::txt('UNKNOWN');
								}
							}
							elseif ($document['owner_type'] == 'group')
							{
								$group = \Hubzero\User\Group::getInstance($document['owner'][0]);
								if (isset($group) && is_object($group))
								{
									echo $group->get('description');
								}
								else
								{
									echo Lang::txt('UNKNOWN');
								}
							}
						}
						else
						{
							echo $document['owner_type'] . ' - ';
							echo Lang::txt('UNKNOWN');
						}
					?>
				</td>
				<td>
					<?php if (!in_array($document['id'], $this->blacklist)): ?>
						<a class="button" href="<?php echo Route::url('index.php?option=' . $this->option . '&task=addToBlackList'
						. '&id=' . $document['id']
						. '&facet=' . $this->facet
						. '&limit=' . $this->pagination->limit
						. '&limitstart=' . $this->pagination->limitstart
						); ?>"><?php echo Lang::txt('COM_SEARCH_ADD_BLACKLIST'); ?></a>
					<?php else: ?>
						<span><?php echo Lang::txt('COM_SEARCH_MARKED_FOR_REMOVAL'); ?></span>
					<?php endif; ?>

				</td>
			</tr>
		<?php endforeach; ?>
	</tbody>
	<tfoot>
		<tr>
			<td colspan="11">
				<input type="hidden" name="facet" value="<?php echo $this->facet; ?>"/>
				<?php echo $this->pagination->render(); ?>
			</td>
		</tr>
	</tfoot>
</table>
