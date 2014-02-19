<!DOCTYPE html>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Auth admin panel</title>
<link rel="stylesheet" type="text/css" href="/a/public/css/admin.css" />

<!-- jquery & jquery-ui -->
<script src="/a/public/js/jquery-1.8.3.min.js"></script>
<script src="/a/public/js/jquery-ui-1.9.1.custom.min.js"></script>
<link rel="stylesheet" type="text/css" href="/a/public/css/ui-lightness/jquery-ui-1.9.1.custom.min.css" />
<script type="text/javascript"src="/a/public/js/jquery.backstretch.min.js"></script>

<!--[if IE]>
<link rel="stylesheet" type="text/css" href="css/ie-sucks.css" />
<![endif]-->

<!-- Bootstrap -->
<!-- Requires jQuery to be loaded already -->
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" type="text/css" href="/a/public/js/bootstrap/css/bootstrap.min.css" />
<script src="/a/public/js/bootstrap/js/bootstrap.min.js"></script>
<script>
	$(function(){
		<?php 
		if($form == 'login' || $form == 'account') {
			echo "\$.backstretch( \"/a/public/bg.jpg\" );";
		}?>
	});
</script>


<style type="text/css">
	body {
		background: #fafafa;
	}
	div.page {
		background: white;
		padding: 40px 0 40px 0;
	}
	.table-bordered td, .table-bordered th {
		padding: 0.5em;
	}
	
	div.login {
		text-align: center;
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
	<div class="container" style="margin-top: 20px;">
		<div class="row">
			<div class="span12">
				<div class="navbar">
				  <div class="navbar-inner" style="text-align: center;">
				    <a class="brand" href="#" style="margin-left: 0;">Auth</a>
    						<?php if($form != 'login') {
							echo "<a href=\"?action=logout\" class=\"btn btn-primary pull-right\">Log Out</a>";
						} ?>
				  </div>
				</div>
			</div>
		</div>
		<?php include(dirname(__FILE__) . "/form/$form.inc"); ?>
	</div>
</body>
</html>

<?php exit(); ?>



















<html>
<head>
<title>Auth</title>
<link rel="stylesheet" type="text/css" href="/a/public/css/admin.css" />
<link rel="stylesheet" type="text/css"
	href="/a/public/css/ui-lightness/jquery-ui-1.9.1.custom.min.css" />
<script type="text/javascript" src="/a/public/js/jquery-1.8.3.min.js"></script>
<script type="text/javascript"
	src="/a/public/js/jquery.backstretch.min.js"></script>
<script src="/a/public/js/jquery-ui-1.9.1.custom.min.js"></script>

<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" type="text/css"
	href="/a/public/js/bootstrap/css/bootstrap.min.css">
<script type="text/javascript"
	src="/a/public/js/bootstrap/js/bootstrap.min.js"></script>

<script type="text/javascript">
			$(function(){
				//$.backstretch( "/a/public/bg.jpg" );
			});
		</script>
<style>
.ui-autocomplete-loading {
	background: white url('/auth/img/icons/ui-anim_basic_16x16.gif') right
		center no-repeat;
}

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

body {
	background: #fafafa;
}

div.page {
	background: white;
	padding: 40px 0 40px 0;
}
</style>
</head>
<body>
	<div class="container" style="margin-top: 20px;">
		<div class="row">
			<div class="span12">
				<div class="navbar">
					<div class="navbar-inner" style="text-align: center;">

						<?php if($form != 'login') {
							echo "<a href=\"?action=logout\" class=\"btn btn-primary pull-right\">Log Out</a>";
						} ?>
						<a class="brand" href="?" style="margin-left: 0; float: none;">Auth</a>
					</div>
				</div>
			</div>
		</div>


	</div>
</body>
</html>
