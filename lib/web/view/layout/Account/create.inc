<script type="text/javascript">
	var sel = '';
	function genUname() {
		var uname = $('#owner-surname').val();
		if($('#owner-firstname').val() != '') {
			uname = $('#owner-firstname').val().substr(0 , 1) + uname;
		}
		$('#account-login').val(normaliseName(uname));
	}
	
	function boxCheck() {
    	if(sel != $('#sel-domain').val()) {
	    	if(sel != '') {
				$('#domain-' + sel).hide();
				$('#services-' + sel).val(0);
	    	}
			sel = $('#sel-domain').val();
	    	if(sel != '') {
				$('#domain-' + sel).show();
	    	}
    	}
	}

	$(function() {
		$("#account-login").focus();
	    $('#sel-domain').change(function() {
			boxCheck();
	     });
	    $('#account-login').focusin(function() {
	    	if($('#account-login').val() == '') {
				genUname();
	    	}
	     });
	    $('#account-login').dblclick(function() {
			genUname();
	     });
	     boxCheck();
	});
</script>

<div class="container">
	<div class="row">
		<div class="span4">
			<div class="well">
				<b>Create account</b>
				<p>Adding a new account will give the user access to another service.</p>
				<p>After creating the account, set the user's password so that they can log in.</p>
			</div>
		</div>
		<div class="span8">
			<div class="alert alert-info">
				<h3>Create account</h3>
			</div>

			<?php 
			if(isset($data['message'])) {
				echo "<div class=\"alert\">".web::escapeHTML($data['message']) . "</div>";
			}
			?>
			<div>
				<form
					action="<?php echo web::constructURL("Account", "create", array((int)$data['AccountOwner'] -> owner_id), 'html'); ?>"
					method="post">
					<table class="table-bordered"
						style="margin-left: auto; margin-right: auto">
						<tr>
							<th>User:</th>
							<td><input type="text" disabled
								value="<?php echo Web::escapeHTML($data['AccountOwner'] -> owner_firstname . " " . $data['AccountOwner'] -> owner_surname) ?>" />
							</td>
						</tr>
						<tr>
							<th>Login name:</th>
							<td><input type="text" name="account_login" id="account-login"
								value="<?php if(isset($_POST['account_login'])) { echo web::escapeHTML($_POST['account_login']); } ?>" />
							</td>
						</tr>
						<tr>
							<th>Domain:</th>
							<td><?php
							/* Domain select box */
							echo "<select id=\"sel-domain\" name=\"domain_id\" >\n" .
									"<option value=\"\">(select domain)</option>";
							foreach($data['ListDomain'] as $domain) {
								$selected = (isset($_POST['domain_id']) && $_POST['domain_id'] == $domain -> domain_id) ? " selected": "";
								echo "<option value=\"".web::escapeHTML($domain -> domain_id)."\"$selected>" . web::escapeHTML($domain -> domain_name) . "</option>\n";
							}
							echo "</select>";

							foreach($data['ListDomain'] as $domain) {
								echo "<div style=\"display: none\" id=\"domain-".web::escapeHTML($domain -> domain_id)."\">";
								echo "<select name=\"services-".web::escapeHTML($domain -> domain_id)."\">";
								echo "<option value=\"\">(select a service)</option>\n";
								foreach($domain -> list_ListServiceDomain as $sd) {
									$s = $sd -> Service; // Shorthand
									$selected = "";
									if(isset($_POST['domain_id']) && isset($_POST['services-' . $sd -> domain_id]) && $_POST['domain_id'] == $sd -> domain_id && $_POST['services-' . $sd -> domain_id] == $sd -> service_id) {
										$selected = " selected";
									}
									echo "<option value=\"" . web::escapeHTML($s -> service_id) . "\"$selected> " .
											web::escapeHTML($s -> service_name);
									echo "</option>\n";
								}
								echo "</select></div>";
							}
							?>
							</td>
						</tr>
						<tr>
							<td colspan=2 style="text-align: right">
								<?php echo "<a class=\"btn\" href=\"" . Web::constructURL("AccountOwner", "view", array((int)$data['AccountOwner'] -> owner_id), 'html') . "\">Cancel</a>"; ?>
								<input class="btn" type="submit" value="Create">
							</td>
						</tr>
					</table>
				</form>
			</div>
		</div>
	</div>
</div>