<form action="index.php" method="post">
	<?php
	if(isset($data['message'])) {
		echo "<div class=\"control-group login-fail\">" . htmlspecialchars($data['message']) . "</div>";
					   } ?>
	<input type="hidden" name="action" value="password-reset" />
	<div class="control-group">
		<label class="control-label" for="inputPassword1">Password</label>
		<div class="controls">
			<input type="password" id="inputPassword1" placeholder=""
				name="inputPassword1">
		</div>
	</div>
	<div class="control-group">
		<label class="control-label" for="inputPassword2">Confirm Password</label>
		<div class="controls">
			<input type="password" id="inputPassword2" placeholder=""
				name="inputPassword2">
		</div>

	</div>
	<div class="control-group">
		<div class="controls">
			<button type="submit" class="btn btn-danger">Reset Password</button>
		</div>
	</div>
</form>