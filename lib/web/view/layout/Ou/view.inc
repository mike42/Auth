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
</script>

<div class="container">
	<div class="row">
		<div class="span4">
				<?php
					if(count($data['Ou'] -> list_Ou) > 0) {
						
						echo '
							<div class="well">
								<ul class="treeview" id="tree" style="padding: 10px;">';
							group_tree($data['Ou']);

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
			
<div>
<?php 
echo "<table class='table table-bordered'>" . 
	"<thead>\n" .
	"<tr>\n" . 
	"<th colspan='1'>Organizational units\n" .
	"<div class='btn-group pull-right'>\n" .
	"<a class='btn' href=\"".web::constructURL("Ou", "create", array($data['Ou'] -> ou_id), "html")."\">New Unit</a>\n" .
	"</div></th>\n" . 
	"</tr>\n" .
	"</thead>\n";
// For each OU
echo "</table>";

echo "<table class='table table-bordered'>" .
		"<thead>\n" .
		"<tr>\n" .
		"<th colspan='1'>Groups\n" .
		"<div class='btn-group pull-right'>\n" .
		"<a class='btn' href=\"".web::constructURL("UserGroup", "create", array($data['Ou'] -> ou_id), "html")."\">New Group</a>\n" .
		"</div></th>\n" .
		"</tr>\n" .
		"</thead>\n";
// For each UserGroup
echo "</table>";

echo "<table class='table table-bordered'>" .
		"<thead>\n" .
		"<tr>\n" .
		"<th colspan='1'>Users\n" .
		"<div class='btn-group pull-right'>\n" .
		"<a class='btn' href=\"".web::constructURL("AccountOwner", "create", array($data['Ou'] -> ou_id), "html")."\">New User</a>\n" .
		"</div></th>\n" .
		"</tr>\n" .
		"</thead>\n";
// For each Users
echo "</table>";
?>
</div>

<?php 
	function group_tree(Ou_model $ou) {
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
				group_tree($subOu);
				echo "</ul>";
				
			}
			echo "</li>";
		}
	}
?>