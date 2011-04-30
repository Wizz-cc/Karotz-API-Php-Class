<?php
#
# Php Class for the Karotz Rabbit by Wizz.cc
#
# (c) 2011 - http://wizz.cc
# Bugs to: bug@wizz.cc
#
# Free distribution
# Release 1.0b : 2011-04-10
#   2011-04-26
#	- adding ears_reset()
#	- adding $relative to ears()
#	- adding previous & next to play()
#
# Please post comments to:
#  Karotz Developper group
#  https://groups.google.com/forum/?hl=en#!forum/karotzdev
#
class wizz_karotz_Exception extends Exception {
	# http://php.net/manual/en/language.exceptions.extending.php
	public function __construct ($message=null, $code=0) {
		parent::__construct ($message, $code);
    }
	public function __toString() {
		return __CLASS__ . ": [{$this->code}]: {$this->message}";
	}
  public function customInfo() {
        # return 'what_you_want...';
  }	
}

class wizz_karotz {

	const API_KZ_BASE			= 'http://www.karotz.com/api/karotz/';

	const API_FCT_ASR			= 'asr';
	const API_FCT_CONFIG		= 'config';
	const API_FCT_CHOREOGRAPHY	= 'choreography';
	const API_FCT_EARS			= 'ears';
	const API_FCT_INTERACTIV	= 'interactivemode';
	const API_FCT_LED			= 'led';
	const API_FCT_MULTIMEDIA	= 'multimedia';
	const API_FCT_REMOTEAUTH	= 'remoteauth';	# for future use
	const API_FCT_SPEAK			= 'tts';
	const API_FCT_WEBCAM		= 'webcam';

	const DEFAULT_LANG			= 'FR'; # 'DE', 'EN', 'ES'
	const DEFAULT_VOICE			= 'FR-Carla'; # for future use...

	protected $debug 			= false;

	protected $liveid			= null;
	protected $rest_method		= null;
	# for future use...
	protected $id1	 			= null;
	protected $id2	 			= null;

	protected $url_encoded 		= null;
	protected $api_params 		= array();
	protected $api_response 	= null;
	protected $api_resp_array 	= array();
	protected $api_debug 		= null;
	protected $error 			= null;

	protected $voosmsgid		= null;
	protected $correlationid	= null;
	protected $interactiveid	= null;
	protected $responsecode		= null;
	
	protected $lang 			= null;
	protected $voice 			= null; # For future use...
	protected $left_ear_pos		= 0;
	protected $right_ear_pos	= 0;


	public function __construct($liveid=null, $debug=false, $lang=null, $voice=null) {
		$this->liveid = $liveid;
		$this->id1   = null;
		$this->id2   = null;
		$this->debug = $debug;
		$this->lang  = ($lang)?strtoupper($lang):null; # or self::DEFAULT_LANG;
		$this->voice = ($voice)?$voice:null; # or self::DEFAULT_VOICE;
		if ($debug) $this->api_debug = '[Debug on]&nbsp;'.date('d-m-Y H:i:s');
		$this->api_params = array();
	}

    public function setDebug($debug=true, $clear=false) {
        $this->debug = $debug;
		if ($this->$debug && ($this->api_debug==null || $clear)) $this->api_debug = '[Debug on]&nbsp;'.date('d-m-Y H:i:s');
    }
    public function clearDebug() {
        $this->api_debug = '[Debug on]&nbsp;'.date('d-m-Y H:i:s');
    }
	protected function setError($message) {
        $message = trim($message);
        $this->error = $message;
        if ($this->debug) $this->api_debug .= '<br />err>&nbsp;'.$message;
    }

    public function isDebug() { return $this->debug; }
    public function showDebug() { return $this->api_debug; }
    public function get_api_error() { return $this->error; }
	public function get_api_params() { return $this->api_params; }
	public function get_api_response() { return $this->api_response; }
	public function get_api_resp_array() { return $this->api_resp_array; }

	public function get_voosmsg_id() { return $this->voosmsgid; }
	public function get_voosmsg_correlationid() { return $this->correlationid; }
	public function get_voosmsg_interactiveid() { return $this->interactiveid; }
	public function get_voosmsg_responsecode() { return $this->responsecode; }

	private function build_rest_url() {
		$url  = self::API_KZ_BASE.$this->rest_method.'?';
		if (count($this->api_params)) foreach ($this->api_params as $key => $value) $url .= ($key)?urlencode($key).'='.urlencode($value).'&':'';
		$url .= 'interactiveid='.$this->liveid;
		$this->url_encoded = $url;
        if ($this->debug) $this->api_debug .= '<br />urlencode>&nbsp;'.$url;
		return $url;
	}

