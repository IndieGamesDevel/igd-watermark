jQuery(document).ready(function() {
	if (typeof wp === 'undefined' || !wp.media) return;

	function apply_watermark(attachment_id)
	{
		jQuery.ajax(
		{
			url: igd_watermark.ajax_url,
			type: 'POST',
			dataType: 'json',
			data:
			{
				action: 'igd_watermark_action',
				attachment_id: attachment_id,
				nonce: igd_watermark.nonce
			},
			success: function(response)
			{
				console.log(response);
				jQuery("button.media-modal-close").click();
				if (response.success) location.reload();
				else alert('Si è verificato un errore durante l\'applicazione del watermark.');
			},
			error: function() { alert('Si è verificato un errore durante l\'applicazione del watermark.'); }
		});
	}

	window.applyWatermarkOnClick = function(attachment_id)
	{
		if (attachment_id) apply_watermark(attachment_id);
		else alert('Seleziona un\'immagine dalla media library.');
	};

	jQuery(document).on('click', 'li.attachment', function()
	{
		if (!wp.media) return;
		window.attachment_id = jQuery(this).data("id");
		var check_media_library_loaded = setInterval(function()
		{
			var attachment_actions = jQuery('.attachment-details .attachment-actions');
			if (attachment_actions.length)
			{
				if (jQuery('.apply-watermark').length === 0)
					attachment_actions.append('<button class="apply-watermark button" onclick="applyWatermarkOnClick(window.attachment_id);">Applica Watermark</button>');
				clearInterval(check_media_library_loaded);
			}
		}, 250);
	});
});
