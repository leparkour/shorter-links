<?php
defined('TheEnd') || die('Oops, has error!');
?>
<!DOCTYPE html>
<html>
    <head>
		<meta charset="utf-8">
		<meta http-equiv="X-UA-Compatible" content="IE=edge">
		<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0">
		
		<title>{{title}}</title>
		<meta name="keywords" content="{{keywords}}" />
		<meta name="description" content="{{description}}">
		<meta name="author" content="TheEnd">
		
		<link rel="shortcut icon" href="/favicon.ico" type="image/x-icon">
		<link rel="icon" href="/favicon.ico" type="image/x-icon">
		
		{{css}}
    </head>

    <body>
		{{content}}

		{{js}}
	</body>
</html>
