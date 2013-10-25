<div class="login container">
	<div class="span4">
		<div class="well">
			<div class="control-group login-fail\">
				<?php if(isset($data['message'])) {
					echo Web::escapeHTML($data['message']);
				} else {
					echo "An unexpected error has occurred";
			    } ?>
			</div>
		</div>
	</div>
</div>
