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
 * @copyright Copyright 2005-2015 HUBzero Foundation, LLC.
 * @license   http://opensource.org/licenses/MIT MIT
 */

defined('_HZEXEC_') or die();
?>
<?php
	$fieldSets = $this->form->getFieldsets('request');

	if (!empty($fieldSets)) {
		$fieldSet = array_shift($fieldSets);
		$label = !empty($fieldSet->label) ? $fieldSet->label : 'COM_MENUS_'.$fieldSet->name.'_FIELDSET_LABEL';
		echo Html::sliders('panel', Lang::txt($label), 'request-options');
		if (isset($fieldSet->description) && trim($fieldSet->description)) :
			echo '<p class="tip">'.$this->escape(Lang::txt($fieldSet->description)).'</p>';
		endif;
	?>
		<fieldset class="panelform">
			<?php $hidden_fields = ''; ?>

			<?php foreach ($this->form->getFieldset('request') as $field) : ?>
				<?php if (!$field->hidden) : ?>
				<div class="input-wrap">
					<?php echo $field->label; ?>
					<?php echo $field->input; ?>
				</div>
				<?php else : $hidden_fields .= $field->input; ?>
				<?php endif; ?>
			<?php endforeach; ?>

			<?php echo $hidden_fields; ?>
		</fieldset>
<?php
	}

	$fieldSets = $this->form->getFieldsets('params');

	foreach ($fieldSets as $name => $fieldSet) :
		$label = !empty($fieldSet->label) ? $fieldSet->label : 'COM_MENUS_'.$name.'_FIELDSET_LABEL';
		echo Html::sliders('panel', Lang::txt($label), $name.'-options');
			if (isset($fieldSet->description) && trim($fieldSet->description)) :
				echo '<p class="tip">'.$this->escape(Lang::txt($fieldSet->description)).'</p>';
			endif;
			?>
		<div class="clr"></div>
		<fieldset class="panelform">
				<?php foreach ($this->form->getFieldset($name) as $field) : ?>
					<div class="input-wrap">
						<?php echo $field->label; ?>
						<?php echo $field->input; ?>
					</div>
				<?php endforeach; ?>
		</fieldset>
<?php endforeach;?>
<?php

	$fieldSets = $this->form->getFieldsets('associations');

	foreach ($fieldSets as $name => $fieldSet) :
		$label = !empty($fieldSet->label) ? $fieldSet->label : 'COM_MENUS_'.$name.'_FIELDSET_LABEL';
		echo Html::sliders('panel', Lang::txt($label), $name.'-options');
			if (isset($fieldSet->description) && trim($fieldSet->description)) :
				echo '<p class="tip">'.$this->escape(Lang::txt($fieldSet->description)).'</p>';
			endif;
			?>
		<div class="clr"></div>
		<fieldset class="panelform">
				<?php foreach ($this->form->getFieldset($name) as $field) : ?>
					<div class="input-wrap">
						<?php echo $field->label; ?>
						<?php echo $field->input; ?>
					</div>
				<?php endforeach; ?>
		</fieldset>
<?php endforeach;