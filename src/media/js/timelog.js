/*
 * @package    Com_Timelog
 * @author     Techjoomla <extensions@techjoomla.com>
 * @copyright  Copyright (c) 2009-2020 TechJoomla. All rights reserved
 * @license    GNU General Public License version 2, or later
 */
var timeLog = {
    openTimeLogPopup: function(url, popupclass="timelog-activities") {
        var wwidth = jQuery(window).width() - 50;
        var wheight = jQuery(window).height() - 50;
        SqueezeBox.open(url, {
            handler: 'iframe',
            closable: false,
            size: {
                x: wwidth,
                y: wheight
            },
            /*iframePreload:true,*/
            sizeLoading: {
                x: wwidth,
                y: wheight
            },
            classWindow: popupclass,
        });
    },
    closePopup: function() {
        window.parent.document.location.reload(true);
        window.parent.SqueezeBox.close();
    },
	deleteAttachment: function(task, currentElement, jtoken)
	{
		if(confirm(Joomla.JText._('COM_TIMELOG_CONFIRM_DELETE_ATTACHMENT')) == true)
		{
			var activityId = jQuery(currentElement).attr('data-aid');
			var mediaId    = jQuery(currentElement).attr('data-mid');

			jQuery.ajax({
				url: Joomla.getOptions('system.paths').base + "/index.php?option=com_timelog&" + jtoken + "=1",
				data: {
					activityId: activityId,
					mediaId: mediaId,
					task: task
				},
				type: 'POST',
				dataType:'JSON',
				success: function(data) {
					let msg = data.message;
					if (data.success === true)
					{
						Joomla.renderMessages({'alert alert-success': [msg]});
						jQuery("html, body").animate({
							scrollTop: 0
						}, 2000);
					}
					else
					{
						Joomla.renderMessages({'alert alert-error': [msg]});
						jQuery("html, body").animate({
							scrollTop: 0
						}, 2000);
					}
					setTimeout(function(){
						window.location.reload(1);
					}, 2000);
				}
			});
		}
		else
		{
			return false;
		}
	},
	validateFile: function(thisFile) {
		/** Validation is for file field only */
		if (jQuery(thisFile).attr('type') != 'file')
		{
			return false;
		}

		/** Clear error message */
		jQuery('#system-message-container').empty();

		var uploadedfile = jQuery(thisFile)[0].files[0];
		var fileType = uploadedfile.type;
		var fileExtension = uploadedfile.name.split(".");

		/** global: allowedAttachments */
		var allowedExtensionsArray = allowedAttachments.split(",");

		var invalid = 0;
		var errorMsg = new Array();

		if ((fileExtension[fileExtension.length-1] !== ''|| fileExtension[fileExtension.length-1] !== null) && (jQuery.inArray(fileType , allowedExtensionsArray) == -1))
		{
			invalid = "1";
			errorMsg.push(Joomla.JText._('COM_TIMELOG_FILE_TYPE_ERROR'));
		}

		var uploadedFileSize       = uploadedfile.size;

		/** global: attachmentMaxSize */
		if (uploadedFileSize > attachmentMaxSize * 1024 *1024)
		{
			invalid = "1";
			errorMsg.push(Joomla.JText._('COM_TIMELOG_FILE_SIZE_ERROR'));
		}

		if (invalid)
		{
			Joomla.renderMessages({'error': errorMsg});

			jQuery("html, body").animate({
				scrollTop: 0
			}, 500);

			return false;
		}
	},
}

jQuery(document).on('click', '.closetimelogpopup', function() {

    if (jQuery(this).data('refresh') == 1) {
        window.parent.document.location.reload(true);
    }

    window.parent.SqueezeBox.close();
});
