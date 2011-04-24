<html>
<head><title>Wizz.cc - full Php Class for Karotz API</title></head>
<body>
<h3><a href="http://blog.wizz.cc/" target="Wizz.cc">Wizz.cc Karotz Php Class</a></h3>
<?php
define('MODE_DEBUG', true);
 
 # On postule que l'interactiveID  est déjà récupéré et mis en session par exemple
 # Postulate : the interactiveID is already stored in a session
 #
 $interID = $_SESSION['interactiveid']; # or $_GET['interactiveid'];
 
 # 1. Include Class
 #
 include('wizz.cc_karotz_class.php');

 # 2. Create the Kz object
 #
 $_k = new wizz_karotz($interID, MODE_DEBUG); # true for debug mode

 # 3. Send one or more commands...
 #
 echo '[TTS]: <code>'.$_k->say('quoi de neuf docteur', 'fr').'</code><br />';
 echo '[EARS]: <code>'.$_k->ears(100, 240).'</code><br />';
 # echo '[LED]: <code>'.$_k->led_light('FF0000', 3000).'</code><br />';
 # echo '[RADIO]: <code>'.$_k->play('http://zenradio.fr:8800/').'</code><br />';
 # echo '[WEBCAM]: <code>'.$_k->photo('http://wizz.cc/karotz/_wrap_photo.php').'</code><br />';
 # echo '[CONFIG]: <code>'; print_r($_k->config()); echo '</code><br />';

 # Radio pause/resume/stop
 # $_k->play('pause'); $_k->play('resume'); $_k->play('stop');

 # echo '[USB-unlock] : <code>'.$_k->play('lock::no').'</code><br />';
 # echo '[USB-playallsongs] : <code>'.$_k->play('allsong::').'</code><br />';
 # echo '[USB-playsong] : <code>'.$_k->play('title::thewall').'</code><br />';
 # echo '[USB-playartist] : <code>'.$_k->play('artist::ladygaga').'</code><br />';
 # echo '[USB-playfolder] : <code>'.$_k->play('dir::pop').'</code><br />';
 # echo '[USB-playgenre] : <code>'.$_k->play('genre::alternative').'</code><br />';
 
 # Usb MP3 list... allsong|folder|artist|genre|playlist
 # $str = $_k->usb_folder(); $str = $_k->usb_artist(); $str = $_k->usb_genre(); $str = $_k->usb_playlist();
 $str = $_k->usb_allsong();
 echo '[USB List AllSongs]: <code>'.$str.'</code><br />';
 $items = explode(':', $str); $nb = count($items);
 if ($nb>=0) {
  echo '<ul>';
  foreach ($items as $item) echo '<li>'.$item.'</li>';
  echo '</ul>';
 }

 # 4. Check result if necessary
 #
if (MODE_DEBUG) {
 echo '<p><b>The returned VoosMsg:</b></p>';
 echo '<ul><li>VoosMsgID = ['.$_k->get_voosmsg_id().']</li>';
 echo '<li>CorrelationID = ['.$_k->get_voosmsg_correlationid().']</li>';
 echo '<li>InteractiveID = ['.$_k->get_voosmsg_interactiveid().']</li>';
 echo '<li>ResponseCode = ['.$_k->get_voosmsg_responsecode().']</li></ul>';
 echo '<hr>';

 echo '<p><b>The API parameters:</b></p>';
 print_r($_k->get_api_params());
 echo '<hr>';

 echo '<p><b>The API response (raw):</b></p>';
 echo '<pre>'.$_k->get_api_response().'</pre>';
 echo '<hr>';

 echo '<p><b>The API response (array):</b></p>';
 print_r($_k->get_api_resp_array());
 echo '<hr>';

 echo '<p><b>The API Error:</b></p>';
 echo '<pre>'.strip_tags($_k->get_api_error()).'</pre>';
 echo '<hr>';

 echo '<p><b>The Class Debug:</b></p>';
 echo $_k->showDebug();
 echo '<hr>';
}

 sleep(1); $_k->quit();

?>
</body>
</html>
