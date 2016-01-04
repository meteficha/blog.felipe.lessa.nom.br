<!DOCTYPE html>
	<!--[if IE 8]>
		<html xmlns="http://www.w3.org/1999/xhtml" class="ie8" lang="en-US">
	<![endif]-->
	<!--[if !(IE 8) ]><!-->
		<html xmlns="http://www.w3.org/1999/xhtml" lang="en-US">
	<!--<![endif]-->
	<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
	<title>Felipe Lessa &rsaquo; Log In</title>
	<link rel='stylesheet' id='open-sans-css'  href='http://fonts.googleapis.com/css?family=Open+Sans%3A300italic%2C400italic%2C600italic%2C300%2C400%2C600&amp;subset=latin%2Clatin-ext&amp;ver=3.8.5' type='text/css' media='all' />
<link rel='stylesheet' id='dashicons-css'  href='wp-includes/css/dashicons.min.css?ver=3.8.5' type='text/css' media='all' />
<link rel='stylesheet' id='wp-admin-css'  href='wp-admin/css/wp-admin.min.css?ver=3.8.5' type='text/css' media='all' />
<link rel='stylesheet' id='buttons-css'  href='wp-includes/css/buttons.min.css?ver=3.8.5' type='text/css' media='all' />
<link rel='stylesheet' id='colors-fresh-css'  href='wp-admin/css/colors.min.css?ver=3.8.5' type='text/css' media='all' />
<!--[if lte IE 7]>
<link rel='stylesheet' id='ie-css'  href='http://blog.felipe.lessa.nom.br/wp-admin/css/ie.min.css?ver=3.8.5' type='text/css' media='all' />
<![endif]-->
<meta name='robots' content='noindex,follow' />
	</head>
	<body class="login login-action-login wp-core-ui">
	<div id="login">
		<h1><a href="http://wordpress.org/" title="Powered by WordPress">Felipe Lessa</a></h1>
	
<form name="loginform" id="loginform" action="wp-login.php" method="post">
	<p>
		<label for="user_login">Username<br />
		<input type="text" name="log" id="user_login" class="input" value="" size="20" /></label>
	</p>
	<p>
		<label for="user_pass">Password<br />
		<input type="password" name="pwd" id="user_pass" class="input" value="" size="20" /></label>
	</p>
	<hr id="openid_split" style="clear: both; margin-bottom: 1.0em; border: 0; border-top: 1px solid #999; height: 1px;" />
	<p style="margin-bottom: 8px;">
		<label style="display: block; margin-bottom: 5px;">Or login using an OpenID<br />
		<input type="text" name="openid_identifier" id="openid_identifier" class="input openid_identifier" value="" size="20" tabindex="25" /></label>
	</p>

	<p style="font-size: 0.9em; margin: 8px 0 24px 0;" id="what_is_openid">
		<a href="http://openid.net/what/" target="_blank">Learn about OpenID</a>
	</p>	<p class="forgetmenot"><label for="rememberme"><input name="rememberme" type="checkbox" id="rememberme" value="forever"  /> Remember Me</label></p>
	<p class="submit">
		<input type="submit" name="wp-submit" id="wp-submit" class="button button-primary button-large" value="Log In" />
		<input type="hidden" name="redirect_to" value="http://blog.felipe.lessa.nom.br/wp-admin/" />
		<input type="hidden" name="testcookie" value="1" />
	</p>
</form>

<p id="nav">
	<a href="wp-login.php?action=lostpassword" title="Password Lost and Found">Lost your password?</a>
</p>

<script type="text/javascript">
function wp_attempt_focus(){
setTimeout( function(){ try{
d = document.getElementById('user_login');
d.focus();
d.select();
} catch(e){}
}, 200);
}

wp_attempt_focus();
if(typeof wpOnload=='function')wpOnload();
</script>

	<p id="backtoblog"><a href="index.html" title="Are you lost?">&larr; Back to Felipe Lessa</a></p>
	
	</div>

	
	<link rel='stylesheet' id='openid-css'  href='wp-content/plugins/openid/f/openid.css?ver=519' type='text/css' media='all' />
	<div class="clear"></div>
	</body>
	</html>
	