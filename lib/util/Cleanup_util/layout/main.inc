<script>
	function localCleanup() {
		// TODO 
		alert('This function has not been implemented yet');
	}

	function recSearch(service_id) {
		// TODO 
		alert('This function has not been implemented yet');
	}

	function sync(service_id) {
		// TODO 
		alert('This function has not been implemented yet');
	}

	function deactivate(service_id) {
		// TODO 
		alert('This function has not been implemented yet');
	}

	function activate(service_id) {
		// TODO 
		alert('This function has not been implemented yet');
	}

	function processAq() {
		// TODO 
		alert('This function has not been implemented yet');
	}

	function emptyAq() {
		// TODO 
		alert('This function has not been implemented yet');
	}
</script>


<div class="container">
	<div class="row">
		<div class="span4">
			<div class="well">
				<p>
					<b>What does this do?</b>
				</p>
				<p>This utility lets you run a few scripts to help you to maintain the directory.</p>
				<p>Two things you need to know about:</p>
				<dl>
				<dt>Search</dt>
				<dd>Looks for accounts on the service, and tries to track them locally. It will re-shuffle folder structures and account locations, if they differ from the local database. No accounts will be made or deleted. You should run this often!</dd>
				<dt>Sync</dt>
				<dd>This goes through all of the local accounts, groups and units, and verifies that they exist on the remote service. If accounts are missing, they will stop being tracked, but groups and units will be copied across. Remote group membership will be <i>completely overridden</i> with the membership information in Auth.</dd>
				</dl>
			</div>
		</div>
		<div class="span8">
			<div class="alert alert-info">
				<h3>Directory Cleanup Utility</h3>
			</div>
			<?php
			if(isset($data['message'])) {
				echo "<div class=\"alert\">".web::escapeHTML($data['message']) . "</div>";
			}
			?>
			<div class="well">
				<h3>Local Cleanup</h3>
				<p>This will delete local 'users' that don't actually have any accounts, and changes nothing on any servers.</p>
				<a class="btn" href="#" onClick="localCleanup();">Do local cleanup</a>
			</div>

			<div class="well">
				<h3>Manage services</h3>
				<h4>Active services</h4>
				<?php 
				if(count($data['service-enabled']) > 0) {
					echo "<table class=\"table table-bordered\">\n";
					foreach($data['service-enabled'] as $service) {
						echo "<tr>\n" .
							"<td>" . Web::escapeHTML($service -> service_name) . "</td>" . 
							"<td style='width:9em;'><a class='btn' href='#' onClick='recSearch(" . json_encode($service -> service_id) . ");'>Search</a>" .
							"<a class='btn' href='#' onClick='sync(" . json_encode($service -> service_id) . ");'>Sync</a></td></td>" .
							"<td style='width:6em;'><a class='btn' href='#' onClick='deactivate(" . json_encode($service -> service_id) . ");'>Deactivate</a></td>" .
							"</tr>";
					}
					echo "</table>\n";
				} else {
					echo "<p>There are no active services.</p>";
				}
				?>
				
				<h4>Inactive services</h4>
				<?php 
				if(count($data['service-disabled']) > 0) {
					echo "<table class=\"table table-bordered\">\n" . 
							"<p>These services will not be interacted with whilst they are disabled. They must be administered manually.</p>";
					foreach($data['service-disabled'] as $service) {
						echo "<tr>\n" .
							"<td>" . Web::escapeHTML($service -> service_name) . "</td>" . 
							"<td style='width:6em;'><a class='btn' href='#' onClick='activate(" . json_encode($service -> service_id) . ");'>Activate</a></td>" .
							"</tr>";
					}
					echo "</table>\n";
				} else {
					echo "<p>There are no inactive services</p>";
				}
				?>				
			</div>
			
			<div class="well">
				<h3>ActionQueue</h3>
				<h4>Process</h4>
				<p>If you have your own scripts that add things to the queue, you might need to start the queue processor manually. Hit this button to do that.</p>
				<a class="btn" href="#" onClick="processAq();">Start processing now</a>

				<h4>Empty</h4>
				<p>On the other hand, if you wrote a silly script that has filled the ActionQueue with junk, or if an item has become stuck in the queue, you can clear the queue with this button:</p>
				<a class="btn" href="#" onClick="emptyAq();">Empty the ActionQueue</a>
			</div>
		</div>
	</div>
</div>
