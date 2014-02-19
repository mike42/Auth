<!--  User account selector -->
<script type="text/javascript">
$(function() {
	$( "#uname" ).autocomplete({
		source: function( request, response ) {
			$.ajax({
				type: "POST",
				url: "/assistant/search.json",
				dataType: "json",
				data: {
					action: 'search',
					term: request.term
				},
				success: function( data ) {
					response( $.map( data, function( item ) {
						return {
							label: item.account_login + ' - ' +  item["AccountOwner"].owner_surname + ', ' + item["AccountOwner"].owner_firstname,
							value: item.label,
							id: item.owner_id
						}
					}));
				}
			});
		},
		minLength: 2,
		select: function( event, ui ) {
			if(ui.item) {
				$( "#owner_id" ).val(ui.item.id);
				$( "#accountselect" ).submit();
			}
		},
		open: function() {
			$( this ).removeClass( "ui-corner-all" ).addClass( "ui-corner-top" );
		},
		close: function() {
			$( this ).removeClass( "ui-corner-top" ).addClass( "ui-corner-all" );
		}
	});
});
</script>
<!--  End of user account selector -->