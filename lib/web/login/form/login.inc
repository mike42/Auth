<div class="login container">
	<div class="span4">
		<div class="well">
			<form action="index.php" method="post">
			  <?if(isset($data['message'])) {
			    echo "<div class=\"control-group login-fail\">" . htmlspecialchars($data['message']) . "</div>";
			    } ?>
			  <div class="control-group">
			    <label class="control-label" for="inputEmail">Username</label>
			    <div class="controls">
			      <input name="loginUsername" type="text" id="inputUname" placeholder="">
			    </div>
			  </div>
			  <div class="control-group">
			    <label class="control-label" for="inputPassword">Password</label>
			    <div class="controls">
			      <input name="loginPassword" type="password" id="inputPassword" placeholder="">
			    </div>
			  </div>
			  <div class="control-group">
			    <div class="controls">
			      <button type="submit" class="btn btn-primary">Sign In</button>
			    </div>
			  </div>
			</form>
		</div>
	</div>
</div>
