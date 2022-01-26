function generateLink(d) {
	if( 'success' == d.status ) {
		d.ele.attr('hidden', true);
		var that = $('#output');
		
		that.removeAttr('hidden').find('input').val(d.response.link);
	}
}

$('#create').on('click', function(e) {
	e.preventDefault();
	$('#form').removeAttr('hidden').find('input').val('');
	$('#output').attr('hidden', true)
});