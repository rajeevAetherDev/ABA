<!DOCTYPE html>
<html class=" ">
    <head>
        <meta http-equiv="content-type" content="text/html;charset=UTF-8" />
        <?php  echo $this->Html->charset('utf-8'); ?>
        <title>ABA SUFI HOUSE</title>
        <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" />
        <meta content="" name="description" />
        <meta content="" name="author" />
        <?php echo $this->Html->meta('favicon.png',  '/assets/images/favicon.png',  array('type' => 'icon', 'rel'=>'shortcut icon')  ); ?><!-- Favicon -->
        <link rel="apple-touch-icon-precomposed" href="assets/images/apple-touch-icon-57-precomposed.png">	<!-- For iPhone -->
        <link rel="apple-touch-icon-precomposed" sizes="114x114" href="assets/images/apple-touch-icon-114-precomposed.png">    <!-- For iPhone 4 Retina display -->
        <link rel="apple-touch-icon-precomposed" sizes="72x72" href="assets/images/apple-touch-icon-72-precomposed.png">    <!-- For iPad -->
        <link rel="apple-touch-icon-precomposed" sizes="144x144" href="assets/images/apple-touch-icon-144-precomposed.png">    <!-- For iPad Retina display -->

        <?php echo $this->Html->css(array('../assets/css/script.css','../assets/css/bootstrap.min.css', '../assets/css/jquery-ui.min.css', '../assets/css/icons.min.css','../assets/css/metisMenu.min.css','../assets/css/app.min.css')); ?>
        <?php echo $this->Html->script(array('../assets/js/jquery.min.js', '../assets/js/jquery-ui.min.js','../assets/js/bootstrap.bundle.min.js', '../assets/js/metismenu.min.js','../assets/js/waves.js', '../assets/js/feather.min.js', '../assets/js/jquery.slimscroll.min.js')); ?>
        </head>
    <body class=" login_page">
        <?php echo $this->Flash->render() ?>
        <?php echo $this->fetch('content'); ?>
    </body>
</html>



