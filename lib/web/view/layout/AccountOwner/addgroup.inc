<?php
use Auth\web\Web;
?>
<?php include(dirname(__FILE__)."/../snippet/group_selector.php"); ?>
<div class="container">
	<div class="row">
		<div class="span4">
			<div class="well">
				<b>What happens when I add a group here?</b>
				<p>If you add a user here, then they will be given the permissions
					of the group, and receive emails which are sent to the group.</p>
			</div>
		</div>
		<div class="span8">
			<div class="alert alert-info">
				<h3>
					Add
					<?php echo "\"" . Web::escapeHTML($data['AccountOwner'] -> owner_firstname . " " . $data['AccountOwner'] -> owner_surname) . "\""; ?>
					to group
				</h3>
			</div>
			<?php 
			if(isset($data['message'])) {
				echo "<div class=\"alert\">".Web::escapeHTML($data['message']) . "</div>";
			}
			?>

			<div class="well">
				<form style="text-align: center; margin: 3em;"
					action="<?php echo Web::constructURL("AccountOwner", "addgroup", array($data['AccountOwner'] -> owner_id), "html"); ?>"
					method="post" id="groupselect">
					<p>Enter the name of the group:</p>
					<div class="input-append">
						<input type="hidden" name="group_cn" id="group_cn" /> <input
							type="text" name="gname" id="gname" class="span2" />

						<?php echo "<a class=\"btn\" href=\"" . Web::constructURL("AccountOwner", "view", array($data['AccountOwner'] -> owner_id), "html") . "\" />Cancel</a>"; ?>
						<button class="btn" type="submit">Add</button>
					</div>
				</form>
			</div>

		</div>
	</div>
</div>

<script type="text/javascript">
	// Set focus on group box
	$("#gname").focus();
</script>

