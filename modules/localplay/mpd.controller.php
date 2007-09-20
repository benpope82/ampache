<?php
/*

 Copyright 2001 - 2007 Ampache.org
 All Rights Reserved

 This program is free software; you can redistribute it and/or
 modify it under the terms of the GNU General Public License v2
 as published by the Free Software Foundation.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with this program; if not, write to the Free Software
 Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA. 

*/

/**
 * AmpacheMpd Class
 * the Ampache Mpd Controller, this is the glue between
 * the MPD class and the Ampahce Localplay class
 */
class AmpacheMpd extends localplay_controller {

	/* Variables */
	private $version 	= '000001'; 
	private $description	= 'Controls an instance of MPD'; 

	/* Constructed variables */
	private $_mpd;

	/**
	 * Constructor
	 * This returns the array map for the localplay object
	 * REQUIRED for Localplay
	 */
	public function __construct() { 
	
		/* Do a Require Once On the needed Libraries */
		require_once Config::get('prefix') . '/modules/mpd/mpd.class.php';

	} // AmpacheMpd

	/**
	 * get_description
	 * Returns the description
	 */
	public function get_description() { 

		return $this->description; 
	
	} // get_description

	/**
	 * get_version
	 * This returns the version information
	 */
	public function get_version() { 

		return $this->version; 

	} // get_version

	/**
	 * is_installed
	 * This returns true or false if MPD controller is installed
	 */
	public function is_installed() { 

                $sql = "DESCRIBE `localplay_mpd`";
                $db_results = Dba::query($sql);

                return Dba::num_rows($db_results);

	} // is_installed

	/**
	 * install
	 * This function installs the MPD localplay controller
	 */
	public function install() { 

                /* We need to create the MPD table */
                $sql = "CREATE TABLE `localplay_mpd` ( `id` INT( 11 ) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY , " .
                        "`name` VARCHAR( 128 ) NOT NULL , " .
                        "`owner` INT( 11 ) NOT NULL , " .
                        "`host` VARCHAR( 255 ) NOT NULL , " .
                        "`port` INT( 11 ) UNSIGNED NOT NULL DEFAULT '6600', " .
                        "`password` VARCHAR( 255 ) NOT NULL , " .
                        "`access` SMALLINT( 4 ) UNSIGNED NOT NULL DEFAULT '0'" .
                        ") ENGINE = MYISAM";
                $db_results = Dba::query($sql);
		
		// Add an internal preference for the users current active instance
		Preference::insert('mpd_active','MPD Active Instance','0','25','integer','internal'); 
		User::rebuild_all_preferences(); 

                return true;

	} // install

	/**
	 * uninstall
	 * This removes the localplay controller 
	 */
	public function uninstall() { 

                $sql = "DROP TABLE `localplay_mpd`";
                $db_results = Dba::query($sql);

		Preference::delete('mpd_active'); 

                return true;

	} // uninstall

	/**
	 * add_instance
	 * This takes key'd data and inserts a new MPD instance
	 */
	public function add_instance($data) { 

		foreach ($data as $key=>$value) { 
			switch ($key) { 
				case 'name': 
				case 'hostname': 
				case 'port': 
				case 'password': 
					${$key} = Dba::escape($value); 
				break;
				default: 

				break;
			} // end switch 
		} // end foreach

		$user_id = Dba::escape($GLOBALS['user']->id); 

		$sql = "INSERT INTO `localplay_mpd` (`name`,`host`,`port`,`password`,`owner`) " . 
			"VALUES ('$name','$hostname','$port','$password','$user_id')";
		$db_results = Dba::query($sql); 
		
		return $db_results; 

	} // add_instance

	/**
 	 * delete_instance
	 * This takes a UID and deletes the instance in question
	 */
	public function delete_instance($uid) { 


	} // delete_instance

	/**
 	 * get_instances
	 * This returns a key'd array of the instance information with 
	 * [UID]=>[NAME]
	 */
	public function get_instances() { 

		$sql = "SELECT * FROM `localplay_mpd`"; 
		$db_results = Dba::query($sql); 

		$results = array(); 

		while ($row = Dba::fetch_assoc($db_results)) { 
			$results[$row['id']] = $row['name']; 
		} 

		return $results; 

	} // get_instances

