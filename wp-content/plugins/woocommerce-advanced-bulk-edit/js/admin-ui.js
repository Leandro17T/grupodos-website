
var contentCheckForUpdateWrapper = document.getElementById('contentCheckForUpdateWrapper');

var btnCheckForUpdate = document.getElementById('btnCheckForUpdate');
btnCheckForUpdate.addEventListener('click', (e) => {
	e.preventDefault();

	var contentCheckForUpdate = document.getElementById('contentCheckForUpdate');

	if (contentCheckForUpdateWrapper) {
		wcabehelper.toggle(contentCheckForUpdateWrapper);
	}

	var params = {
		action: 'wpmelon_wcabe',
		type: 'checkforupdate',
		nonce: W3ExABE.nonce

	};

	if (wcabehelper.isToggleInDisplayState(contentCheckForUpdateWrapper)) {

		params = new FormData();
		params.append( 'action', 'wpmelon_wcabe' );
		params.append( 'type', 'checkforupdate' );
		params.append( 'nonce', W3ExABE.nonce );
		fetch(W3ExABE.ajaxurl, {
			method: 'POST',
			body: params
		})
			.then(response => response.json())
			.then(data => {
				contentCheckForUpdate.innerHTML = data.message;
			})
			.catch(err => console.log('Request Failed', err));
	}

});

var btnCheckForUpdateContentClose = document.getElementById('btnCheckForUpdateClose');
btnCheckForUpdateContentClose.addEventListener('click', (e) => {
	if (contentCheckForUpdateWrapper) {
		wcabehelper.toggle(contentCheckForUpdateWrapper);
	}
});

$('body').on('click','#attrsCheckAll, #attrsUncheckAll',function(e)
{
	e.preventDefault();

	var dontDisplayTypes = ['[product_type]']; // An array of types not allowed to display All|None check buttons
	if (!$(this).parent().next().find('input').prop('name')) {
		return;
	}
	var shouldDisplayAllNoneButtons = true;
	var it = $(this);
	dontDisplayTypes.forEach(function (elem, index) {
		if (it.parent().next().find('input').prop('name').toString().indexOf('[product_type]') >= 0) {
			shouldDisplayAllNoneButtons = false;
		}
	});

	if (shouldDisplayAllNoneButtons) {
		if (e.target.id == 'attrsCheckAll') {
			$(this).parent().next().find('input').prop('checked', true);
		} else if (e.target.id == 'attrsUncheckAll') {
			$(this).parent().next().find('input').prop('checked', false);
		}
	}
});
