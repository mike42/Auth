<?php
use Auth\web\Web;
?>
<div class="container">
	<div class="row">
		<div class="span4">
			<div class="well">
				<p>
					<b>What is the ActionQueue?</b>
				</p>
				<p>Because this system manages multiple back-end account-keeping
					systems, it would be really slow to use if you had to wait for it
					all the time!</p>
				<p>While you are working, Auth puts together a "Todo" list with all
					the things you've changed, and applies the changes in its own time.</p>
				<p>You can find all sorts of extra detail in the <?php echo "<a href=\"" . Web::constructURL("ActionQueue", "log", array(''), "html") . "\">ActionQueue log file</a>."?>
			</div>
		</div>
		<div class="span8">
			<div class="alert alert-info">
				<h3>ActionQueue</h3>
			</div>
			<?php
			if(count($data['AQ']) == 0) {
				echo "<div class=\"well\"><p>The ActionQueue is empty.</p></div>";
			} else {
				echo "<p>Items are shown in approximate execution order:</p>";
				echo "<table class='table table-bordered'>" .
						"<tr><th>Action</th><th>Service</th><th>Domain</th><th>Target</th><th>Date</th><th>Attempts</th></tr>";
				foreach($data['AQ'] as $aq) {
					echo "<tr>".
							"<td>" . Web::escapeHTML($aq -> action_type) . "</td>" .
							"<td>" . Web::escapeHTML($aq -> Service -> service_name) . "</td>" .
							"<td>" . Web::escapeHTML($aq -> ListDomain -> domain_name) . "</td>" .
							"<td>" . Web::escapeHTML($aq -> aq_target) . "</td>" .
							"<td>" . Web::escapeHTML($aq -> aq_date) . "</td>" .
							"<td>" . Web::escapeHTML($aq -> aq_attempts) . "</td>" .
							"</tr>";
				}
				echo "</table>";
			}
			?>
		</div>
	</div>
</div>