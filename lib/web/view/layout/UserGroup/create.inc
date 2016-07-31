<?php
use Auth\web\Web;
?>
<script type="text/javascript">
	$(function() {
		$("#group-name").focus();
	});

	$('#group-name').live('input', function() {
		alias = normaliseName($('#group-name').val());	
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
					echo "<div class=\"alert\">".Web::escapeHTML($data['message']) . "</div>";
				}
			?>
			<div>
				<form action="<?php echo Web::constructURL("UserGroup", "create", array((int)$data['Parent'] -> ou_id), 'html'); ?>" method="post">
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
								Short name:
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
								<?php echo Web::escapeHTML($data['Parent'] -> ou_name); ?>
							</td>
						</tr>
						<tr>
							<th>
								Domain:
							</th>
							<td>
							<?php
							echo "<select id=\"sel-domain\" name=\"domain_id\" >\n" .
									"<option value=\"\">(select domain)</option>";
							foreach($data['ListDomain'] as $domain) {
								$selected = (isset($_POST['domain_id']) && $_POST['domain_id'] == $domain -> domain_id) ? " selected": "";
								echo "<option value=\"".Web::escapeHTML($domain -> domain_id)."\"$selected>" . Web::escapeHTML($domain -> domain_name) . "</option>\n";
							}
							?>
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
