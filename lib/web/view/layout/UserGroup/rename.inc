<script type="text/javascript">
	$(function() {
		$("#group-name").focus();
	});
</script>

<div class="container">
	<div class="row">
		<div class="span4">
			<div class="well">
				<b>Renaming a group</b>
				<p>Changing a group alias will affect email routing, but all users will stay in the group. Note that aliases are limited to alphanumeric characters for technical reasons.</p>
			</div>
		</div>
		<div class="span8">
			<div class="alert alert-info">
				<h3>Rename <?php echo web::escapeHTML($data['UserGroup'] -> group_name . " (" . $data['UserGroup'] -> group_cn . ")"); ?></h3>
			</div>
			<?php 
				if(isset($data['message'])) {
					echo "<div class=\"alert\">".web::escapeHTML($data['message']) . "</div>";
				}
			?>
			<div>
				<form action="<?php echo web::constructURL("UserGroup", "rename", array((int)$data['UserGroup'] -> group_id), 'html'); ?>" method="post">
					<table class="table-bordered" style="margin-left: auto; margin-right: auto">
						<tr>
							<th>
								Group name:
							</th>
							<td>
								<input type="text" name="group_name" id="group-name" value="<?php echo web::escapeHTML($data['UserGroup'] -> group_name); ?>" />
							</td>
						</tr>
						<tr>
							<th>
								Short name:
							</th>
							<td>
								<input type="text" name="group_cn" id="group-cn" value="<?php echo web::escapeHTML($data['UserGroup'] -> group_cn); ?>"/>
							</td>
						</tr>
						<tr>
							<td colspan=2 style="text-align: right">
								<a class="btn" href="<?php echo web::constructURL("UserGroup", "rename", array((int)$data['UserGroup'] -> group_id), 'html'); ?>">Cancel</a>
								<input class="btn" type="submit" value="Rename">
							</td>
						</tr>
					</table>
				</form>
			</div>
		</div>
	</div>
</div>