<?php
use Auth\web\Web;
?>
<script>
	function updateNow() {
		$("#action").val('update');
		$("#frmUpdate").submit();
	}

	function check() {
		$("#action").val('check');
		$("#frmUpdate").submit();
	}
	
	function summary() {
		$("#action").val('summary');
		$("#frmUpdate").submit();
	}

</script>
<div class="container">
	<div class="row">
		<div class="span4">
			<div class="well">
				<p>
					<b>What does this do?</b>
				</p>
				<p>This utility logs on to SIMON and checks timetables. All teachers and students are then sorted into email groups. Their email address must be correct on SIMON for this to work.</p>
			</div>
		</div>
		<div class="span8">
			<div class="alert alert-info">
				<h3>Automatic mail groups</h3>
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
			
			if(isset($data['summary'])) {
				echo "<div class=\"panel\">" .
					"<h4>Summary of groups</h4>";
				echo "<ul>";
				foreach($data['summary'] as $group) {
					$email = $group -> group_cn . "@" . SimonTeacherYL_util::getHostname($group -> group_domain);
					echo "<li>" . web::escapeHTML($group -> group_name) . " (<a href=\"mailto:$email\">" . $email . "</a>)";
					if(count($group -> children) != 0) {
						echo "<ul>";
						foreach($group -> children as $subgroup) {
							$email = $subgroup -> group_cn . "@" . SimonTeacherYL_util::getHostname($subgroup -> group_domain);
							echo "<li>" . web::escapeHTML($subgroup -> group_name) . " (<a href=\"mailto:$email\">" . $email . "</a>)";
						}
						echo "</ul>";
					}
					echo "</li>";
				}
				echo "</ul>";
				echo "</div>";
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
								<?php 
								foreach($data['semester'] as $semester) {
									$selected = "";
									if((isset($_POST['semester']) && (int)$_POST['semester'] == $semester['FileSeq']) || (!isset($_POST['semester']) && $semester['FileActiveFlag']) == '1') {
										$selected  = " selected";
									}
									echo "<option value=\"" . (int)$semester['FileSeq'] . "\"$selected>" . web::escapeHTML($semester['FileYear'] . ", Semeser " . $semester['FileSemester']) . "</option>";
								}
								?>
							</select>
						</td>
					</tr>
					<tr>
						<th>Detect changes</th>
						<td><a class="btn btn-primary" href="#" onClick="check();">Test</a></td>
					</tr>
					<tr>
						<th>Apply changes</th>
						<td><a class="btn btn-warning" href="#" onClick="updateNow();">Update</a><label><input type="checkbox" checked name="limit" /> 100 changes only</label></td>
					</tr>		
					<tr>
						<th>Get info</th>
						<td><a class="btn btn-info" href="#" onClick="summary();">Summary</a></td>
					</tr>	
				</table>
				<?php echo "</form>"; ?>
			</div>
			
			
		</div>
	</div>
</div>
