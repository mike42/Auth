<script type="text/javascript">
	function deleteAo() {
		if(confirm('Are you sure you want to delete this user? This will permanently delete all of their accounts.')) {
			$("#deleteAo").submit();
		}
	}

	function delGroup(group_id) {
		$("#delGroup-group_id").val(group_id);
		$("#delGroup").submit();
	}
</script>

<div class="container">
	<div class="row">
		<div class="span4">
			<div class="well">
				<?php echo "<a href=\"" . web::constructURL("Ou", "view", array($data['AccountOwner'] -> Ou -> ou_id), "html"). "\">" . 
						"&larr; Back to '" . web::escapeHTML($data['AccountOwner'] -> Ou -> ou_name) . "' organizational unit</a>";?>
			</div>
		</div>
		<div class="span8">
			<div class="alert alert-info">
				<h3>
					<?php echo web::escapeHTML($data['AccountOwner'] -> owner_firstname . " ". $data['AccountOwner'] -> owner_surname); ?>
				</h3>
			</div>
			<?php
			/* Displaying groups */
			echo "<table class='table table-bordered'>" .
					"<thead>\n" .
					"<tr>\n" .
					"<th colspan='1'>Member Of" .
					"<div class='btn-group pull-right'>\n" .
					"<a class='btn' href=\"".web::constructURL("AccountOwner", "addgroup", array($data['AccountOwner'] -> owner_id), "html")."\">Add to Group</a>\n" .
					"</div></th>\n" .
					"</tr>\n" .
					"</thead>\n";
			foreach($data['AccountOwner'] -> list_OwnerUserGroup as $oug) {
				echo "<tr><td>" .
						"<div style=\"float: right\"><a href=\"javascript: void(null)\" onClick=\"delGroup(" . (int)$oug -> group_id . ");\"><img src=\"/img/delete.png\" /></a></div>" .
						"<a href=\"".web::constructURL("UserGroup", "view", array($oug -> UserGroup -> group_id), "html")."\">" .
						web::escapeHTML($oug -> UserGroup -> group_name . " (" . $oug -> UserGroup -> group_cn . ")") . "</a>" .
						"</td></td>";
			}
			echo "</table>";
			?>

			<?php
			/* Displaying accounts */
			echo "<table class='table table-bordered'>" .
					"<thead>\n" .
					"<tr>\n" .
					"<th colspan='3'>Accounts" .
					"<div class='btn-group pull-right'>\n" .
					"<a class='btn' href=\"".web::constructURL("Account", "create", array($data['AccountOwner'] -> owner_id), "html")."\">Create account</a>\n" .
					"</div></th>\n" .
					"</tr>\n" .
					"<th>Login</th><th>Service</th><th>Domain</th>" .
					"</thead>\n";
			foreach($data['AccountOwner'] -> list_Account as $account) {
				echo "<tr>" .
						"<td><a href=\"" . web::constructURL("Account", "view", array($account -> account_id), "html") . "\">" . web::escapeHTML($account -> account_login) . "</a></td>" .
						"<td>" . web::escapeHTML($account -> Service -> service_name) . "</td>" .
						"<td>" . web::escapeHTML($account -> account_domain) . "</td>" .
						"</td>";
			}
			echo "</table>";

			echo "<div class='well'>" .
					"<h3>Credentials</h3>" .
					"<a class='btn' href=\"".web::constructURL("AccountOwner", "pwreset", array($data['AccountOwner'] -> owner_id), "html")."\">Reset Password</a>" .
					"</div>";

			echo "<div class='well'>" .
					"<h3>User options</h3>" .
					"<p>Moving or renaming accounts can seriously break some things (eg, windows logons). Use with caution.</p>" .
					"<a class='btn' href=\"" . web::constructURL("AccountOwner", "rename", array($data['AccountOwner'] -> owner_id), "html") . "\">Rename User</a>" .
					"<a class='btn' href=\"" . web::constructURL("AccountOwner", "move", array($data['AccountOwner'] -> owner_id), "html") . "\">Move user</a>" .
					"</div>";

			echo "<div class='alert alert-danger'>" .
					"<h3>Danger zone</h3>" .
					"<p>Deleting a user deletes all their accounts. It cannot be undone!</p>" .
					"<input type=\"button\" class='btn btn-danger' value=\"Delete User\" onClick=\"deleteAo()\" />" .
					"</div>";
			?>
		</div>
	</div>
</div>
<?php
echo "<form style=\"display: none\" id=\"deleteAo\" " .
		"action=\"" . web::constructURL("AccountOwner", "view", array($data['AccountOwner'] -> owner_id), 'html') ."\" " .
		"method=\"post\">\n " .
		"<input type=\"hidden\" name=\"action\" value=\"delete\">" .
		"</form>";
echo "<form style=\"display: none\" id=\"delGroup\" action=\"" . web::constructURL("AccountOwner", "view", array($data['AccountOwner'] -> owner_id), 'html') ."\" " .
		" method=\"post\">" .
		"<input type=\"hidden\" name=\"action\" value=\"delGroup\" />" .
		"<input type=\"hidden\" name=\"group_id\" id=\"delGroup-group_id\" value=\"\" />" .
		"</form>";