<?php
use Auth\web\Web;
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
	
	function moveAo(ou_id) {
		if(confirm('Are you sure you want to move this user?')) {
			$("#ou-id").val(ou_id);
			$("#moveAo").submit();
		}
	}
</script>

<div class="container">
	<div class="row">
		<div class="span4">
			<div class="well">
				<b>What is moving?</b>
				<p>You can relocate a user to a different Organzational Unit for
					housekeeping. This will not affect email distribution, but some
					basic permissions (such as the ability to log in or connect to the
					wifi) will be changed if you move a user between the top-level
					units.</p>
			</div>
		</div>
		<div class="span8">
			<div class="alert alert-info">
				<h3>Move User</h3>
			</div>

			<?php 
			if(isset($data['message'])) {
				echo "<div class=\"alert\">".Web::escapeHTML($data['message']) . "</div>";
			}
			?>
			<div class="well">
				Select the organizational unit which <b><?php echo Web::escapeHTML($data['AccountOwner'] -> owner_firstname . " " . $data['AccountOwner'] -> owner_surname); ?>
				</b> should go in:
				<ul class="treeview" id="tree" style="padding: 5px;">
					<li><?php 
					if($data['AccountOwner'] -> ou_id == $data['Ou_root'] -> ou_id) {
						echo "root";
					} else {
						echo "<a href=\"#\" onClick='moveAo(" . (int)$data['Ou_root'] -> ou_id . ")'>root</a>";
					}
					?>
						<ul>
							<?php ou_tree($data['Ou_root'], $data['AccountOwner'] -> ou_id); ?>
						</ul>
					</li>
				</ul>

				<?php 
				echo "<a class=\"btn\" href=\"" . Web::constructURL("AccountOwner", "view", array((int)$data['AccountOwner'] -> owner_id), "html"). "\">Cancel</a>";
				?>
			</div>
		</div>
	</div>
</div>

<form id="moveAo"
	action="<?php echo Web::constructURL("AccountOwner", "move", array($data['AccountOwner'] -> owner_id), "html"); ?>"
	method="post">
	<input type="hidden" id="ou-id" name="ou_id" />
</form>

<?php 
function ou_tree(Ou_model $ou, $ou_id) {
	foreach($ou -> list_Ou as $subOu) {
		if(count($subOu -> list_Ou) > 0) {
			echo "<li class=\"expandable\"><div class=\"hitarea expandable-hitarea\"></div>";
		} else {
			echo "<li>";
		}

		if($subOu -> ou_id == $ou_id) {
			/* Don't bother showing link of this one */
			echo Web::escapeHTML($subOu -> ou_name);
		} else {
			echo "<a href=\"#\" onClick='moveAo(" . (int)$subOu -> ou_id . ")'>" . Web::escapeHTML($subOu -> ou_name) . "</a>";
		}
		if(count($subOu -> list_Ou) > 0) {
			echo "<ul>";
			ou_tree($subOu, $ou_id);
			echo "</ul>";
		}
		echo "</li>";
	}
}
?>