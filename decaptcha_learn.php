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
	elseif(empty($_POST['captcha_solution']))
	{
		echo 'Please provide captcha solution!';
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

	move_uploaded_file($_FILES['captcha_gfx']['tmp_name'], TEMP_FOLDER . 'learning_captcha.jpg');

	$Decaptcha = new Decaptcha(TEMP_FOLDER . 'learning_captcha.jpg');
	$Decaptcha->training($_POST['captcha_solution']);
	echo 'training done...<br />';
}
?>

<html>
	<head>
		<title>Decaptcha Learn</title>
	</head>

	<body>
		<form method="post" action="<?php echo $_SERVER['REQUEST_URI']; ?>" enctype="multipart/form-data">
			Captcha File: <input type="file" name="captcha_gfx" /><br /><br />
			Captcha Solution: <input type="text" name="captcha_solution" /><br /><br />
			<input type="submit" value="Save" />
		</form>
	</body>
</html>