<?php
use Auth\web\Web;
?>
<?php include(dirname(__FILE__)."/../snippet/account_selector.php"); ?>
<?php include(dirname(__FILE__)."/../snippet/group_selector.php"); ?>
<div id="rightnow">
	<div class="alert alert-info">
		<h3 class="reallynow">
			<span>Search</span>
		</h3>
	</div>

	<div class="well">
		<form style="text-align: center; margin: 3em;<?php if(isset($data['selected']) && $data['selected'] == 'group') { echo " display: none"; } ?>" method="post" id="accountselect">
			<p>
			<?php
			if(isset($data['message'])) {
				echo "<b>".Web::escapeHTML($data['message']) . "</b>";
			}
			?>
			</p>

			<div class="input-append">
				<input type="hidden" name="owner_id" id="owner_id" /> 
				<div class="btn-group">
					<a class="btn dropdown-toggle" data-toggle="dropdown" href="#">
						User <span class="caret"></span>
					</a>
					<ul class="dropdown-menu">
						<li><a href="#" onClick="return switchUser();">User</a></li>
						<li><a href="#" onClick="return switchGroup();">Group</a></li>
					</ul>
				</div><input
					type="text" name="uname" id="uname" class="span2" />
				<button class="btn" type="submit">Go</button>
			</div>
		</form>

		<form style="text-align: center; margin: 3em;<?php if(!isset($data['selected']) || $data['selected'] != 'group') { echo " display: none"; } ?>" method="post" id="groupselect">
			<p>
			<?php
			if(isset($data['message'])) {
				echo "<b>".Web::escapeHTML($data['message']) . "</b>";
			}
			?>
			</p>

			<div class="input-append">
				<input type="hidden" name="selected" value="group" />
				<input type="hidden" name="group_cn" id="group_cn" />
				<div class="btn-group">
					<a class="btn dropdown-toggle" data-toggle="dropdown" href="#">
						Group <span class="caret"></span>
					</a>
					<ul class="dropdown-menu">
						<li><a href="#" onClick="return switchUser();">User</a></li>
						<li><a href="#" onClick="return switchGroup();">Group</a></li>
					</ul>
				</div>
				<input type="text" name="gname" id="gname" class="span2" />
				<button class="btn" type="submit">Go</button>
			</div>
		</form>
	</div>
</div>

<script>
	// Set focus on search box
	$("#<?php if(!isset($data['selected']) || $data['selected'] != 'group') { echo "uname"; } else { echo "gname"; } ?>").focus();

	function switchUser() {
		$('#groupselect').hide();
		$('#accountselect').show();
		$('#uname').focus();
		return false;
	}

	function switchGroup() {
		$('#accountselect').hide();
		$('#groupselect').show();
		$('#gname').focus();
		return false;
	}
</script>
