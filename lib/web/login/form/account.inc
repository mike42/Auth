<div class="login container">
	<div class="row">
		<div class="span6 offset3" style="background: white;">
			<div style="padding: 10px;">
				<ul class="nav nav-tabs">
				  <li<?php if($data['active'] == 'info') { echo ' class="active"'; } ?>><a href="#info" data-toggle="tab">Info</a></li>
				  <li<?php if($data['active'] == 'reset') { echo ' class="active"'; } ?>><a href="#reset" data-toggle="tab">Reset Password</a></li>
				</ul>
				<div class="tab-content">
				  <div class="tab-pane<?php if($data['active'] == 'info') { echo ' active'; } ?>" id="info">

<?php
		echo "<table class='table table-bordered'>
			<thead>
				<tr>
					<th colspan='1'>Member of</th>
				</tr>
			</thead>";
		foreach($data['AccountOwner'] -> list_OwnerUserGroup as $ownerGroup) {
			echo "<tr>";
			echo "<td>".web::escapeHTML($ownerGroup -> UserGroup -> group_name). " (".web::escapeHTML($ownerGroup -> UserGroup -> group_cn).")</td>";
			echo "</tr>";
		}
		echo "</table>";
		echo "<div style=\"float: right\">";
		
		echo "</div>";
		echo "<br />";
		
		if(count($data['AccountOwner'] -> list_Account) > 0) {
		echo "<table class='table table-bordered'>
			<thead>
				<tr>
					<th colspan='3'>Services</th>
				</tr>
				<tr>
					<th>Service</th>
					<th>Login</th>
					<th>Domain</th>
				</tr>
			</thead>";
			foreach($data['AccountOwner'] -> list_Account as $account) {
				echo "<tr>";
				echo "<td>".web::escapeHTML($account -> Service -> service_name)."</td>";
				echo "<td>".web::escapeHTML($account -> account_login)."</td>";
				echo "<td>".web::escapeHTML($account -> account_domain)."</td>";
				echo "</tr>";
			}			
			echo "</table>";

			echo "<br />";
		}

		?>
				  </div>
				  <div class="tab-pane<?php if($data['active'] == 'reset') { echo ' active'; } ?>" id="reset">


					<form action="index.php" method="post">
					  <?php if(isset($data['message'])) {
					    echo "<div class=\"control-group ".(isset($data['good']) ? "login-ok" : "login-fail" ) ."\">" . htmlspecialchars($data['message']) . "</div>";
					    } ?>
					  <input type="hidden" name="action" value="password-reset" />
					  <div class="control-group">
					    <label class="control-label" for="inputPassword1">Password</label>
					    <div class="controls">
					      <input type="password" id="inputPassword1" placeholder="" name="inputPassword1">
					    </div>
					  </div>
					  <div class="control-group">
					    <label class="control-label" for="inputPassword2">Confirm Password</label>
					    <div class="controls">
					      <input type="password" id="inputPassword2" placeholder="" name="inputPassword2">
					    </div>

					  </div>
					  <div class="control-group">
					    <div class="controls">
					      <button type="submit" class="btn btn-danger">Reset Password</button>
					    </div>
					  </div>
					</form>

				  </div>
				</div>
			</div>
		</div>
	</div>
</div>
