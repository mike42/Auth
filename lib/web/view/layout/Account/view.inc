<script type="text/javascript">
	function deleteAccount() {
		if(confirm('Are you sure you want to delete this account?')) {
			$("#deleteAccount").submit();
		}
	}

	function enableAccount() {
		$("#enableAccount").submit();
	}

	function disableAccount() {
		if(confirm('Are you sure you want to disable this account? The user will not be able to log in')) {
			$("#disableAccount").submit();
		}
	}
</script>

<div class="container">
	<div class="row">
		<div class="span4">
			<div class="well">
				<?php echo "<a href=\"" . web::constructURL("AccountOwner", "view", array($data['Account'] -> owner_id), "html"). "\">" .
						"&larr; Back to '" . web::escapeHTML($data['Account'] -> AccountOwner -> owner_firstname . ' ' . $data['Account'] -> AccountOwner -> owner_surname) . "'</a>";?>
			</div>
		</div>
		<div class="span8">
			<div class="alert alert-info">
				<h3>
					<?php echo web::escapeHTML($data['Account'] -> account_login . " on " . $data['Account'] -> Service -> service_name); ?>
				</h3>
			</div>

			<?php 
			if(isset($data['message'])) {
				echo "<div class=\"alert\">".web::escapeHTML($data['message']) . "</div>";
			} ?>

			<table class='table table-bordered'>
				<tr>
					<th>Login</th>
					<td><?php echo web::escapeHTML($data['Account'] -> account_login); ?>
					</td>
				</tr>
				<tr>
					<th>Service</th>
					<td><?php echo web::escapeHTML($data['Account'] -> Service -> service_name); ?>
					</td>
				</tr>
				<tr>
					<th>Domain</th>
					<td><?php echo web::escapeHTML($data['Account'] -> ListDomain -> domain_name); ?>
					</td>
				</tr>
				<tr>
					<th>Owner</th>
					<td><?php echo "<a href=\"" . web::constructURL("AccountOwner", "view", array($data['Account'] -> owner_id), "html"). "\">" .
							web::escapeHTML($data['Account'] -> AccountOwner -> owner_firstname . ' ' . $data['Account'] -> AccountOwner -> owner_surname) . "</a>"; ?>
					</td>
				</tr>
				<tr>
					<th>Status</th>
					<td><?php
					if($data['Account'] -> account_enabled == 1) {
						echo "Enabled";
						echo "<div style=\"float:right\">";
						echo "<input type=\"button\" class='btn' value=\"Disable account\" onClick=\"disableAccount()\" />";
						echo "<div>";
					} else {
						echo "Disabled";
						echo "<div style=\"float:right\">";
						echo "<input type=\"button\" class='btn' value=\"Enable account\" onClick=\"enableAccount()\" />";
						echo "<div>";
					}
					?></td>
				</tr>
			</table>

			<div class='well'>
				<h3>Account options</h3>
				<?php 
				echo "<a class='btn' href=\"" . web::constructURL("Account", "rename", array($data['Account'] -> account_id), "html") . "\">Change login name</a>";
				?>
			</div>

			<div class='alert alert-danger'>
				<h3>Danger zone</h3>
				<p>Deleting an account cannot be undone.</p>
				<input type="button" class='btn btn-danger' value="Delete Account"
					onClick="deleteAccount()" />
			</div>
		</div>
	</div>
</div>
<?php
echo "<form style=\"display: none\" id=\"deleteAccount\" " .
		"action=\"" . web::constructURL("Account", "view", array($data['Account'] -> account_id), 'html') ."\" " .
		"method=\"post\">\n " .
		"<input type=\"hidden\" name=\"action\" value=\"delete\">" .
		"</form>";
echo "<form style=\"display: none\" id=\"enableAccount\" " .
		"action=\"" . web::constructURL("Account", "view", array($data['Account'] -> account_id), 'html') ."\" " .
		"method=\"post\">\n " .
		"<input type=\"hidden\" name=\"action\" value=\"enable\">" .
		"</form>";
echo "<form style=\"display: none\" id=\"disableAccount\" " .
		"action=\"" . web::constructURL("Account", "view", array($data['Account'] -> account_id), 'html') ."\" " .
		"method=\"post\">\n " .
		"<input type=\"hidden\" name=\"action\" value=\"disable\">" .
		"</form>";
