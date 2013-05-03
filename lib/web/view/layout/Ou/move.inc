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
	
	function moveOu(ou_id, ou_parent_id) {
		if(confirm('Are you sure you want to move this organizational unit?')) {
			$("#ou-id").val(ou_id);
			$("#ou-parent-id").val(ou_parent_id);
			$("#moveOu").submit();
		}
	}
</script>

<div class="container">
	<div class="row">
		<div class="span4">
			<div class="well">
				<b>What is moving?</b>
				<p>Moving an organizational unit puts it under a different container. This could mean that it gets very different permissions to what it had before!</p>
			</div>
		</div>
		<div class="span8">
			<div class="alert alert-info">
				<h3>Move Organizational Unit</h3>
			</div>

			<?php 
				if(isset($data['message'])) {
					echo "<div class=\"alert\">".web::escapeHTML($data['message']) . "</div>";
				}
			?>
			<div class="well">
				Select the organizational unit which <b><?php echo web::escapeHTML($data['Ou'] -> ou_name); ?></b> should go in:
				<ul class="treeview" id="tree" style="padding: 5px;">
				<li><a href="#" onClick='moveOu(<?php echo (int)$data['Ou'] -> ou_id; ?>,<?php echo (int)$data['Ou_root'] -> ou_id; ?>)'>root</a>
				<ul>
				<?php group_tree($data['Ou_root'], $data['Ou'] -> ou_id); ?>
				</ul>
				</li>
				</ul>
				
				<a class="btn">Cancel</a>
			</div>
		</div>
	</div>
</div>

<form id="moveOu" action="<?php echo Web::constructURL("Ou", "move", array($data['Ou'] -> ou_id), "html"); ?>" method="post">
	<input type="hidden" id="ou-id" name="ou_id" />
	<input type="hidden" id="ou-parent-id" name="ou_parent_id" />
</form>

<?php 
function group_tree(Ou_model $ou, $ou_id) {
	foreach($ou -> list_Ou as $subOu) {
		if($subOu -> ou_id == $ou_id) {
			/* Don't bother showing subtree of this one */
			echo "<li>". Web::escapeHTML($subOu -> ou_name) . "</li>";
		} else {
			if(count($subOu -> list_Ou) > 0) {
				echo "<li class=\"expandable\"><div class=\"hitarea expandable-hitarea\"></div>";
			} else {
				echo "<li>";
			}
			echo "<a href=\"#\" onClick='moveOu(". (int)$ou_id. "," . (int)$subOu -> ou_id . ")'>" . Web::escapeHTML($subOu -> ou_name) . "</a>";
			if(count($subOu -> list_Ou) > 0) {
				echo "<ul>";
				group_tree($subOu, $ou_id);
				echo "</ul>";
	
			}
			echo "</li>";
		}
	}
}
?>