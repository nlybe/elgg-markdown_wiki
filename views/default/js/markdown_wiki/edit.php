
/**
 *	Elgg-markdown_wiki plugin
 *	@package elgg-markdown_wiki
 *	@author Emmanuel Salomon @ManUtopiK
 *	@license GNU Affero General Public License, version 3 or late
 *	@link https://github.com/ManUtopiK/elgg-markdown_wiki
 *
 *	Elgg-markdown_wiki edit javascript file
 **/

/**
 * Elgg-markdown_wiki edit initialization
 *
 * @return void
 */
elgg.provide('elgg.markdown_wiki.edit');

elgg.markdown_wiki.edit.init = function() {
	// allow plugins to cancel event
	var options = { trigger:false };
	options = elgg.trigger_hook('init', 'markdown_wiki.edit.init', null, options);

	if (!options.trigger) {
		$(document).ready(function() {
			
			$('.previewPaneWrapper .elgg-input-dropdown').change(function() {
				$('.pane').addClass('hidden');
				$('#'+$(this).val()).removeClass('hidden');
			});
		
			var textarea = $('textarea.elgg-input-markdown'),
				previewPane = $('#previewPane'),
				outputPane = $('#outputPane');
	
			// Continue only if the `textarea` is found
			if (textarea) {
				var converter = new Showdown.converter().makeHtml;
				textarea.keyup(function() {
					var text = converter(textarea.val());
					outputPane.val(text);;
					previewPane.html(text);
					
					// resize textarea
					$('textarea.elgg-input-markdown, #outputPane, #syntaxPane').innerHeight(previewPane.innerHeight() + 10 + 2); // padding (cannot set to textarea) + border
				}).trigger('keyup');
			}
			
			
		});
	}
}
elgg.register_hook_handler('init', 'system', elgg.markdown_wiki.edit.init);

// End of edit js for elgg-markdown_wiki plugin

