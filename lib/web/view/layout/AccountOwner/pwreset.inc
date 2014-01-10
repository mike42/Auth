<script type="text/javascript">
	$(function() {
		updateView();
	})

	function updateView() {
		var pwbox = document.getElementById('pwbox');
		var inputPassword = document.getElementById( 'inputPassword' );
		if($("#pwreset input[type='radio']:checked").val() == 'auto') {
			$('#pwbox').hide();
		} else {
			$('#pwbox').show();
			$('#password1').focus();
		}
	}
</script>

<div class="container">
	<div class="row">
		<div class="span4">
			<div class="well">
				<p><b>What does this reset?</b></p>
				<p>This resets the password for each of the services that this user has an account on. The password must meet the constraints for all of those services in order to be valid.</p>
				<p>If you are trying to log in to something specific, then after this is set, you may wish to check the "Action Queue" to see whether the change has been applied to that service.</p>
			</div>
		</div>
		<div class="span8">
			<div class="alert alert-info">
				<h3>
					<?php echo web::escapeHTML($data['AccountOwner'] -> owner_firstname . " ". $data['AccountOwner'] -> owner_surname); ?>
				</h3>
			</div>

			<?php 
			if(isset($data['message'])) {
				if(isset($data['set'])) {
					echo "<div class=\"alert alert-success\">"
				} else {
					echo "<div class=\"alert\">"
				}
				echo web::escapeHTML($data['message']) . "</div>";
			}?>

			<div class="well">
			<?php
				$userURL = Web::constructURL("AccountOwner", "view", array((int)$data['AccountOwner'] -> owner_id), "html");
				if(!isset($data['set'])) {
						echo "<form id=\"pwreset\" method=\"post\">" .
							"<fieldset>" .
							"<legend>Reset Password</legend>";
						/* Figure out which box to tick */
						$isAuto = !isset($_POST['source']) || (isset($_POST['source']) && $_POST['source'] == 'auto');
						$auto = $isAuto ? " checked" : "";
						$manual = !$isAuto ? " checked" : "";

						
						echo "<label class=\"radio\"> <input id=\"radioAuto\" type=\"radio\"" .
								"	name=\"source\" value=\"auto\" checked onClick=\"updateView()\" ".$auto."/>" .
								"	Choose a password for me" .
								"</label> <label class=\"radio\"> <input id=\"radioManual\"" .
								"	type=\"radio\" name=\"source\" value=\"manual\" onClick=\"updateView()\" ".$manual."/>" .
								"	Enter password" .
								"</label>" .
								"<div id=\"pwbox\" class=\"control-group\" style=\"display: none\">" .
								"	<div class=\"controls\">" .
								"		<input type=\"password\" id=\"password1\" name=\"password1\"" .
								"			placeholder=\"Password\" /> <input type=\"password\" id=\"password2\"" .
								"			name=\"password2\" placeholder=\"Confirm Password\" />" .
								"	</div>" .
								"</div>" .
								"<div class=\"form-actions\">" .
								"	<a class=\"btn\" href=\"".$userURL."\">Back to User</a>" .
								"	<button type=\"submit\" class=\"btn\">Reset</button>" .
								"	<div class=\"control-group\"><input type=\"checkbox\" checked=\"yes\" name=\"print_receipt\"/> Print account details</div>" .
								"</div>";
						
						echo "</fieldset>" .
							"</form>";
				} else {
					echo "<div class=\"form-actions\">" .
						"	<a class=\"btn\" href=\"".$userURL."\">Back to User</a>" .
						"</div>";
				}
				?>
			</div>
		</div>
	</div>
</div>
