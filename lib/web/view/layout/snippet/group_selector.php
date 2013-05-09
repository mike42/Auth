<!--  Group selector -->
<script type="text/javascript">
$(function() {
	$( "#gname" ).autocomplete({
		source: function( request, response ) {
			$.ajax({
				type: "POST",
				url: "/admin/UserGroup/search/query.json",
				dataType: "json",
				data: {
					term: request.term
				},
				success: function( data ) {
					response( $.map( data, function( item ) {
						return {
							label: item.group_name + ' (' + item.group_cn + ')',
							value: item.label,
							id: item.group_cn
						}
					}));
				}
			});
		},
		minLength: 2,
		select: function( event, ui ) {
			if(ui.item) {
				$( "#group_cn" ).val(ui.item.id);
				$( "#groupselect" ).submit();
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
<!--  Group selector -->