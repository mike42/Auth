<script type="text/javascript">
	$(function() {
		$("#owner-firstname").focus();
	});
</script>

<div class="container">
	<div class="row">
		<div class="span4">
			<div class="well">
				<b>Renaming a user</b>
				
				<p>Changing a user's name will update their full-name, as it is displayed in different places.</p>
				
				<p>Changing the user's login for a specific account (much more disruptive) is done by clicking on the account.</p>
			</div>
		</div>
		<div class="span8">
			<div class="alert alert-info">
				<h3>Rename User</h3>
			</div>
			<?php 
				if(isset($data['message'])) {
					echo "<div class=\"alert\">".web::escapeHTML($data['message']) . "</div>";
				}
			?>
			<div>
				<form action="<?php echo web::constructURL("AccountOwner", "rename", array((int)$data['AccountOwner'] -> owner_id), 'html'); ?>" method="post">
					<table class="table-bordered" style="margin-left: auto; margin-right: auto">
						<tr>
							<th>
								First name:
							</th>
							<td>
								<input type="text" name="owner_firstname" id="owner-firstname" value="<?php echo web::escapeHTML($data['AccountOwner'] -> owner_firstname); ?>" />
							</td>
						</tr>
						<tr>
							<th>
								Surname:
							</th>
							<td>
								<input type="text" name="owner_surname" id="owner-surname" value="<?php echo web::escapeHTML($data['AccountOwner'] -> owner_surname); ?>" />
							</td>
						</tr>

						<tr>
							<td colspan=2 style="text-align: right">
								<a class="btn" href="<?php echo web::constructURL("AccountOwner", "view", array((int)$data['AccountOwner'] -> owner_id), 'html'); ?>">Cancel</a>
								<input class="btn" type="submit" value="Rename">
							</td>
						</tr>
					</table>
				</form>
			</div>
		</div>
	</div>
</div>