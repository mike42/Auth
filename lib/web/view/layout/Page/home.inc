<?php include(dirname(__FILE__)."/../snippet/account_selector.php"); ?>
<div id="rightnow">
	<div class="alert alert-info">
		<h3 class="reallynow">
			<span>Search</span>
		</h3>
	</div>

	<div class="well">
		<form style="text-align: center; margin: 3em;" method="post" id="accountselect">
			<p>
				<?php	if(isset($data['message'])) {
					echo "<b>".web::escapeHTML($data['message']) . "</b>";
				} else {
					echo "Enter a username:";
			} ?>
			</p>
			<div class="input-append">
				<input type="hidden" name="owner_id" id="owner_id" /> <input
					type="text" name="uname" id="uname" class="span2" />
				<button class="btn" type="submit">Go</button>
			</div>
		</form>
	</div>
</div>

<script>
	// Set focus on search box
	$("#uname").focus();
</script>