	/**
	 * get_instance
	 * This returns the specified instance and all it's pretty variables
	 */
	private function get_instance($instance) { 
	
		$instance = Dba::escape($instance); 

		$sql = "SELECT * FROM `localplay_mpd` WHERE `id`='$instance'";  
		$db_results = Dba::query($sql); 

		$row = Dba::fetch_assoc($db_results); 
		
		return $row; 

	} // get_instance

	/**
	 * instance_fields
	 * This returns a key'd array of [NAME]=>array([DESCRIPTION]=>VALUE,[TYPE]=>VALUE) for the
	 * fields so that we can on-the-fly generate a form
	 */
	public function instance_fields() { 

		$fields['name'] 	= array('description'=>_('Instance Name'),'type'=>'textbox'); 
		$fields['hostname'] 	= array('description'=>_('Hostname'),'type'=>'textbox'); 
		$fields['port']		= array('description'=>_('Port'),'type'=>'textbox'); 
		$fields['password']	= array('description'=>_('Password'),'type'=>'textbox'); 

		return $fields; 

	} // instance_fields

	/**
	 * set_active_instance
	 * This sets the specified instance as the 'active' one
	 */
	public function set_active_instance($uid,$user_id='') { 

		// Not an admin? bubkiss!
		if (!$GLOBALS['user']->has_access('100')) { 
			$user_id = $GLOBALS['user']->id; 
		} 

		$user_id = $user_id ? $user_id : $GLOBALS['user']->id; 

		Preference::update('mpd_instance',$user_id,intval($uid)); 

		return true; 

	} // set_active_instance	

	/**
	 * get_active_instance
	 * This returns the UID of the current active instance
	 * false if none are active
	 */
	public function get_active_instance() { 


	} // get_active_instance

	/**
	 * add
	 * This takes a single object and adds it in, it uses the built in 
	 * functions to generate the URL it needs
	 */
	public function add($object) { 
		
		if (is_null($this->_mpd->ClearPLIfStopped())) {
	                debug_event('mpd_add', 'Error: Unable to clear the MPD playlist ' . $this->_mpd->errStr,'1');
         	}

		$url = $this->get_url($object); 

		if (is_null($this->_mpd->PlAdd($url))) { 
			debug_event('mpd_add',"Error: Unable to add $url to MPD " . $this->_mpd->errStr,'1'); 
			return false; 
		} 
		
		return true;

	} // add_songs

	/**
	 * delete_songs
	 * This must take an array of ID's (as passed by get function) from Ampache
	 * and delete them from MPD
	 */
	public function delete($objects) { 

		/* Default to true */
		$return = true;

		/* This should be an array of UID's as returned by
		 * the get function so that we can just call the class based 
		 * functions to remove them or if there isn't a uid for 
		 * the songs, then however ya'll have stored them
		 * in this controller 
		 */
		foreach ($songs as $uid) { 

			if (is_null($this->_mpd->PLRemove($uid))) { $return = false; } 

		} // foreach of songs

		return $return;

	} // delete_songs
	
	/**
	 * clear_playlist
	 * This deletes the entire MPD playlist... nuff said
	 */
	function clear_playlist() { 

		if (is_null($this->_mpd->PLClear())) { return false; }

		return true;

	} // clear_playlist

	/**
	 * play
	 * This just tells MPD to start playing, it does not
	 * take any arguments
	 */
	function play() { 

		if (is_null($this->_mpd->Play())) { return false; } 
		return true;

	} // play

	/**
	 * stop
	 * This just tells MPD to stop playing, it does not take
	 * any arguments
	 */
	function stop() { 

		if (is_null($this->_mpd->Stop())) { return false; } 
		return true;

	} // stop

	/**
	 * skip
	 * This tells MPD to skip to the specified song
	 */
	function skip($song) { 

		if (is_null($this->_mpd->SkipTo($song))) { return false; }
		return true; 

	} // skip

	/**
	 * This tells MPD to increase the volume by 5
	 */
	function volume_up() { 

		if (is_null($this->_mpd->AdjustVolume('5'))) { return false; } 
		return true;

	} // volume_up

	/**
	 * This tells MPD to decrese the volume by 5
	 */
	function volume_down() { 

		if (is_null($this->_mpd->AdjustVolume('-5'))) { return false; }
		return true;
		
	} // volume_down

