<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

$go = false;
if($_SERVER['REQUEST_METHOD'] == 'POST')
{
	if(empty($_FILES['captcha_gfx']['tmp_name']))
	{
		echo 'Please select a file!<br />';
	}
	else
	{
		$go = true;
	}
}

if($go === true)
{
	include dirname(__FILE__).'/includes/constants.inc.php';
	include LIBS . 'class.decaptcha.php';

	move_uploaded_file($_FILES['captcha_gfx']['tmp_name'], TEMP_FOLDER . 'some_captcha.jpg');

	$Decaptcha = new Decaptcha(TEMP_FOLDER . 'some_captcha.jpg');
	$solution = $Decaptcha->solve();
}
?>

<html>
	<head>
		<title>Decaptcha Solve</title>
	</head>

	<body>
		<form method="post" action="<?php echo $_SERVER['REQUEST_URI']; ?>" enctype="multipart/form-data">
			Captcha File: <input type="file" name="captcha_gfx" /><br /><br />
			<input type="submit" value="Solve" />
		</form>
	</body>
</html>