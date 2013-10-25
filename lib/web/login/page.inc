<html>
	<head>
		<script type="text/javascript" src="/a/public/js/jquery-1.8.3.min.js"></script>
		<script type="text/javascript" src="/a/public/js/jquery.backstretch.min.js"></script>
		<script type="text/javascript" src="/a/public/js/bootstrap/js/bootstrap.min.js"></script>
		<link rel="stylesheet" type="text/css" href="/a/public/js/bootstrap/css/bootstrap.min.css">
		<script type="text/javascript">
			$(function(){
				$.backstretch( "/a/public/bg.jpg" );
			});
		</script>
		<style>
			div.login {
				text-align: center;
				margin-top: 3em;
			}
			div.login div.span4 {
				margin: 0 auto;
				float: none;
			}
			div.login form {
				margin-bottom: 0;
			}
			div.login-fail {
				font-weight: bold;
				color: #a00;
			}
			div.login-ok {
				font-weight: bold;
				color: #0a0;
			}
			div.login table {
				font-size: 14px !important;
			}
		</style>
	</head>
	<body>
		<div class="container">
			<div class="navbar">
			  <div class="navbar-inner" style="text-align: center;">
		  		
			    <?php if($form != 'login') {
					 echo "<a href=\"?action=logout\" class=\"btn btn-primary pull-right\">Log Out</a>";
					} ?>
				<a class="brand" href="?" style="margin-left: 0; float: none;">Auth</a>
			  </div>
			</div>
		</div>
		<?php include(dirname(__FILE__) . "/form/$form.inc"); ?>
	</body>
</html>
