<?php
use Auth\web\Web;
use Auth\model\Ou_model;
?>
<script type="text/javascript">
	$(function() {
		$("#tree").treeview({
			collapsed: true,
			animated: "fast",
			control:"#sidetreecontrol",
			prerendered: true,
			persist: "location"
		});
	})
	
	function deleteOu() {
		if(confirm('Are you sure you want to delete this organizational unit? Anything in it will be moved up a level.')) {
			$("#deleteOu").submit();
		}
	}

	function moveOu() {
		$("#moveOu").submit();
	}

	function renameOu() {
		$("#renameOu").submit();
	}
</script>

<div class="container">
	<div class="row">
		<div class="span4">
			<?php
			if(count($data['Ou_root'] -> list_Ou) > 0) {

				echo '
				<div class="well">
				<ul class="treeview" id="tree" style="padding: 5px;">';
				ou_tree($data['Ou_root']);

				echo '<div id="sidetreecontrol">
				<a class="btn btn-link" href="#">Collapse All</a>
				<a class="btn btn-link" href="#">Expand All</a>
				</div>
				</ul>
				</div>';
			} else {
				echo "&nbsp;";
				}?>
		</div>
		<div class="span8">
			<div class="alert alert-info">
				<?php 
				if($data['Ou'] -> ou_name == "root") {
					echo "<h3>Organization</h3>";
				} else {
					echo "<h3>".Web::escapeHTML($data['Ou'] -> ou_name)."</h3>";
				}
				?>
			</div>
			<?php 
			if(isset($data['message'])) {
				echo "<div class=\"alert\">".Web::escapeHTML($data['message']) . "</div>";
			}

			/* Displaying sub-units */
			echo "<div>";
			echo "<table class='table table-bordered'>" .
					"<thead>\n" .
					"<tr>\n" .
					"<th colspan='1'>Organizational units\n" .
					"<div class='btn-group pull-right'>\n" .
					"<a class='btn' href=\"".Web::constructURL("Ou", "create", array($data['Ou'] -> ou_id), "html")."\">New Unit</a>\n" .
					"</div></th>\n" .
					"</tr>\n" .
					"</thead>\n";
			foreach($data['Ou'] -> list_Ou as $ou) {
				echo "<tr><td>" .
						"<a href=\"".Web::constructURL("Ou", "view", array($ou -> ou_id), "html")."\">" .
						Web::escapeHTML($ou -> ou_name) . "</a>" .
						"</td></td>";
			}
			echo "</table>";

			/* Displaying groups */
			echo "<table class='table table-bordered'>" .
					"<thead>\n" .
					"<tr>\n" .
					"<th colspan='1'>Groups\n" .
					"<div class='btn-group pull-right'>\n" .
					"<a class='btn' href=\"".Web::constructURL("UserGroup", "create", array($data['Ou'] -> ou_id), "html")."\">New Group</a>\n" .
					"</div></th>\n" .
					"</tr>\n" .
					"</thead>\n";
			foreach($data['Ou'] -> list_UserGroup as $ug) {
				echo "<tr><td>" .
						"<a href=\"".Web::constructURL("UserGroup", "view", array($ug -> group_id), "html")."\">" .
						Web::escapeHTML($ug -> group_name . " (" . $ug -> group_cn . ")") . "</a>" .
						"</td></td>";
			}
			echo "</table>";

			/* Displaying user accounts */
			echo "<table class='table table-bordered'>" .
					"<thead>\n" .
					"<tr>\n" .
					"<th colspan='1'>Users\n" .
					"<div class='btn-group pull-right'>\n" .
					"<a class='btn' href=\"".Web::constructURL("AccountOwner", "create", array($data['Ou'] -> ou_id), "html")."\">New User</a>\n" .
					"</div></th>\n" .
					"</tr>\n" .
					"</thead>\n";
			foreach($data['Ou'] -> list_AccountOwner as $ao) {
				echo "<tr><td>" .
						"<a href=\"".Web::constructURL("AccountOwner", "view", array($ao -> owner_id), "html")."\">" .
						Web::escapeHTML($ao -> owner_firstname . " " . $ao -> owner_surname) . "</a>" .
						"</td></td>";
			}
			echo "</table>";
			echo "</div>";

			if($data['Ou_root'] -> ou_id != $data['Ou'] -> ou_id) {
				echo "<div class='well'>" .
						"<h3>Unit options</h3>" .
						"<p>Renaming or moving an organizational unit may affect email distribution, or affect users' ability to access some services. Proceed with care.</p>" .
						"<input type=\"button\" class='btn' value=\"Rename Unit\" onClick=\"renameOu()\" />" .
						"<input type=\"button\" class='btn' value=\"Move Unit\" onClick=\"moveOu()\" />" .
						"</div>";
					
				echo "<div class='alert alert-danger'>" .
						"<h3>Danger zone</h3>" .
						"<p>Deleting an organizational unit cannot be undone!</p>" .
						"<input type=\"button\" class='btn btn-danger' value=\"Delete Unit\" onClick=\"deleteOu()\" />" .
						"</div>";
			}
			?>


		</div>
	</div>
</div>

<!-- Forms used for hitting buttons -->
<form style="display: none" id="deleteOu"
	action="<?php echo Web::constructURL("Ou", "view", array($data['Ou'] -> ou_id), 'html'); ?>"
	method="post">
	<input type="hidden" name="action" value="delete">
</form>

<form style="display: none" id="renameOu"
	action="<?php echo Web::constructURL("Ou", "rename", array($data['Ou'] -> ou_id), 'html'); ?>"
	method="post"></form>

<form style="display: none" id="moveOu"
	action="<?php echo Web::constructURL("Ou", "move", array($data['Ou'] -> ou_id), 'html'); ?>"
	method="post"></form>

<?php 
function ou_tree(Ou_model $ou) {
	foreach($ou -> list_Ou as $subOu) {
		if(count($subOu -> list_Ou) > 0) {
			echo "<li class=\"expandable\"><div class=\"hitarea expandable-hitarea\"></div>";
		} else {
			echo "<li>";
		}
		echo "<a href=\"" .
				Web::constructURL('Ou', 'view', array($subOu -> ou_id), 'html')
				. "\">" . Web::escapeHTML($subOu -> ou_name) . "</a>";
		if(count($subOu -> list_Ou) > 0) {
			echo "<ul style=\"display: none;\">";
			ou_tree($subOu);
			echo "</ul>";

		}
		echo "</li>";
	}
}

?>