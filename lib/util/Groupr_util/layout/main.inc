<?php include(dirname(__FILE__)."/../../../web/view/layout/snippet/group_selector.php"); 
	  ?>

<style type="text/css">
#txtarea {
display:block;
width:25%;
padding:0.5%;
margin:0;
border:1px solid #fbeed5;
background-color:#fcf8e3;
overflow:auto;
}
</style>

<div class="container">
	<div class="row">
		<div class="span4">
			<div class="well">
				<p>
					<b>What does this do?</b>
				</p>
				<p>This utility is for batch resetting passwords for an entire group. Each member has their password reset and output in an excel/csv compatible format. 

This utility only resets the passwords of the direct members of the defined group and not that of members of any subgroups.

</p>
				<dl>
					<dt></dt>
					<dd></dd>
					<dt></dt>
					<dd></dd>
				</dl>
			</div>
		</div>
		<div class="span8">
			<div class="alert alert-info">
				<h3>Group Reset</h3>
			</div>
			<?php
			if(isset($data['message'])) {
				echo "<div class=\"alert\">".web::escapeHTML($data['message']). ' has been reset.' . "</div>";
			}
			?>
			<div class="well">

				<h3>Feature</h3>
				<h4>Group:</h4>
				<?php echo "<form onsubmit=\"return confirm('Do you really want to reset all these passwords?');\" onkeypress=\"return event.keyCode != 13;\" method=\"post\" action=\"".web::constructURL("Utility", "Groupr", array(''), 'html')."\"/>"?>
				
				<div class="input-append">
					<input type="hidden" name="group_cn" id="group_cn" />
					<input type="text" style="width: 14.7em;" name="gname" id="gname" class="span2" />
					<input type="submit" class="btn" value="Reset" />
				</div>
				<div class="input-group">
					<label class="checkbox inline">
						<input type="checkbox" name="print"/> Print account details
					</label>
				</div>
				<?php echo "</form>"; ?>
			</div>
			
				<?php if(isset($data['passwrd'])) {
				echo "<div class=\"well\">";
				echo "<textarea id=\"txtarea\" cols=\"1\" rows=\"15\" >";
					foreach($data['passwrd'] as $account_login => $password){
 						
   						echo web::escapeHTML("$account_login, $password\n");
    						
				     	}
				echo "</textarea>";
				echo "</div>";
    				}
			?>

		</div>
	</div>
</div>
