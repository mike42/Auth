<script>
	function toggleEverything() {
		if ($("#everything").is(':checked')) {
			$('#notify-select').find(':checkbox').prop('checked', true);
		} else {
			$('#notify-select').find(':checkbox').prop('checked', false);
		}
	}
</script>

<div class="container">
	<div class="row">
		<div class="span4">
			<div class="well">Select users to notify. Each selected user will receive a list of groups which this utility has placed them in.</div>
		</div>
		<div class="span8">
			<div class="alert alert-info">
				<h3>SimonTeacherYL Utility - Notify users</h3>
			</div>
			<div class="well">
				<? echo "<form id=\"notify-select\" method=\"post\" action=\"".web::constructURL("Utility", "SimonTeacherYL", array(''), 'html')."\">"; ?>
					<input type="hidden" name="action" value="notify" />
					<? echo "<input type=\"hidden\" name=\"semester\" value=\"".(int)$data['semester']."\" />"; ?>
					<table class="table">
						<tr>
							<th><input type="checkbox" name="everything" id="everything" onClick="toggleEverything()" checked /></th>
							<th>Email</th>
							<th>Name</th>
						</tr>
						<?php
						ksort($data['notify']['people']);
						foreach($data['notify']['people'] as $email => $person) {
							echo "<tr><td><input type=\"checkbox\" name=\"".web::escapeHTML(str_replace(".", "_", $email))."\" checked /></td>";
							echo "<td>".web::escapeHTML($email)."</td>"."<td>".web::escapeHTML($person[0] -> Preferred)." ".web::escapeHTML($person[0] -> Surname)."</td></tr>";
						}
						?>
						<tr>
							<td colspan=3>
								<?php  echo "<a class=\"btn\" href=\"".web::constructURL("Utility", "SimonTeacherYL", array(''), 'html')."\">Cancel</a>"; ?>
								<input type="submit" class="btn" value="Notify selected users" />
							</td>					
						</tr>
					</table>
				<?php echo "</form>"; ?>
			</div>
		</div>
	</div>
</div>
