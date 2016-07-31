<?php
use Auth\web\Web;
?>
<script type="text/javascript">
	$(function() {
		$("#account-login").focus();
	});
</script>

<div class="container">
	<div class="row">
		<div class="span4">
			<div class="well">
				<b>Changing a login name</b>
				<p>Changing a login name will affect the user's ability to log in! Not only will they have to use a different username, but it will seriously break their windows profiles, and will confuse some email clients. This should only be done if absolutely necessary</p>
				<p>To correct Windows user profiles, you will need to edit the logon name stored in the registry.</p>
			</div>
		</div>
		<div class="span8">
			<div class="alert alert-info">
				<h3>Change login name</h3>
			</div>

			<?php 
			if(isset($data['message'])) {
				echo "<div class=\"alert\">".Web::escapeHTML($data['message']) . "</div>";
			}
			?>
			<div>
				<form
					action="<?php echo Web::constructURL("Account", "rename", array((int)$data['Account'] -> account_id), 'html'); ?>"
					method="post">
					<table class="table-bordered"
						style="margin-left: auto; margin-right: auto">
						<tr>
							<th>User:</th>
							<td><input type="text" disabled
								value="<?php echo Web::escapeHTML($data['Account'] -> AccountOwner -> owner_firstname . " " . $data['Account'] -> AccountOwner -> owner_surname) ?>" />
							</td>
						</tr>
						<tr>
							<th>Login name:</th>
							<td><input type="text" name="account_login" id="account-login"
								value="<?php echo Web::escapeHTML($data['Account'] -> account_login); ?>" />
							</td>
						</tr>
						<tr>
							<td colspan=2 style="text-align: right">
								<?php echo "<a class=\"btn\" href=\"" . Web::constructURL("Account", "view", array((int)$data['Account'] -> account_id), 'html') . "\">Cancel</a>"; ?>
								<input class="btn" type="submit" value="Rename">
							</td>
						</tr>
					</table>
				</form>
			</div>
		</div>
	</div>
</div>