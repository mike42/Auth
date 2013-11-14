<script>
	function localCleanup() {
		$("#service_id").val('');
		$("#action").val('localCleanup');
		$("#frmCleanup").submit();
	}

	function recSearch(service_id) {
		$("#service_id").val(service_id);
		$("#action").val('recSearch');
		$("#frmCleanup").submit();
	}

	function sync(service_id) {
		$("#service_id").val(service_id);
		$("#action").val('sync');
		$("#frmCleanup").submit();
	}

	function deactivate(service_id) {
		if(confirm('Are you sure that you want to de-activate this service? It will become out of sync with Auth.')) {
			$("#service_id").val(service_id);
			$("#action").val('deactivate');
			$("#frmCleanup").submit();
		}
	}

	function activate(service_id) {
		$("#service_id").val(service_id);
		$("#action").val('activate');
		$("#frmCleanup").submit();
	}

	function processAq() {
		$("#service_id").val('');
		$("#action").val('processAq');
		$("#frmCleanup").submit();
	}

	function emptyAq() {
		if(confirm('Empty the queue? This can leave anything in the queue out of sync!')) {
			$("#service_id").val('');
			$("#action").val('emptyAq');
			$("#frmCleanup").submit();
		}
	}

	function eraseLocal() {
		if(confirm('Erase everything?')) {
			$("#service_id").val('');
			$("#action").val('eraseLocal');
			$("#frmCleanup").submit();
		}
	}

	function dummyData() {
		if(confirm('Introduce bogus data?')) {
			$("#service_id").val('');
			$("#action").val('dummyData');
			$("#frmCleanup").submit();
		}
	}
</script>


<div class="container">
	<div class="row">
		<div class="span4">
			<div class="well">
				<p>
					<b>What does this do?</b>
				</p>
				<p>This utility lets you run a few scripts to help you to maintain
					the directory.</p>
				<p>Two things you need to know about:</p>
				<dl>
					<dt>Search</dt>
					<dd>Looks for accounts on the service, and tries to track them
						locally. It will re-shuffle folder structures and account
						locations, if they differ from the local database. No accounts
						will be made or deleted. You should run this often!</dd>
					<dt>Sync</dt>
					<dd>
						This goes through all of the local accounts, groups and units, and
						verifies that they exist on the remote service. If accounts are
						missing, they will stop being tracked, but groups and units will
						be copied across. Remote group membership will be <i>completely
							overridden</i> with the membership information in Auth.
					</dd>
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
				<p>This will delete local 'users' that don't actually have any
					accounts, and changes nothing on any servers.</p>
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
								"<td style='width:10em;'><a class='btn' href='#' onClick='recSearch(" . json_encode($service -> service_id) . ");'>Search</a>" .
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
				<p>If you have your own scripts that add things to the queue, you
					might need to start the queue processor manually. Hit this button
					to do that.</p>
				<a class="btn" href="#" onClick="processAq();">Start processing now</a>

				<h4>Empty</h4>
				<p>On the other hand, if you wrote a silly script that has filled
					the ActionQueue with junk, or if an item has become stuck in the
					queue, you can clear the queue with this button:</p>
				<a class="btn" href="#" onClick="emptyAq();">Empty the ActionQueue</a>
			</div>

			<div class="alert alert-danger">
				<h3>Danger zone</h3>
				<h4>Erase local database</h4>
				<p>This will clear Auth's knowledge of all external databases,
					leaving it a "Clean slate".</p>
				<a class="btn btn-danger" href="#" onClick="eraseLocal();">Erase now</a><br />
				<br />

				<h4>Create dummy data</h4>
				<p>Adds a series of fake users and groups for testing.</p>
				<a class="btn btn-danger" href="#" onClick="dummyData();">Add dummy
					data</a>
			</div>
		</div>
	</div>
</div>
<?php 
echo "<form id=\"frmCleanup\" method=\"post\" action=\"".web::constructURL("Utility", "Cleanup", array(''), 'html')."\" style=\"display: none;\">" .
		"<input id=\"action\" type=\"hidden\" name=\"action\" value=\"\" />" .
		"<input id=\"service_id\" type=\"hidden\" name=\"service_id\" value=\"\" />" .
		"</form>\n";

?>
