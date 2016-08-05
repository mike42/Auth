<?php
use Auth\web\Web;
?>
<?php include(dirname(__FILE__)."/../snippet/group_selector.php"); ?>
<div class="container">
	<div class="row">
		<div class="span4">
			<div class="well">
				<b>What happens when I add a sub-group?</b>
				<p>When you add a group here, everybody in that group will receive
					emails and inherit the permissions associated with the parent
					group.</p>
			</div>
		</div>
		<div class="span8">
			<div class="alert alert-info">
				<h3>
					Add group to
					<?php echo "\"" . Web::escapeHTML($data['UserGroup'] -> group_name) . "\""; ?>
				</h3>
			</div>
			<?php 
			if(isset($data['message'])) {
				echo "<div class=\"alert\">".Web::escapeHTML($data['message']) . "</div>";
			}
			?>

			<div class="well">
				<form style="text-align: center; margin: 3em;"
					action="<?php echo Web::constructURL("UserGroup", "addchild", array($data['UserGroup'] -> group_id), "html"); ?>"
					method="post" id="groupselect">
					<p>Enter the name of the group to add:</p>
					<div class="input-append">
						<input type="hidden" name="group_cn" id="group_cn" /> <input
							type="text" name="gname" id="gname" class="span2" /> <a
							class="btn"
							href="<?php echo Web::constructURL("UserGroup", "view", array($data['UserGroup'] -> group_id), "html"); ?>">Cancel</a>
						<button class="btn" type="submit">
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
	$("#gname").focus();
</script>

