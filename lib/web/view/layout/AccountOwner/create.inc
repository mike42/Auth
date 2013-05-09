<script type="text/javascript">
	var sel = '';

	$(function() {
		$("#owner-firstname").focus();
	    $('#sel-domain').change(function() {
	    	if(sel != $('#sel-domain').val()) {
		    	if(sel != '') {
					$('#domain-' + sel).hide();
		    	}
				sel = $('#sel-domain').val();
		    	if(sel != '') {
					$('#domain-' + sel).show();
		    	}
	    	}
	     });
	});
</script>

<div class="container">
	<div class="row">
		<div class="span4">
			<div class="well">
				<b>Create user</b>
				<p>Users are people who control multiple accounts. Auth will track
					these accounts, and set their password and permissions at the same
					time.</p>
				<p>The 'domain' feature allows you to include some users only on
					some services. This simply defines where accounts are (or aren't)
					created. For some services, such as email, the domain will affect
					the user's email address. Other services, such as active directory,
					only really support one domain, and this is taken into account
					whern displaying the domain of an account below.</p>
			</div>
		</div>
		<div class="span8">
			<div class="alert alert-info">
				<h3>Create user</h3>
			</div>

			<?php 
			if(isset($data['message'])) {
				echo "<div class=\"alert\">".web::escapeHTML($data['message']) . "</div>";
			}
			?>
			<div>
				<form
					action="<?php echo web::constructURL("AccountOwner", "create", array((int)$data['Ou'] -> ou_id), 'html'); ?>"
					method="post">
					<table class="table-bordered"
						style="margin-left: auto; margin-right: auto">
						<tr>
							<th>Organizational unit:</th>
							<td><input type="text" disabled
								value="<?php echo Web::escapeHTML($data['Ou'] -> ou_name) ?>" />
							</td>
						</tr>
						<tr>
							<th>First name:</th>
							<td><input type="text" name="owner_firstname"
								id="owner-firstname"
								value="<?php if(isset($_POST['owner_firstname'])) { echo web::escapeHTML($_POST['owner_firstname']); } ?>" />
							</td>
						</tr>
						<tr>
							<th>Surname:</th>
							<td><input type="text" name="owner_surname" id="owner-surname"
								value="<?php if(isset($_POST['owner_surname'])) { echo web::escapeHTML($_POST['owner_surname']); } ?>" />
							</td>
						</tr>
						<tr>
							<th>Username:</th>
							<td><input type="text" name="account_login" id="account-login"
								value="<?php if(isset($_POST['account_login'])) { echo web::escapeHTML($_POST['account_login']); } ?>" />
							</td>
						</tr>
						<tr>
							<th>Accounts for:</th>
							<td><?php
							/* Domain select box */
							echo "<select id=\"sel-domain\">\n" .
									"<option value=\"\">(select domain)</option>";
							foreach($data['ListDomain'] as $domain) {
								echo "<option value=\"".web::escapeHTML($domain -> domain_id)."\">" . web::escapeHTML($domain -> domain_name) . "</option>\n";
							}
							echo "</select>";

							foreach($data['ListDomain'] as $domain) {
								echo "<div style=\"display: none\" id=\"domain-".web::escapeHTML($domain -> domain_id)."\">";
								foreach($domain -> list_ListServiceDomain as $sd) {
									$s = $sd -> Service; // Shorthand
									echo "<div><input type=\"checkbox\" name=\"" . web::escapeHTML($s -> service_id) . "\" /> " .
											web::escapeHTML($s -> service_name);
									echo "</div>";
								}
								echo "</div>";
							}

							?>
							</td>
						</tr>
						<tr>
							<td colspan=2 style="text-align: right"><a class="btn">Cancel</a>
								<input class="btn" type="submit" value="Create">
							</td>
						</tr>
					</table>
				</form>
			</div>
		</div>
	</div>
</div>