	/**
	 * next
	 * This just tells MPD to skip to the next song 
	 */
	function next() { 

		if (is_null($this->_mpd->Next())) { return false; } 
		return true;

	} // next

	/**
	 * prev
	 * This just tells MPD to skip to the prev song
	 */
	function prev() { 

		if (is_null($this->_mpd->Previous())) { return false; } 
		return true;
	
	} // prev

	/**
	 * pause
	 * This tells MPD to pause the current song 
	 */
	function pause() { 
		
		if (is_null($this->_mpd->Pause())) { return false; } 
		return true;

	} // pause 


        /**
        * volume
        * This tells MPD to set the volume to the parameter
        */
       function volume($volume) {

               if (is_null($this->_mpd->SetVolume($volume))) { return false; }
               return true;

       } // volume

       /**
        * loop
        * This tells MPD to set the repeating the playlist (i.e. loop) to either on or off
        */
       function loop($state) {
	
		if (is_null($this->_mpd->SetRepeat($state))) { return false; }
       		return true;

       } // loop


       /**
        * random
        * This tells MPD to turn on or off the playing of songs from the playlist in random order
        */
       function random($onoff) {

               if (is_null($this->_mpd->SetRandom($onoff))) { return false; }
               return true;

       } // random

       /**
        * move
        * This tells MPD to move song from SrcPos to DestPos
        */
       function move($SrcPos, $DestPos) {

		if (is_null($this->_mpd->PLMoveTrack($SrcPos, $DestPos))) { return false; }

        	return true;
	} // move

	/**
	 * get_songs
	 * This functions returns an array containing information about
	 * The songs that MPD currently has in it's playlist. This must be
	 * done in a standardized fashion
	 */
	public function get() { 

		/* Get the Current Playlist */
		$playlist = $this->_mpd->playlist;
		
		foreach ($playlist as $entry) { 
			$data = array();

			/* Required Elements */
			$data['id'] 	= $entry['Pos'];
			$data['raw']	= $entry['file'];		

			/* Parse out the song ID and then create the song object */
			preg_match("/song=(\d+)\&/",$entry['file'],$matches);

			/* Attempt to build the new song */
			$song = new Song($matches['1']);
			
			/* If we don't know it, look up by filename */
			if (!$song->title) { 
				$filename = sql_escape($entry['file']);
				$sql = "SELECT id FROM song WHERE file LIKE '%$filename'";
				$db_results = mysql_query($sql, dbh());
				if ($r = mysql_fetch_assoc($db_results)) { 
					$song = new Song($r['id']);
				}	
				else { 
					$song->title = _('Unknown');
				}
			}

			/* Make the name pretty */
			$song->format_song();
			$data['name']	= $song->f_title . ' - ' . $song->f_album . ' - ' . $song->f_artist;

			/* Optional Elements */
			$data['link']   = '';
			$data['track']	= $entry['Pos']+1;

			$results[] = $data;

		} // foreach playlist items
		
		return $results;

	} // get_songs

	/**
	 * get_status
	 * This returns bool/int values for features, loop, repeat and any other features
	 * That this localplay method support
	 */
	public function status() { 

		$track = $this->_mpd->current_track_id;

		/* Construct the Array */
		$array['state'] 	= $this->_mpd->state;
		$array['volume']	= $this->_mpd->volume;
		$array['repeat']	= $this->_mpd->repeat;
		$array['random']	= $this->_mpd->random;
		$array['track']		= $track+1;

		preg_match("/song=(\d+)\&/",$this->_mpd->playlist[$track]['file'],$matches);
		$song_id = $matches['1'];
		$song = new Song($song_id);
		$array['track_title'] 	= $song->title;
		$array['track_artist'] 	= $song->get_artist_name();
		$array['track_album']	= $song->get_album_name();

		return $array;

	} // get_status

	/**
	 * connect
	 * This functions creates the connection to MPD and returns
	 * a boolean value for the status, to save time this handle
	 * is stored in this class
	 */
	public function connect() { 
	
		// Look at the current instance and pull the options for said instance
		$options = self::get_instance($GLOBALS['user']->prefs['mpd_active']); 
		$this->_mpd = new mpd($options['host'],$options['port'],$options['password']);

		if ($this->_mpd->connected) { return true; } 

		return false;

	} // connect
	
} //end of AmpacheMpd

?>
