jQuery(document).ready(function() {

	jQuery('table.posts #the-list').sortable({

		'items' : 'tr',
		'axis' : 'y',
		'update' : function(e, ui) {

			var cpt   = getQueryVariable('post_type');
			var orden = jQuery('#the-list').sortable('serialize');
			var paged = getQueryVariable('paged');

			if ('undefined' == typeof paged)
				paged = 1;

			jQuery.ajax({

				'url' : reordena.ajax_url,
				'type' : 'POST',
				'cache' : false,
				'dataType' : 'html',
				'data' : {
					'action' : 'actualiza_orden_cpt',
					'cpt' : cpt,
					'orden' : orden,
					'paged' : paged,
					},

				'success' : function(data) {},
				'error' : function(html) {},
				});
			},
		});
	});

function getQueryVariable (variable) {

	var query = window.location.search.substring(1);
	var vars  = query.split('&');

	for (var i = 0; i < vars.length; i++) {

		var pair = vars[i].split('=');

		if (pair[0] == variable)
			return pair[1];
		}

	return false;
	}