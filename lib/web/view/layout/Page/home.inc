<div id="rightnow">
	<div class="alert alert-info">
		<h3 class="reallynow">
		<span>Search</span>
		</h3>
	</div>

	<div class="well">
	<form style="text-align: center; margin: 3em;" method="post">
		<p>
		<?	if(isset($data['message'])) {
				echo "<b>".web::escapeHTML($data['message']) . "</b>";
			} else {
				echo "Enter a username:";
			} ?>
		</p>
		<div class="input-append">
		  <input class="span2" id="q" name="q" type="text">
		  <button class="btn" type="submit">Go</button>
		</div>
	</form>
	</div>
</div>

<script type="text/javascript">
	// Set focus on search box
	$("#q").focus();
</script>
