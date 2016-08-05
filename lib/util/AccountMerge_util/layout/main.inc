<?php
use Auth\web\Web;
?>
<?php include(dirname(__FILE__)."/../../../web/view/layout/snippet/account_selector.php"); ?>
<script>
	function prepare() {
		$("#action").val('prepare');
		$("#accountselect").submit();
	}

	function merge() {
		$("#action").val('merge');
		$("#accountselect").submit();
	}
</script>

<div class="container">
	<div class="row">
		<div class="span4">
			<div class="well">
				<p>
					<b>What is account merging?</b>
				</p>
				<p>If a user has multiple existing accounts (on different services),
					then you probably want them to appear on the same page on Auth.
					This means that you can reset the login details, account
					information, and group membership details in one place.</p>
				<p>This utility exists to help you merge together multiple
					AccountOwner pages. To do this, they must not contain accounts on
					the same service!</p>
			</div>
		</div>
		<div class="span8">
			<div class="alert alert-info">
				<h3>Account Merge Utility</h3>
			</div>
			<?php
			if(isset($data['message'])) {
				echo "<div class=\"alert\">".web::escapeHTML($data['message']) . "</div>";
			}
			?>
			<div class="well">
				<h3>Select Accounts to Merge</h3>

				<?php echo "<form method=\"post\" id=\"accountselect\" action=\"".web::constructURL("Utility", "AccountMerge", array(''), 'html')."\"/>"?>
				<input type="hidden" name="action" id="action" value="" />
				<p>Accounts can only be merged if they are in the same Organization
					Unit, in no groups at all, and have the same name. The form below
					will help you find the accounts, and the 'Prepare' button will make
					these changes.</p>
				<p>After the Queue has emptied, you can then merge the accounts
					together, and add the new AccountOwner back to the correct User
					Groups!</p>

				<table class="table" style="width: 45%">
					<tr>
						<td>Select new:</td>
						<td><input type="hidden" name="owner_id" id="owner_id" /> <input
							type="text" style="width: 14.7em;" name="uname" id="uname" class="span2" />
						</td>
					</tr>

					<tr>
						<td>Selected:</td>
						<td><?php  echo "<textarea rows=\"5\" style=\"width: 14.7em;\" name=\"owners\">".web::escapeHTML($data['owners'])."</textarea>"; ?>
						</td>
					</tr>
					
					<tr>
						<td colspan="2" style="text-align: right"><input type="button" class="btn" value="Prepare" onClick="prepare()" /> <input
							type="button" class="btn" value="Merge" onClick="merge()" /></td>
					</tr>

				</table>


				<?php echo "</form>"; ?>
			</div>
		</div>
	</div>
</div>
