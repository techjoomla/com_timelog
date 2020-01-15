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
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;

HTMLHelper::_('behavior.keepalive');
HTMLHelper::_('behavior.tooltip');
HTMLHelper::_('behavior.formvalidator');
HTMLHelper::_('formbehavior.chosen', 'select');
HTMLHelper::script(JUri::root() . 'media/com_timelog/js/timelog.js');
HTMLHelper::_('jquery.token');
JText::script('COM_TIMELOG_CONFIRM_DELETE_ATTACHMENT');

?>

<div class="timelog-add-form activity-edit front-end-edit">
	<?php if (!$this->canEdit) : ?>
		<h3><?php throw new Exception(Text::_('COM_TIMELOG_ERROR_MESSAGE_NOT_AUTHORISED'), 403); ?></h3>
	<?php else : ?>
		<?php if (!empty($this->item->id)): ?>
			<h1><?php echo Text::sprintf('COM_TIMELOG_EDIT_ITEM_TITLE', $this->item->id); ?></h1>
		<?php else: ?>
			<h1><?php echo Text::_('COM_TIMELOG_ADD_ITEM_TITLE'); ?></h1>
		<?php endif;
		?>

		<form id="form-activity"
		action="<?php echo Route::_('index.php?option=com_timelog&task=activity.save'); ?>" method="post"
		class="form-validate form-horizontal" enctype="multipart/form-data">
			<?php
				echo $this->form->renderField('id');
				echo $this->form->renderField('client_id');
				echo $this->form->renderField('activity_type_id');
				echo $this->form->renderField('client');
			?>
			<div class="control-group">
				<div class="control-label"><?php echo JText::_('COM_TIMELOG_FORM_LBL_ACTIVITY_LOG_TIME')?></div>
				<div class="controls add-timelog-format">
					<?php echo $this->form->getInput('hours');?>
					<?php echo $this->form->getInput('min');?>
				</div>
			</div>
			<?php
				echo $this->form->renderField('activity_note');
				echo $this->form->renderField('created_date');
			?>
			<div class="control-group">
				<div class="controls w-100 control-group-fwidth">
					<?php echo $this->form->getInput('attachment');?>
					<ul class="list-unstyled ml-0 mt-0">
					<?php
					if (!empty($this->item->oldAttachment))
					{
						$oldFiles = array();
						$token = JSession::getFormToken();

						foreach ($this->item->oldAttachment as $key=>$attachment)
						{
							echo '<input type="hidden" name="oldFiles['. $key . ']" value="'. $attachment['media_id'] . '">';

							$downloadAttachmentLink = JUri::root() . 'index.php?option=com_timelog&task=activity.downloadAttachment&' .
							$token . '=1' . '&mediaId=' . $attachment['media_id'] . '&activityId=' . $this->item->id;

						?>
							<li>
							<a
								class="mr-20"
								href="<?php echo Route::_($downloadAttachmentLink);?>"
								target=""
								title="<?php echo $this->escape(strip_tags($attachment));?>">
								<?php echo $attachment['title'];?>
								<span><i class="icon-download" aria-hidden="true"></i></span>
							</a>

							<i class="icon-trash"
								title="<?php echo Text::_('COM_TIMELOG_ATTACHMENT_DELETE');?>"
								data-mid="<?php echo $attachment['media_id'];?>"
								data-aid="<?php echo $this->item->id;?>"
								onclick="timeLog.deleteAttachment('activity.deleteAttachment', this, '<?php echo $token ?>')"></i>
							<li>
						<?php
							$i++;
						}
					}?>
					</ul>
				</div>
			</div>

			<?php
				echo $this->form->renderField('state');
				echo $this->form->getInput('created_by');
				echo $this->form->getInput('modified_by');
			?>

			<div class="control-group">
				<div class="controls">
					<?php if ($this->canSave): ?>
						<button type="submit" class="validate btn btn-primary"><?php echo Text::_('JSUBMIT');?></button>
					<?php endif; ?>
					<a class="btn" href="<?php echo Route::_('index.php?option=com_timelog&task=activityform.cancel'); ?>" title="<?php echo Text::_('JCANCEL'); ?>"><?php echo Text::_('JCANCEL');?></a>
				</div>
			</div>

			<!-- No need to show field labels on form but it is required to show validation of field -->

			<div class="hide">
				<?php
					echo $this->form->getLabel('hours');
					echo $this->form->getLabel('min');
				?>
			</div>

			<input type="hidden" name="option" value="com_timelog"/>
			<input type="hidden" name="task" value="activityform.save"/>
			<?php echo HTMLHelper::_('form.token'); ?>
		</form>
	<?php endif; ?>
</div>
<script type="text/javascript">
<!-- Close popup if data saved successfully -->
jQuery(document).ready (function(){
	jQuery('.timelog-add-form').find('.subform-repeatable').children('.btn-toolbar').remove();
	jQuery('.timelog-add-form').find('.subform-repeatable-group').children('.btn-toolbar').remove();

	if(jQuery('#system-message div').hasClass('alert alert-success'))
	{
		jQuery("#system-message-container").fadeTo(2000, 500, function(){
			window.parent.document.location.reload(true);
			window.parent.SqueezeBox.close();
		});
	}

	// Joomla form validator to check -ve value in hour and min
	document.formvalidator.setHandler('positivenumber', function (value) {
		regex=/^[+]?[0-9]*$/;
		return regex.test(value);
    });

});

var allowedAttachments    = "<?php echo $this->params->get('upload_extensions', 'image/jpeg,image/jpg,image/png,application/pdf');?>";
var attachmentMaxSize     = "<?php echo $this->params->get('upload_maxsize', 2);?>";

</script>
