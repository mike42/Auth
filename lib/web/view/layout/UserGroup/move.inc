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
	
	function moveGroup(group_id, ou_id) {
		if(confirm('Are you sure you want to move this group?')) {
			$("#group-id").val(group_id);
			$("#ou-id").val(ou_id);
			$("#moveGroup").submit();
		}
	}
</script>

<div class="container">
	<div class="row">
		<div class="span4">
			<div class="well">
				<b>What is moving?</b>
				<p>You can re-locate a group to a different organizational group for
					housekeeping. This will not affect distribution of email.</p>
			</div>
		</div>
		<div class="span8">
			<div class="alert alert-info">
				<h3>Move Group</h3>
			</div>

			<?php 
			if(isset($data['message'])) {
				echo "<div class=\"alert\">".web::escapeHTML($data['message']) . "</div>";
			}
			?>
			<div class="well">
				Select the organizational unit which <b><?php echo web::escapeHTML($data['UserGroup'] -> group_name . " (" . $data['UserGroup'] -> group_cn . ")"); ?>
				</b> should go in:
				<ul class="treeview" id="tree" style="padding: 5px;">
					<li><?php 
					if($data['UserGroup'] -> ou_id == $data['Ou_root'] -> ou_id) {
						echo "root";
					} else {
						echo "<a href=\"#\" onClick='moveGroup(" . (int)$data['UserGroup'] -> group_id . "," . (int)$data['Ou_root'] -> ou_id . ")'>root</a>";
					} ?>
						<ul>
							<?php ou_tree($data['Ou_root'], $data['UserGroup'] -> ou_id, $data['UserGroup'] -> group_id); ?>
						</ul>
					</li>
				</ul>

				<?php 
				echo "<a class=\"btn\" href=\"" . Web::constructURL("UserGroup", "view", array((int)$data['UserGroup'] -> group_id), "html"). "\">Cancel</a>";
				?>
			</div>
		</div>
	</div>
</div>

<form id="moveGroup"
	action="<?php echo Web::constructURL("UserGroup", "move", array($data['UserGroup'] -> group_id), "html"); ?>"
	method="post">
	<input type="hidden" id="group-id" name="group_id" /> <input
		type="hidden" id="ou-id" name="ou_id" />
</form>

<?php 
function ou_tree(Ou_model $ou, $ou_id, $group_id) {
	foreach($ou -> list_Ou as $subOu) {
		if(count($subOu -> list_Ou) > 0) {
			echo "<li class=\"expandable\"><div class=\"hitarea expandable-hitarea\"></div>";
		} else {
			echo "<li>";
		}

		if($subOu -> ou_id == $ou_id) {
			/* Don't bother showing link of this one */
			echo web::escapeHTML($subOu -> ou_name);
		} else {
			echo "<a href=\"#\" onClick='moveGroup(". (int)$group_id. "," . (int)$subOu -> ou_id . ")'>" . Web::escapeHTML($subOu -> ou_name) . "</a>";
		}

		if(count($subOu -> list_Ou) > 0) {
			echo "<ul>";
			ou_tree($subOu, $ou_id, $group_id);
			echo "</ul>";
		}
		echo "</li>";
	}
}
?>