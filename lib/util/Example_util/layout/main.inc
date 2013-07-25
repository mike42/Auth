<div class="container">
	<div class="row">
		<div class="span4">
			<div class="well">
				<p>
					<b>What does this do?</b>
				</p>
				<p>A brief description of the utility and its purpose. You may wish
					to use a definition list if there is special jargon involved:</p>
				<dl>
					<dt>Feature</dt>
					<dd>Description</dd>
					<dt>Feature</dt>
					<dd>Description</dd>
				</dl>
			</div>
		</div>
		<div class="span8">
			<div class="alert alert-info">
				<h3>Example Utility</h3>
			</div>
			<?php
			if(isset($data['message'])) {
				echo "<div class=\"alert\">".web::escapeHTML($data['message']) . "</div>";
			}
			?>
			<div class="well">
				<h3>Feature</h3>

				<?php echo "<form method=\"post\" action=\"".web::constructURL("Utility", "Example", array(''), 'html')."\"/>"?>
				<input type="hidden" name="helloworld" value="yes" />
				<p>The "Hello World" feature says hello to the world</p>
				<input type="submit" class="btn" value="Do something">
				<?php echo "</form>"; ?>
			</div>

		</div>
	</div>
</div>
