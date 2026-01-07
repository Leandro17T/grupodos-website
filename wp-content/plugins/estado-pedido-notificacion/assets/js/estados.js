jQuery(document).ready(function() {

	//Botón para importar estados de pedido
	if (meta = document.getElementById('meta-boton-importar-estados'))
		jQuery('body.post-type-' + meta.getAttribute('data-cpt') + ' .subsubsub').prepend('<div class="boton-importar-estados"><input class="button button-primary" type="button" value="' + meta.getAttribute('data-texto') + '" onclick="importaEstados();"></div>');

	//Permitimos ordenar el CPT mediante arrastrar y soltar
	ordenaEstados();
	});

function validaLicenciaEstados () {

	if (false == (clave = document.getElementById('clave-licencia-estados-pedido').value))
		return false;

	jQuery.ajax({

		url: estados.ajax_url,
		type : 'post',
		data : {
			action : 'valida_licencia_estados_pedido',
			clave  : clave,
			},

		beforeSend : function () {

			jQuery('#boton-licencia-estados-pedido').prop('disabled', true);
			},

		success : function (data) {

			var datos = JSON.parse(data);

			if ('valid' == datos['estado']) {

				jQuery('#estados-pedido-error-licencia').css('border-left-color', '#46b450');
				jQuery('.licencia-novalida-estados-pedido').css('display', 'none');
				jQuery('#resultado-activacion-estados-pedido').text(datos['exito']);
				}

			else {

				jQuery('#boton-licencia-estados-pedido').prop('disabled', false);
				jQuery('#resultado-activacion-estados-pedido').text(datos['error']);
				}
			}
		});
	}

function importaEstados () {

	var datos = [];

	datos['pregunta'] = meta.getAttribute('data-pregunta');
	datos['frase']    = meta.getAttribute('data-frase');
	datos['si']       = meta.getAttribute('data-si');
	datos['no']       = meta.getAttribute('data-no');
	datos['exito']    = meta.getAttribute('data-exito');
	datos['import']    = meta.getAttribute('data-import');

	swal({

		title: datos['pregunta'],
		text: datos['frase'],
		type: 'warning',
		showCancelButton: true,
		confirmButtonColor: '#c42030',
		confirmButtonText: datos['si'],
		cancelButtonText: datos['no'],
		closeOnConfirm: false,
		closeOnCancel: true
		},

		function (isConfirm) {

			if (isConfirm) {

				swal(datos['exito'], datos['import'], 'success');

				jQuery.ajax({

					url: estados.ajax_url,
					type : 'POST',
					data : {
						action : 'importa_estados',
						},

					success : function (data) {

						location.reload();
						},

					error : function (html) {},
					});
				}

			else
				return false;
			}
		);
	}

function ordenaEstados () {

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

				'url' : estados.ajax_url,
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
	}

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