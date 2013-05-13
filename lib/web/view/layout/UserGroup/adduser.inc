<?php include(dirname(__FILE__)."/../snippet/account_selector.php"); ?>
<div class="container">
	<div class="row">
		<div class="span4">
			<div class="well">
				<b>What happens when I add a user?</b>
				<p>Adding a user here will give them the permissions of the group, and will cause them to receive group emails</p>
			</div>
		</div>
		<div class="span8">
			<div class="alert alert-info">
				<h3>
					Add user to
					<?php echo "\"" . web::escapeHTML($data['UserGroup'] -> group_name) . "\""; ?>
				</h3>
			</div>
			<?php 
			if(isset($data['message'])) {
				echo "<div class=\"alert\">".web::escapeHTML($data['message']) . "</div>";
			}
			?>

			<div class="well">
				<form style="text-align: center; margin: 3em;"
					action="<?php echo web::constructURL("UserGroup", "adduser", array($data['UserGroup'] -> group_id), "html"); ?>"
					method="post" id="accountselect">
					<p>Enter the name of person to add:</p>
					<div class="input-append">
						<input type="hidden" name="owner_id" id="owner_id" /> <input
							type="text" name="uname" id="uname" class="span2" /> <a
							class="btn"
							href="<?php echo web::constructURL("UserGroup", "view", array($data['UserGroup'] -> group_id), "html"); ?>" />Cancel</a>
						<button class="btn" type="submit" />
						Add
						</button>
					</div>
				</form>
			</div>

		</div>
	</div>
</div>

<script type="text/javascript">
	// Set focus on group box
	$("#uname").focus();
</script>