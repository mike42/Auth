<?php
use Auth\web\Web;
?>
<script type="text/javascript">
	$(function() {
		$("#ou-name").focus();
	});
</script>

<div class="container">
	<div class="row">
		<div class="span4">
			<div class="well">
				<b>What is an organizational unit?</b>
				<p>An OU is a container where
				accounts and groups are kept. Unlike groups, a user can only be in
				one OU at a time.</p>
			</div>
		</div>
		<div class="span8">
			<div class="alert alert-info">
				<h3>Create Organizational Unit</h3>
			</div>

			<?php 
				if(isset($data['message'])) {
					echo "<div class=\"alert\">".Web::escapeHTML($data['message']) . "</div>";
				}
			?>
			<div>
				<form action="<?php echo Web::constructURL("Ou", "create", array((int)$data['Parent'] -> ou_id), 'html'); ?>" method="post">
					<table class="table-bordered" style="margin-left: auto; margin-right: auto">
						<tr>
							<th>
								New unit name:
							</th>
							<td>
								<input type="text" name="ou_name" id="ou-name" />
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
