<?php 
 
  require_once __DIR__ . '/facebook-php-sdk-v4/src/Facebook/autoload.php';
  session_start();

  $fb = new Facebook\Facebook([
  'app_id' => '275920526192446',
  'app_secret' => '793a9193cd90cd55f12bc96cad64c823',
  'default_graph_version' => 'v2.5',
]);

$helper = $fb->getRedirectLoginHelper();
$permissions = ['email', 'user_likes']; // optional
$loginUrl = $helper->getLoginUrl('http://http://flixfox.000webhostapp.com/login-callback.php', $permissions);

echo '<a href="' . $loginUrl . '">Log in with Facebook!</a>';

?>