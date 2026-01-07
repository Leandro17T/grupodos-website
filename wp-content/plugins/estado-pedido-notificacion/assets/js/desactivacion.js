window.onload = function(){

	jQuery('a[href^="plugins.php?action=deactivate&plugin=estado-pedido-notificacion"]').attr('onclick', 'if (!desactivacion_estados_pedido()) return false;');
	}

function desactivacion_estados_pedido () {

	var meta  = document.getElementById('meta-boton-desactivar-plugin')
	var datos = [];

	datos['pregunta'] = meta.getAttribute('data-pregunta');
	datos['frase']    = meta.getAttribute('data-frase');
	datos['si']       = meta.getAttribute('data-si');
	datos['no']       = meta.getAttribute('data-no');
	datos['exito']    = meta.getAttribute('data-exito');
	datos['completo'] = meta.getAttribute('data-completo');

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
				var destino = jQuery('a[href^="plugins.php?action=deactivate&plugin=estado-pedido-notificacion"]').attr('href');
				location.href = destino;
				}

			else
				return false;
			}
		);
	}