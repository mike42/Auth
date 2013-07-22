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
				<p>This utility logs on to Simon and checks teacher timetables. All of the teachers are then put into year-level user groups. Their email address must be correct on SIMON for this to work.</p>
			</div>
		</div>
		<div class="span8">
			<div class="alert alert-info">
				<h3>SimonTeacherYL Utility</h3>
			</div>
			<?php 
			if(isset($data['message'])) {
				echo "<div class=\"alert\">".web::escapeHTML($data['message']) . "</div>";
			}
			?>
			<div class="well">
				<h3>Test run</h3>
				<p>This will log in, and produce a list of changes, without actually updating anything. This is a good way to test that the utility is installed and working correctly.</p>
				<a class="btn" href="#" onClick="check();">Check</a>

			</div>

			<div class="well">
				<h3>Update year-level groups</h3>
				<p>This will update the groups. Changes will be visible in the AuthQueue after this runs.</p>
				<a class="btn" href="#" onClick="updateNow();">Update now</a>

			</div>
		</div>
	</div>
</div>

<?php 
echo "<form id=\"frmUpdate\" method=\"post\" action=\"".web::constructURL("Utility", "SimonTeacherYL", array(''), 'html')."\" style=\"display: none;\">" .
		"<input type=\"hidden\" name=\"action\" value=\"update\" />" .
		"</form>\n";
echo "<form id=\"frmCheck\" method=\"post\" action=\"".web::constructURL("Utility", "SimonTeacherYL", array(''), 'html')."\" style=\"display: none;\">" .
		"<input type=\"hidden\" name=\"action\" value=\"check\" />" .
		"</form>\n";
?>
