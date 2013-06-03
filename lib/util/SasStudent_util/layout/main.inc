<script>
	function updateNow() {
		$("#frmUpdate").submit();
	}

	function check() {
		$("#frmCheck").submit();
	}

</script>

<div class="container">
	<div class="row">
		<div class="span4">
			<div class="well">
				<p>
					<b>What does this do?</b>
				</p>
				<p>This utility is responsible for sorting students into homerooms,
					and creating accounts automatically.</p>
				<p>It relies on correct data in the SAS student database.</p>
			</div>
		</div>
		<div class="span8">
			<div class="alert alert-info">
				<h3>SasStudent Utility</h3>
			</div>
			<?php 
			if(isset($data['message'])) {
				echo "<div class=\"alert\">".web::escapeHTML($data['message']) . "</div>";
			}
			?>
			<div class="well">
				<h3>Test run</h3>
				<p>This will look for changes in SAS, but will not modify anything.
					It is a simple way to check that the plugin is set up correctly.</p>
				<a class="btn" href="#" onClick="check();">Check</a>

			</div>

			<div class="well">
				<h3>Update local accounts</h3>
				<p>This will look for changes in SAS, and update them in the
					AuthQueue.</p>
				<a class="btn" href="#" onClick="updateNow();">Update now</a>

			</div>
		</div>
	</div>
</div>

<?php 
echo "<form id=\"frmUpdate\" method=\"post\" action=\"".web::constructURL("Utility", "SasStudent", array(''), 'html')."\" style=\"display: none;\">" .
		"<input type=\"hidden\" name=\"action\" value=\"update\" />" .
		"</form>\n";
echo "<form id=\"frmCheck\" method=\"post\" action=\"".web::constructURL("Utility", "SasStudent", array(''), 'html')."\" style=\"display: none;\">" .
		"<input type=\"hidden\" name=\"action\" value=\"check\" />" .
		"</form>\n";
?>