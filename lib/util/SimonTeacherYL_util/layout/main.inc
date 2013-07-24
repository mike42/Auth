<script>
	function updateNow() {
		$("#action").val('update');		
		$("#frmUpdate").submit();
	}

	function check() {
		$("#action").val('check');		
		$("#frmUpdate").submit();
	}
	
	function notifySelect() {
		$("#notifySelect").submit();
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
			} else if(isset($data['run'])) {
				echo "<div class=\"alert\">Group membership summary: <ul>";
				foreach($data['run']['yl'] as $level => $memberlist) {
					echo "<li>".mkgrpname($level)."<ul>";
					foreach($memberlist as $alias => $account) {
						echo "<li>".web::escapeHTML($alias) . "</li>\n";
					}
					echo "</ul></li>\n";
				}
				
				if(count($data['run']['unamecheck']['fail']) > 0) {
					echo "<li>Unrecognised usernames:<ul>";
					foreach($data['run']['unamecheck']['fail'] as $uname => $true) {
						echo "<li>".web::escapeHTML($uname)."</li>";
					}
					echo "</ul></li>";
				}
				echo "</ul></div>";
			}
			?>
			<div class="well">
				<h3>Update groups</h3>
				<p>This will log in, and produce a list of changes. Use the 'test' feature first to verify that the utility is installed and working correctly. If you run the update, then changes are visible in the ActionQueue</p>
				<?php
				echo "<form id=\"frmUpdate\" method=\"post\" action=\"".web::constructURL("Utility", "SimonTeacherYL", array(''), 'html')."\">" .
					"<input type=\"hidden\" name=\"action\" id=\"action\" value=\"\" />"; ?>
				<table>
					<tr>
						<th>Semester:</th>
						<td>
							<select name="semester">
								<option>(please select)</option>
								<option value=1>Semester 1</option>
								<option value=2>Semester 2</option>
							</select>
						</td>
					</tr>
					<tr>
						<th>Get info</th>
						<td><a class="btn" href="#" onClick="check();">Test</a></td>
					</tr>
					<tr>
						<th>Run</th>
						<td><a class="btn" href="#" onClick="updateNow();">Update</a></td>
					</tr>				
				</table>
				<?php echo "</form>"; ?>
			</div>
			
			<div class="well">
				<h3>Notification</h3>
				<?php  echo "<form id=\"notifySelect\" method=\"post\" action=\"".web::constructURL("Utility", "SimonTeacherYL", array(''), 'html')."\">" .
				"<input type=\"hidden\" name=\"action\" value=\"notifySelect\" />"; ?>
				<p>This will send emails to people to tell them their timetable and email groups.</p>
				
				<table>
					<tr>
						<th>Semester:</th>
						<td>
							<select name="semester">
								<option>(please select)</option>
								<option value=1>Semester 1</option>
								<option value=2>Semester 2</option>
							</select>
						</td>
					</tr>
					<tr>
						<td colspan="2">
							<a class="btn" href="#" onClick="notifySelect();">Select users to notify</a>
						</td>
					</tr>
				</table>
				<?php echo "</form>"; ?>
			</div>
		</div>
	</div>
</div>