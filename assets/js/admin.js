jQuery(function($){
	var ajax  = bigSitemapAjax.url;
	var nonce = bigSitemapAjax.nonce;

	function showMsg(msg, type) {
		type = type || 'updated';
		$('#big-sitemap-message')
			.removeClass('updated error notice-success notice-error')
			.addClass('notice ' + type)
			.html('<p>' + msg + '</p>')
			.show();
		setTimeout(function(){ $('#big-sitemap-message').fadeOut(); }, 4000);
	}

	// Single Dashboard button: saves content types + type defaults + generates
	$('#big-sitemap-generate').on('click', function(){
		var btn = $(this).prop('disabled', true).text('Generating...');

		var types = [];
		$('.big-sitemap-type-check:checked').each(function(){ types.push($(this).val()); });

		var defaults = {};
		$('select[name^="type_defaults["]').each(function(){
			var match = $(this).attr('name').match(/type_defaults\[(\w+)\]\[(\w+)\]/);
			if (match) {
				if (!defaults[match[1]]) defaults[match[1]] = {};
				defaults[match[1]][match[2]] = $(this).val();
			}
		});

		$.post(ajax, {
			action:        'big_sitemap_save_type_defaults',
			nonce:         nonce,
			content_types: types,
			type_defaults: defaults
		}, function(res){
			btn.prop('disabled', false).text('🔄 Save & Generate Sitemap');
			if (res.success) showMsg(res.data.message, 'notice-success');
			else showMsg(res.data, 'notice-error');
		});
	});

	// Save URL overrides (View & Edit tab)
	$('#big-sitemap-save-urls').on('click', function(){
		var btn = $(this).prop('disabled', true).text('Saving...');
		var overrides = {};
		$('#big-sitemap-url-table tbody tr').each(function(){
			var loc = $(this).data('loc');
			overrides[loc] = {
				priority:   $(this).find('.priority-select').val(),
				changefreq: $(this).find('.changefreq-select').val(),
				exclude:    $(this).find('.exclude-check').is(':checked')
			};
		});
		$.post(ajax, {
			action:    'big_sitemap_save_overrides',
			nonce:     nonce,
			overrides: JSON.stringify(overrides)
		}, function(res){
			btn.prop('disabled', false).text('Save Changes & Regenerate');
			if (res.success) showMsg(res.data.message, 'notice-success');
			else showMsg(res.data, 'notice-error');
		});
	});

	// Re-include excluded URLs
	$('#big-sitemap-reinclude-urls').on('click', function(){
		var btn = $(this).prop('disabled', true).text('Saving...');
		var overrides = {};
		$('#big-sitemap-excluded-table tbody tr').each(function(){
			var loc       = $(this).data('loc');
			var reinclude = $(this).find('.reinclude-check').is(':checked');
			overrides[loc] = { exclude: !reinclude };
		});
		$.post(ajax, {
			action:    'big_sitemap_save_overrides',
			nonce:     nonce,
			overrides: JSON.stringify(overrides)
		}, function(res){
			btn.prop('disabled', false).text('Re-include Selected & Regenerate');
			if (res.success) {
				showMsg(res.data.message, 'notice-success');
				setTimeout(function(){ location.reload(); }, 1500);
			} else {
				showMsg(res.data, 'notice-error');
			}
		});
	});

	// Save Raw XML
	$('#big-sitemap-save-xml').on('click', function(){
		var btn = $(this).prop('disabled', true).text('Saving...');
		$.post(ajax, {
			action: 'big_sitemap_save_xml',
			nonce:  nonce,
			xml:    $('#big-sitemap-xml-editor').val()
		}, function(res){
			btn.prop('disabled', false).text('Save XML to sitemap.xml');
			if (res.success) showMsg(res.data.message, 'notice-success');
			else showMsg(res.data, 'notice-error');
		});
	});
});
