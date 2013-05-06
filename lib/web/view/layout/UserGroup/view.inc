<script type="text/javascript">
function renameGroup() {
	$("#renameGroup").submit();
}

function moveGroup() {
	$("#moveGroup").submit();
}

function deleteGroup() {
	if(confirm('Are you sure you want to delete this group? All of the membership information for the group will be deleted.')) {
		$("#deleteGroup").submit();
	}
}

function delchild(parent, child) {
	$("#delchild-parent_group_id").val(parent);
	$("#delchild-group_id").val(child);
	$("#delchild").submit();
}

</script>

<div class="container">
	<div class="row">
		<div class="span4">
			<div class="well">
				<?php echo "<a href=\"" . web::constructURL("Ou", "view", array($data['UserGroup'] -> Ou -> ou_id), "html"). "\">" . 
						"&larr; Back to '" . web::escapeHTML($data['UserGroup'] -> Ou -> ou_name) . "' organizational unit</a>";?>
			</div>
		</div>
		<div class="span8">
			<div class="alert alert-info">
				<h3>
					<?php echo web::escapeHTML($data['UserGroup'] -> group_name . " (". $data['UserGroup'] -> group_cn .")"); ?>
				</h3>
			</div>

			<?php 
			echo "<div>";
			if(isset($data['message'])) {
				echo "<div class=\"alert\">".web::escapeHTML($data['message']) . "</div>";
			}
			
			echo "<table class='table table-bordered'>" .
					"<thead>\n" .
					"<tr>\n" .
					"<th colspan='1'>Member of\n" .
					"<div class='btn-group pull-right'>\n" .
					"<a class='btn' href='" .  web::constructURL("UserGroup", "addparent", array($data['UserGroup'] -> group_id), 'html') ."'>Add to group</a>\n" .
					"</div></th>\n" .
					"</tr>\n" .
					"</thead>\n";
			foreach($data['Parents'] as $parent) {
				echo "<tr><td>";
				echo "<div style=\"float: right\"><a href=\"javascript: void(null)\" onClick=\"delchild(" . (int)$parent -> group_id . ", " . (int)$data['UserGroup'] -> group_id . ");\"><img src=\"/admin/img/delete.png\" /></a></div>";
				echo "<a href=\"".web::constructURL("UserGroup", "view", array((int)$parent -> group_id), "html")."\">" . web::escapeHTML($parent -> group_name . " (" . $parent -> group_cn . ")") . "</a>";
				echo "</td></tr>";
			}
			echo "</table>";

			echo "<div>";
			echo "<table class='table table-bordered'>" .
					"<thead>\n" .
					"<tr>\n" .
					"<th colspan='1'>Sub-groups\n" .
					"<div class='btn-group pull-right'>\n" .
					"<a class='btn' href='" .  web::constructURL("UserGroup", "addchild", array($data['UserGroup'] -> group_id), 'html') ."'>Add group here</a>\n" .
					"</div></th>\n" .
					"</tr>\n" .
					"</thead>\n";

			foreach($data['Children'] as $child) {
					echo "<tr><td>";
					echo "<div style=\"float: right\"><a href=\"javascript: void(null)\" onClick=\"delchild(" . (int)$data['UserGroup'] -> group_id . ", " . (int)$child -> group_id . ");\"><img src=\"/admin/img/delete.png\" /></a></div>";
					echo "<a href=\"".web::constructURL("UserGroup", "view", array((int)$child -> group_id), "html")."\">" . web::escapeHTML($child -> group_name . " (" . $child -> group_cn . ")") . "</a>";
					echo "</td></tr>";
			}
			echo "</table>";

			echo "<div>";
			echo "<table class='table table-bordered'>" .
					"<thead>\n" .
					"<tr>\n" .
					"<th colspan='1'>Members\n" .
					"<div class='btn-group pull-right'>\n" .
					"<a class='btn' href='" .  web::constructURL("UserGroup", "adduser", array($data['UserGroup'] -> group_id), 'html') ."'>Add user</a>\n" .
					"</div></th>\n" .
					"</tr>\n" .
					"</thead>\n";
			// For each user
			echo "</table>";

			echo "<div class='well'>" .
					"<h3>Group options</h3>" .
					"<p>Renaming a group may affect email distribution, or affect users' ability to access some services. Proceed with care.</p>" .
					"<input type=\"button\" class='btn' value=\"Rename Group\" onClick=\"renameGroup()\" />" .
					"<input type=\"button\" class='btn' value=\"Move Group\" onClick=\"moveGroup()\" />" .
					"</div>";

			echo "<div class='alert alert-danger'>" .
					"<h3>Danger zone</h3>" .
					"<p>Deleting a group cannot be undone!</p>" .
					"<input type=\"button\" class='btn btn-danger' value=\"Delete Group\" onClick=\"deleteGroup()\" />" .
					"</div>";
			?>

		</div>
	</div>
</div>

<form style="display: none" id="renameGroup"
	action="<?php echo web::constructURL("UserGroup", "rename", array($data['UserGroup'] -> group_id), 'html'); ?>"
	method="post"></form>

<form style="display: none" id="moveGroup"
	action="<?php echo web::constructURL("UserGroup", "move", array($data['UserGroup'] -> group_id), 'html'); ?>"
	method="post"></form>
	
<form style="display: none" id="delchild"
	action="<?php echo web::constructURL("UserGroup", "view", array($data['UserGroup'] -> group_id), 'html'); ?>"
	method="post">
	<input type="hidden" name="action" value="delchild">
	<input type="hidden" name="parent_group_id" id="delchild-parent_group_id" value="" />
	<input type="hidden" name="group_id" id="delchild-group_id" value="" />
</form>
	
<form style="display: none" id="deleteGroup"
	action="<?php echo web::constructURL("UserGroup", "view", array($data['UserGroup'] -> group_id), 'html'); ?>"
	method="post">
	<input type="hidden" name="action" value="delete">
</form>
