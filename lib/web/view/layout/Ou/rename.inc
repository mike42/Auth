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
				<b>What is renaming?</b>
				<p>Changing the unit name affects every object in the organizational unit! If you rename a large unit, then you can expect issues to arise with authentication.</p>
				<p>You should only rename a unit if its current name is incorrect.</p>
			</div>
		</div>
		<div class="span8">
			<div class="alert alert-info">
				<h3>Rename Organizational Unit</h3>
			</div>

			<?php 
				if(isset($data['message'])) {
					echo "<div class=\"alert\">".Web::escapeHTML($data['message']) . "</div>";
				}
			?>
			<div>
				<form action="<?php echo Web::constructURL("Ou", "rename", array((int)$data['Ou'] -> ou_id), 'html'); ?>" method="post">
					<table class="table-bordered" style="margin-left: auto; margin-right: auto">
						<tr>
							<th>
								New unit name:
							</th>
							<td>
								<input type="text" name="ou_name" id="ou-name" value="<?php echo Web::escapeHTML($data['Ou'] -> ou_name); ?>" />
							</td>
						</tr>
						<tr>
							<td colspan=2 style="text-align: right">
								<input class="btn" type="submit" value="Rename">
							</td>
						</tr>
					</table>
				</form>
			</div>
		</div>
	</div>
</div>