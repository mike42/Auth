<script type="text/javascript">
	$(function() {
		$("#group-name").focus();
	});

	$('#group-name').live('input', function() {
		/* This is a simulation of Auth::normaliseName(),
			which will be used to derive the alias on the server.
		*/
		alias = $('#group-name').val().replace(/[^a-z0-9]/gi, '').toLowerCase();
		
		$('#group-cn').val(alias);
	})
</script>

<div class="container">
	<div class="row">
		<div class="span4">
			<div class="well">
				<b>What is a group?</b>
				<p>A group is a collection of users, used for security purposes. A user account can be in many different groups, and will receive email messages sent to the group address.</p>
			</div>
		</div>
		<div class="span8">
			<div class="alert alert-info">
				<h3>Create Group</h3>
			</div>
			<?php 
				if(isset($data['message'])) {
					echo "<div class=\"alert\">".web::escapeHTML($data['message']) . "</div>";
				}
			?>
			<div>
				<form action="<?php echo web::constructURL("UserGroup", "create", array((int)$data['Parent'] -> ou_id), 'html'); ?>" method="post">
					<table class="table-bordered" style="margin-left: auto; margin-right: auto">
						<tr>
							<th>
								Group name:
							</th>
							<td>
								<input type="text" name="group_name" id="group-name" />
							</td>
						</tr>
						<tr>
							<th>
								Alias:
							</th>
							<td>
								<input type="text" name="group_cn" id="group-cn" disabled/>
							</td>
						</tr>
						<tr>
							<th>
								Create in:
							</th>
							<td>
								<?php echo web::escapeHTML($data['Parent'] -> ou_name); ?>
							</td>
						</tr>
						<tr>
							<td colspan=2 style="text-align: right">
								<input class="btn" type="submit" value="Create">
							</td>
						</tr>
					</table>
				</form>
			</div>
		</div>
	</div>
</div>