	private function call_api($binary = false) {
		$this->voosmsgid = ''; $this->correlationid = null; $this->interactiveid = null;
		$this->responsecode = null; $this->error = null;
		
		$curl = curl_init($this->build_rest_url());
		if (!$curl) {
			$this->api_response = 'cUrl_init() Error'; $this->error = 'cUrl_init() Error';
			$this->responsecode = 'ERROR';
			return $this->responsecode;
		}
		curl_setopt($curl, CURLINFO_HEADER_OUT, true);
		curl_setopt($curl, CURLOPT_HEADER, false);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_BINARYTRANSFER, $binary);
		$result = curl_exec($curl);
		if (curl_errno($curl)) {
			$curl_error = curl_error($curl); curl_close($curl);
			if ($this->debug) $this->api_debug .= '<br />curl>&nbsp;'.$curl_error;
			$this->api_response = $curl_error; $this->error = $curl_error;
			$this->responsecode = 'ERROR';
			return $this->responsecode;
		}
		$curl_info = curl_getinfo($curl);
		if ($this->debug) $this->api_debug .= '<br />Request time:&nbsp;'.$curl_info['total_time'].' sec.'
											. '<br />Request header:&nbsp;'.$curl_info['request_header']
											. '<br />Bytes transfered:&nbsp;'.number_format($curl_info['size_download'], 0, '', ' ');
		curl_close($curl);
		$this->api_response = $result; # store <XML VoosMsg> returned by Kz servers
		$this->responsecode = 'UNDEF';
		try {
			if($result=='') throw @new wizz_karotz_Exception('No xml response!'); else {
				$xml = @new SimpleXMLElement($result);
				$this->voosmsgid = $xml->id;
				$this->correlationid = $xml->correlationId;
				$this->interactiveid = $xml->interactiveId;
				$this->responsecode = $xml->response->code;
				$this->api_resp_array = xml2array($xml);
			}
		} catch(wizz_karotz_Exception $e) { $this->setError($e); }
		  catch(Exception $e) { $this->setError($e->getMessage().' (sev. '.$e->getCode().')'); }
		# return now what_you_want in your favorite format : xml, json, html, delimited ascii... up to you!
		# return ($this->responsecode)?$this->responsecode:$this->api_response;
		# here return the response_code string! OK|ERROR
		if (($this->responsecode != 'OK') && $this->debug) $this->api_debug .= '<br />[Response]'.strip_tags($result).'[/Response]';
		return $this->responsecode;
	}

	public function replay() {
		return $this->call_api();
	}

	# INTERACTIVE MODE
	public function quit() {
		$this->rest_method = self::API_FCT_INTERACTIV;
		unset($this->api_params);
		$this->api_params['action'] = 'stop';
		return $this->call_api();
	}

	# TTS
	public function say($text=null, $lang=null) {
		$this->rest_method = self::API_FCT_SPEAK;
		unset($this->api_params);
		$this->api_params['action'] = ((strtoupper($text)=='STOP') || ($text=='') || ($text==null))?'stop':'speak';
		if ($this->api_params['action']=='speak') {
			$this->api_params['text'] = $text;
			if (strlen($lang)>=2) $this->api_params['lang'] = strtoupper($lang);
				elseif ($this->lang) $this->api_params['lang'] = $this->lang;
		}
		return $this->call_api();
	}

	# EARS
	public function ears($left=null, $right=null, $relative=null) {
		$this->rest_method = self::API_FCT_EARS;
		unset($this->api_params);
		$left	= (int) $left; $right = (int) $right;
		if ($left) $this->api_params['left'] = $left;
		if ($right)	$this->api_params['right'] = $right;
		if ($relative) $this->api_params['relative'] = true;
		return $this->call_api();
	}
	public function ears_reset() {
		$this->rest_method = self::API_FCT_EARS;
		unset($this->api_params);
		$this->api_params['reset'] = true;
		return $this->call_api();
	}

	# LED
	public function led_pulse($color='FFFFFF', $period=3000, $pulse=500) {
		$this->rest_method = self::API_FCT_LED;
		unset($this->api_params);
		$this->api_params['action'] = 'pulse';
		$this->api_params['color']  = $color;
		$this->api_params['period'] = $period;
		$this->api_params['pulse']  = $pulse;
		return $this->call_api();
	}
	public function led_fade($color='FFFFFF', $period=3000) {
		$this->rest_method = self::API_FCT_LED;
		unset($this->api_params);
		$this->api_params['action'] = 'fade';
		$this->api_params['color']  = $color;
		$this->api_params['period'] = $period;
		return $this->call_api();
	}
	public function led_light($color='FFFFFF') {
		$this->rest_method = self::API_FCT_LED;
		unset($this->api_params);
		$this->api_params['action'] = 'light';
		$this->api_params['color'] = $color;
		return $this->call_api();
	}

	# ASR
	public function asr($lang=null) {
		$this->rest_method = self::API_FCT_ASR;
		unset($this->api_params);
		$this->api_params['grammar'] = 'yes,no';
		if (strlen($lang)>=2) $this->api_params['lang'] = strtoupper($lang);
			elseif ($this->lang) $this->api_params['lang'] = $this->lang;
		return $this->call_api();
	}
	
	# MULTIMEDIA
	public function play($url=null) {
		$this->rest_method = self::API_FCT_MULTIMEDIA;
		unset($this->api_params);
		if((strtoupper($url)=='STOP') || ($url=='') || ($url==null)) $this->api_params['action'] = 'stop';
		 elseif(strtoupper($url)=='PAUSE') $this->api_params['action'] = 'pause';
		  elseif(strtoupper($url)=='RESUME') $this->api_params['action'] = 'resume';
		   elseif(strtoupper($url)=='PREVIOUS') $this->api_params['action'] = 'previous';
		    elseif(strtoupper($url)=='NEXT') $this->api_params['action'] = 'next';
		     else { $this->api_params['action'] = 'play'; $this->api_params['url'] = $url; }
		return $this->call_api();
	}

	# USB
	public function usb_unlock() {
		$this->rest_method = self::API_FCT_MULTIMEDIA;
		unset($this->api_params);
		$this->api_params['action'] = 'play';
		$this->api_params['url'] = 'lock::no';
		return $this->call_api();
	}
	public function usb_allsong() {
		$this->rest_method = self::API_FCT_MULTIMEDIA;
		unset($this->api_params);
		$this->api_params['action'] = 'allsong';
		# XML VoosMsg with songs or NULL if response_code <> OK
		# <multimedia><songlist>file1:file2:file3...</songlist></multimedia>
		return ($this->call_api()=='OK')?$this->api_resp_array['multimedia']['songlist']:null;
	}
	public function usb_folder() {
		$this->rest_method = self::API_FCT_MULTIMEDIA;
		unset($this->api_params);
		$this->api_params['action'] = 'folder';
		# <multimedia><folderlist>pop:</folderlist></multimedia>
		return ($this->call_api()=='OK')?$this->api_resp_array['multimedia']['folderlist']:null;
	}
	public function usb_artist() {
		$this->rest_method = self::API_FCT_MULTIMEDIA;
		unset($this->api_params);
		$this->api_params['action'] = 'artist';
		# <multimedia><artistlist>u2:mc:</artistlist></multimedia>
		return ($this->call_api()=='OK')?$this->api_resp_array['multimedia']['artistlist']:null;
	}
	public function usb_genre() {
		$this->rest_method = self::API_FCT_MULTIMEDIA;
		unset($this->api_params);
		$this->api_params['action'] = 'genre';
		# <multimedia><genrelist>world:alternative:</genrelist></multimedia>
		return ($this->call_api()=='OK')?$this->api_resp_array['multimedia']['genrelist']:null;
	}
	public function usb_playlist() {
		$this->rest_method = self::API_FCT_MULTIMEDIA;
		unset($this->api_params);
		$this->api_params['action'] = 'playlist';
		# <multimedia><playlistlist></playlistlist></multimedia>
		return ($this->call_api()=='OK')?$this->api_resp_array['multimedia']['playlistlist']:null;
	}

	# WEBCAM
	public function photo($url=null) {
		if (!$url) return null;
		$this->rest_method = self::API_FCT_WEBCAM;
		unset($this->api_params);
		$this->api_params['action'] = 'photo';
		$this->api_params['url'] = $url;
		return $this->call_api();
	}

	# CHOREOGRAPHY
	public function chor($chor=null) {
		$this->rest_method = self::API_FCT_CHOREOGRAPHY;
		unset($this->api_params);
		$this->api_params['start'] = $chor;
		return $this->call_api();
	}
	public function chor_file($file=null) {
		$this->rest_method = self::API_FCT_CHOREOGRAPHY;
		unset($this->api_params);
		$this->api_params['file'] = $file;
		return $this->call_api();
	}
	public function chor_stop() {
		$this->rest_method = self::API_FCT_CHOREOGRAPHY;
		unset($this->api_params);
		$this->api_params['action'] = 'stop';
		return $this->call_api();
	}

	# CONFIG
	# <ConfigResponse><config><interruptible>true</interruptible><awake>true</awake><name>config</name><uuid>adc58683-xxx</uuid><params><key>awake</key><value>true</value></params><params><key>interruptible</key><value>true</value></params><params><key>permanentTriggerActivator</key><value>false</value></params><params><key>scheduledDateTriggerActivator</key><value>false</value></params><params><key>scheduledTriggerActivator</key><value>false</value></params></config></ConfigResponse>
	public function config() {
		$this->rest_method = self::API_FCT_CONFIG;
		unset($this->api_params);
		$this->api_params[''] = 'none';
		$this->call_api();
		return $this->api_resp_array['config'];
	}

}

function xml2array($xml) {
 $res = array();
 foreach ($xml->children() as $parent => $child) {
  $res[$parent] = xml2array($child)?xml2array($child):$child;
 }
 return $res;
}

?>