<?php
use Auth\web\Web;
?>
<div class="container">
	<div class="row">
		<div class="span4">
			<div class="well">
				<p><b>What are utilities?</b></p>
				<p>Utilities are simply special pages which add to the functionality of Auth.</p>
				<p>They can be written to contain extra logic for managing users in different ways, or for automating some tasks.</p>
			</div>
		</div>
		<div class="span8">
			<div class="alert alert-info">
				<h3>
					Utilities
				</h3>
			</div>

			<div class="well">
				<?php
				if(count($data['util']) == 0) {
					echo "<p>There are no utilities installed.<p>";
				} else {
					echo "<ul>";
					foreach($data['util'] as $key => $caption) {
						echo "<li><a href=\"" . Web::constructURL("Utility", $key, array(''), "html") . "\">".Web::escapeHTML($caption)."</a></li>";
					}
					echo "</ul>";
				}
				?>
			</div>
		</div>
	</div>
</div>
