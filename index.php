<?php header('Content-type: text/html'); header('Access-Control-Allow-Origin: *'); ?>

<?php
	if (isset($_POST['check_u'])) {
		include_once("php_includes/db_conx.php");
		
		#preg_replace ensures no invalid username characters were passed
		#this is a safety net incase someone bypasses the RegEx javascript on the app page
		$username = preg_replace('#[^a-z_0-9]#i', '', $_POST['check_u']);
		$sql = "SELECT id FROM users WHERE username='$username' LIMIT 1";
		$query = mysqli_query($db_conx, $sql);
		$uname_check = mysqli_num_rows($query);
		
		if (strlen($username) < 3 || strlen($username) > 16) {
			
			echo "uname_length_error"; #this will be error code for username length error
									   #user prompted to enter a username with length btn 3 and 16 characters
			exit;				
		} 
		
		if ($uname_check < 1) {
			#username is unique and valid... no error msg
			echo "uname_unique"; 
		} else {
			#username exists will prompts user to enter another one 
			echo "uname_exists";
		}
	
	} 
	
	if (isset($_POST['check_e'])) {
		include_once("php_includes/db_conx.php");
		
		#preg_replace ensures no invalid email characters were passed
		#this is a safety net incase someone bypasses the RegEx javascript on the app page 
		$email = preg_replace('#[^a-z0-9_@.]#i', '', $_POST['check_e']);
		$sql = "SELECT id FROM users WHERE email='$email' LIMIT 1";
		$query = mysqli_query($db_conx, $sql);
		$email_check = mysqli_num_rows($query);
		
		#validates if email is formated properly... not thaaaaat strict since user will have to confirm email later.
		$has_art = strpos($email, '@');  #checks for @ sign
		$has_dot = strpos($email, '.');  #checks for .
		if ($has_art === FALSE || has_dot === FALSE) {
			
			echo "email_format_error"; #this will be error code for email_format_error
							#user prompted to enter a valid email address
			exit();		
		} 
		
		if ($email_check < 1) {
			#username is unique and valid
				echo "email_unique"; 
				exit();
			} else {
				#username exists will prompts user to enter another one 
				echo "email_exists";
				exit();
		}

	}
	
?><?php
// Ajax calls this REGISTRATION code to execute
if(isset($_POST["u"])){
	// CONNECT TO THE DATABASE
	include_once("php_includes/db_conx.php");
	// GATHER THE POSTED DATA INTO LOCAL VARIABLES
	$u = preg_replace('#[^a-z_0-9]#i', '', $_POST['u']);
	$e = mysqli_real_escape_string($db_conx, $_POST['e']);
	$p = $_POST['p'];
	$g = preg_replace('#[^a-z]#', '', $_POST['g']);
	$fn = preg_replace('#[^a-z ]#i', '', $_POST['fn']);
	
	
	// DUPLICATE DATA CHECKS FOR USERNAME AND EMAIL
	$sql = "SELECT id FROM users WHERE username='$u' LIMIT 1";
    $query = mysqli_query($db_conx, $sql); 
	$u_check = mysqli_num_rows($query);
	// -------------------------------------------
	$sql = "SELECT id FROM users WHERE email='$e' LIMIT 1";
    $query = mysqli_query($db_conx, $sql); 
	$e_check = mysqli_num_rows($query);
	// FORM DATA ERROR HANDLING
	if($u == "" || $e == "" || $p == "" || $g == "" || $fn == "" ){
		echo "values_missing";
        exit();
	} else if ($u_check > 0){ 
        echo "uname_exists";
        exit();
	} else if ($e_check > 0){ 
        echo "email_exists";
        exit();
	} else if (strlen($u) < 3 || strlen($u) > 16) {
        echo "uname_length_error";
        exit(); 
    } else {
	// END FORM DATA ERROR HANDLING
	    
//-----------PASS ENCRYPTION TOO BASIC & NOT SECURE!!! READ UP, MAKE BETTER------------------------------
		$p_hash = md5($p);
		
		// Add user info into the database table for the main site table
		$sql = "INSERT INTO users (username, email, full_name, password, gender, signup, lastlogin, notescheck)       
		        VALUES('$u','$e','$fn','$p_hash','$g',now(),now(),now())";
		$query = mysqli_query($db_conx, $sql); 
		$uid = mysqli_insert_id($db_conx);
		// Establish their row in the useroptions table
		$sql = "INSERT INTO useroptions (id, username, background) VALUES ('$uid','$u','original')";
		$query = mysqli_query($db_conx, $sql);
		// Create directory(folder) to hold each user's files(pics, MP3s, etc.)
		if (!file_exists("user/$u")) {
			mkdir("user/$u", 0755);
		}
		//MAKE EMAIL ACTIVATION HERE----------------------------------------------------------------------------
		//$to = "$e";							 
		//$from = "auto_responder@yoursitename.com";
		//$subject = 'yoursitename Account Activation';
		//$message = '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>yoursitename Message</title></head><body style="margin:0px; font-family:Tahoma, Geneva, sans-serif;"><div style="padding:10px; background:#333; font-size:24px; color:#CCC;"><a href="http://www.yoursitename.com"><img src="http://www.yoursitename.com/images/logo.png" width="36" height="30" alt="yoursitename" style="border:none; float:left;"></a>yoursitename Account Activation</div><div style="padding:24px; font-size:17px;">Hello '.$u.',<br /><br />Click the link below to activate your account when ready:<br /><br /><a href="http://www.yoursitename.com/activation.php?id='.$uid.'&u='.$u.'&e='.$e.'&p='.$p_hash.'">Click here to activate your account now</a><br /><br />Login after successful activation using your:<br />* E-mail Address: <b>'.$e.'</b></div></body></html>';
		//$headers = "From: $from\n";
        //$headers .= "MIME-Version: 1.0\n";
        //$headers .= "Content-type: text/html; charset=iso-8859-1\n";
		//mail($to, $subject, $message, $headers);
		echo '{"user_id" : '.$uid.', "user_name" : "'. $u .'", "full_name" : "'. $fn .'", "email" : "'. $e .'", "gender" : "'. $g .'", "p_hash" : "'.$p_hash.'", "origin" : "signup" }';
		exit();
	}
	
}
?>


<?php
// AJAX CALLS THIS LOGIN CODE TO EXECUTE
if(isset($_POST["e"])){
	// CONNECT TO THE DATABASE
	include_once("php_includes/db_conx.php");
	// GATHER THE POSTED DATA INTO LOCAL VARIABLES AND SANITIZE
	$e = mysqli_real_escape_string($db_conx, $_POST['e']);
	$p = md5($_POST['p']);
	
	// FORM DATA ERROR HANDLING
	if($e == "" || $p == ""){
		echo "login_values_missing";
        exit();
	} else {
	// END FORM DATA ERROR HANDLING
		$sql = "SELECT id, username, password, full_name FROM users WHERE email='$e' LIMIT 1";
        $query = mysqli_query($db_conx, $sql);
		$e_check = mysqli_num_rows($query);
		if ($e_check < 1){ 
			echo "email_not_in_db";
			exit();
	    }
		
		$row = mysqli_fetch_row($query);

		$db_id = $row[0];
		$db_username = $row[1];
        $db_pass_str = $row[2];
        $db_full_name = $row[3];

		if($p != $db_pass_str){
			echo "wrong_password";
            exit();
		} else {
			
			// UPDATE THEIR "LASTLOGIN" FIELDS
			$sql = "UPDATE users SET lastlogin=now() WHERE username='$db_username' LIMIT 1";
            $query = mysqli_query($db_conx, $sql);
			echo '{"id" : '.$db_id.', "username" : "'.$db_username.'", "full_name" : "'.$db_full_name.'", "password" : "'.$db_pass_str.'"}';
		    exit();
		}
	}
	exit();
}
?><?php 
if (isset($_POST['userLib'])) {
	include_once("php_includes/db_conx.php");
	
	$uid = preg_replace('#[^0-9]#i', '', $_POST['uid']);
	
	if($uid == ""){
		echo "values_missing";
        exit();
		
	} else {
		
		//query to get all liked_media
		$sql = "SELECT media_id, media_type FROM opinions WHERE user_id='$uid' AND opinion='1' ORDER BY opinion_date DESC";
		$result = mysqli_query($db_conx, $sql);
		$likes_check = mysqli_num_rows($result);
		
		$json_str = '{"liked_media" : ';
		
		if ($likes_check < 1) {
			//if the user hasn't liked any media
			$json_str = $json_str.'{"no_likes" : true }';
		} else {
		
		
		$json_str = $json_str. "[";
	while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
		$media_id = $row["media_id"];
		$media_type = $row["media_type"];

		$curl = curl_init();

		curl_setopt_array($curl, array(
		  CURLOPT_URL => "https://api.themoviedb.org/3/$media_type/$media_id?language=en-US&api_key=95404890f3069fa998b428bd35e8ac0a&append_to_response=external_ids",
		  CURLOPT_RETURNTRANSFER => true,
		  CURLOPT_ENCODING => "",
		  CURLOPT_MAXREDIRS => 10,
		  CURLOPT_TIMEOUT => 30,
		  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		  CURLOPT_CUSTOMREQUEST => "GET",
		  CURLOPT_POSTFIELDS => "{}",
		));

		$response = curl_exec($curl);
		$err = curl_error($curl);

		curl_close($curl);

		if ($err) {
		  //echo error if request failed
		  echo "cURL Error #:" . $err;
		} else { 
		 //no error: turn json response into a php readable associative array
		  $response_assoc = json_decode($response, true);
		  
		  //format the media's genres into something like this "Comedy | Crime | Drama"
		    $genres = $response_assoc['genres'];

			$genre_str ="";
			foreach( $genres as $genre ) {
				$genre_str .= $genre['name']." | ";       
			}
			$genre_str = substr_replace($genre_str,"",-3);
			
		  //-----------------------------------------------------------------------------
		  
							
		  //distill fields of interest from the associative array to form json string
		  if ($media_type == 'tv'){
			  
			$json_str = $json_str.'{"id" : '.$media_id.', "title" : "'.$response_assoc['name'].'", "genre" : "'.$genre_str.'", "media_type" : "'.$media_type.'", "release_date" : "'.$response_assoc['first_air_date'].'", "poster_path" : "'.$response_assoc['poster_path'].'", "backdrop_path": "'.$response_assoc['backdrop_path'].'", "imdb_id": "'.$response_assoc['external_ids']['imdb_id'].'" },';
		  
		  } else if ($media_type == 'movie'){
			  
			$json_str = $json_str.'{"id" : '.$media_id.', "title" : "'.$response_assoc['title'].'", "genre" : "'.$genre_str.'", "media_type" : "'.$media_type.'", "release_date" : "'.$response_assoc['release_date'].'", "poster_path" : "'.$response_assoc['poster_path'].'", "backdrop_path": "'.$response_assoc['backdrop_path'].'", "imdb_id": "'.$response_assoc['imdb_id'].'" },';
		  
		  }
		}
	}
	
	$json_str = substr_replace($json_str,"]",-1);
	}
###################################### END OF GATHERING LIKED MEDIA #######################################################
								//NOW GATHERING BOOED(DISLIKED) MEDIA\\
								
	$json_str = $json_str. ', "booed_media" : ';
	
	//query to get all liked_media
		$sql = "SELECT media_id, media_type FROM opinions WHERE user_id='$uid' AND opinion='0' ORDER BY opinion_date DESC";
		$result = mysqli_query($db_conx, $sql);
		$boos_check = mysqli_num_rows($result);
		
		
		
		if ($boos_check < 1) {
			//if the user hasn't booed any media
			$json_str = $json_str.'{"no_boos" : true }';
		} else {
		
		//user has booed some media
		$json_str = $json_str. "[";
	while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
		$media_id = $row["media_id"];
		$media_type = $row["media_type"];

		$curl = curl_init();

		curl_setopt_array($curl, array(
		  CURLOPT_URL => "https://api.themoviedb.org/3/$media_type/$media_id?language=en-US&api_key=95404890f3069fa998b428bd35e8ac0a&append_to_response=external_ids",
		  CURLOPT_RETURNTRANSFER => true,
		  CURLOPT_ENCODING => "",
		  CURLOPT_MAXREDIRS => 10,
		  CURLOPT_TIMEOUT => 30,
		  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		  CURLOPT_CUSTOMREQUEST => "GET",
		  CURLOPT_POSTFIELDS => "{}",
		));

		$response = curl_exec($curl);
		$err = curl_error($curl);

		curl_close($curl);

		if ($err) {
		  //echo error if request failed
		  echo "cURL Error #:" . $err;
		} else { 
		 //no error: turn json response into a php readable associative array
		  $response_assoc = json_decode($response, true);
		  
		   //format the media's genres into something like this "Comedy | Crime | Drama"
		    $genres = $response_assoc['genres'];

			$genre_str ="";
			foreach( $genres as $genre ) {
				$genre_str .= $genre['name']." | ";       
			}
			$genre_str = substr_replace($genre_str,"",-3);
			
		  //-----------------------------------------------------------------------------
		  
							
		  //distill fields of interest from the associative array to form json string
		  if ($media_type == 'tv'){
			  
			$json_str = $json_str.'{"id" : '.$media_id.', "title" : "'.$response_assoc['name'].'", "genre" : "'.$genre_str.'", "media_type" : "'.$media_type.'", "release_date" : "'.$response_assoc['first_air_date'].'", "poster_path" : "'.$response_assoc['poster_path'].'", "backdrop_path": "'.$response_assoc['backdrop_path'].'", "imdb_id": "'.$response_assoc['external_ids']['imdb_id'].'" },';
		  
		  } else if ($media_type == 'movie'){
			  
			$json_str = $json_str.'{"id" : '.$media_id.', "title" : "'.$response_assoc['title'].'", "genre" : "'.$genre_str.'", "media_type" : "'.$media_type.'", "release_date" : "'.$response_assoc['release_date'].'", "poster_path" : "'.$response_assoc['poster_path'].'", "backdrop_path": "'.$response_assoc['backdrop_path'].'", "imdb_id": "'.$response_assoc['imdb_id'].'" },';
		  
		  }
		}
	}
	
	$json_str = substr_replace($json_str,"]",-1);
  }
  
###################################### END OF GATHERING booED MEDIA #######################################################
								//NOW GATHERING MEDIA IN HOME LIBRARY\\
								
	$json_str = $json_str. ', "home_library" : ';
	
	//query to get media in users home library
		$sql = "SELECT DISTINCT media_id, media_type FROM library WHERE user_id='$uid' ORDER BY entry_date DESC";
		$result = mysqli_query($db_conx, $sql);
		$lib_check = mysqli_num_rows($result);
		
		
		
		if ($lib_check < 1) {
			//if the user hasn't entered any media into home lib list
			$json_str = $json_str.'{"library_empty" : true }';
		} else {

		$json_str = $json_str. "[";
	while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
		$media_id = $row["media_id"];
		$media_type = $row["media_type"];
		

		$curl = curl_init();

		curl_setopt_array($curl, array(
		  CURLOPT_URL => "https://api.themoviedb.org/3/$media_type/$media_id?language=en-US&api_key=95404890f3069fa998b428bd35e8ac0a&append_to_response=external_ids",
		  CURLOPT_RETURNTRANSFER => true,
		  CURLOPT_ENCODING => "",
		  CURLOPT_MAXREDIRS => 10,
		  CURLOPT_TIMEOUT => 30,
		  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		  CURLOPT_CUSTOMREQUEST => "GET",
		  CURLOPT_POSTFIELDS => "{}",
		));

		$response = curl_exec($curl);
		$err = curl_error($curl);

		curl_close($curl);

		if ($err) {
		  //echo error if request failed
		  echo "cURL Error #:" . $err;
		} else { 
		 //no error: turn json response into a php readable associative array
		  $response_assoc = json_decode($response, true);
		  
		   //format the media's genres into something like this "Comedy | Crime | Drama"
		    $genres = $response_assoc['genres'];

			$genre_str ="";
			foreach( $genres as $genre ) {
				$genre_str .= $genre['name']." | ";       
			}
			$genre_str = substr_replace($genre_str,"",-3);
			
		  //-----------------------------------------------------------------------------
		  
							
		  //distill fields of interest from the associative array to form json string
		  if ($media_type == 'tv'){
			  
			$json_str = $json_str.'{"id" : '.$media_id.', "title" : "'.$response_assoc['name'].'", "genre" : "'.$genre_str.'", "media_type" : "'.$media_type.'", "release_date" : "'.$response_assoc['first_air_date'].'", "poster_path" : "'.$response_assoc['poster_path'].'", "backdrop_path": "'.$response_assoc['backdrop_path'].'", "imdb_id": "'.$response_assoc['external_ids']['imdb_id'].'" },';
		  
		  } else if ($media_type == 'movie'){
			  
			$json_str = $json_str.'{"id" : '.$media_id.', "title" : "'.$response_assoc['title'].'", "genre" : "'.$genre_str.'", "media_type" : "'.$media_type.'", "release_date" : "'.$response_assoc['release_date'].'", "poster_path" : "'.$response_assoc['poster_path'].'", "backdrop_path": "'.$response_assoc['backdrop_path'].'", "imdb_id": "'.$response_assoc['imdb_id'].'" },';
		  
		  }
		}
	}
	
	$json_str = substr_replace($json_str,"]",-1);
  }
	$json_str = $json_str. " }";
 
	echo $json_str;
	exit();
		
 }
}	
?>
<?php 
if (isset($_POST['m_lib'])) {
	include_once("php_includes/db_conx.php");
	
	
	//store data into local vars
	
	$media_id = preg_replace('#[^0-9]#i', '', $_POST['m_id']);	
	$uid = preg_replace('#[^0-9]#i', '', $_POST['uid']);
	
	
	$sql = "SELECT * FROM users WHERE id='$uid' LIMIT 1";
    $query = mysqli_query($db_conx, $sql);
	$id_check = mysqli_num_rows($query);
	
	if($media_id == "" || $uid == "" ){
		echo "values_missing";
        exit();
		
	} else if ($id_check < 1){ //ensures the uid exists in db 
		echo "uid_not_in_db"; 
        exit();
	
	} else {
		//data from client is all good so we check if user has the movie in his/her lib list
		
		
		$sql = "SELECT * FROM library WHERE user_id='$uid' AND media_id='$media_id' LIMIT 1";
		$query = mysqli_query($db_conx, $sql);
		$m_lib_check = mysqli_num_rows($query);
		
		if ($m_lib_check < 1) {
			//add movie to lib list
			$sql = "INSERT INTO library (user_id, media_id, media_type, entry_date) VALUES ('$uid','$media_id','movie',now())";
			$query = mysqli_query($db_conx, $sql);
			
			echo "movie_added_to_lib";
			exit();
			
		}else{
			//user clicked already lit movie button, therefore wants to remove movie from lib 
			$sql = "DELETE FROM library WHERE user_id='$uid' AND media_id='$media_id' AND media_type='movie' LIMIT 1";
			$query = mysqli_query($db_conx, $sql); 
			
			echo "lib_movie_deleted";
			exit();
		}
		
		
	}
		
	
}	
?>


<?php 
if (isset($_POST['tv_lib'])) {
	include_once("php_includes/db_conx.php");
	$media_id = preg_replace('#[^0-9]#i', '', $_POST['tv_id']);	
	$uid = preg_replace('#[^0-9]#i', '', $_POST['uid']);
	
	$curl = curl_init();

		curl_setopt_array($curl, array(
		  CURLOPT_URL => "https://api.themoviedb.org/3/tv/$media_id?language=en-US&api_key=95404890f3069fa998b428bd35e8ac0a",
		  CURLOPT_RETURNTRANSFER => true,
		  CURLOPT_ENCODING => "",
		  CURLOPT_MAXREDIRS => 10,
		  CURLOPT_TIMEOUT => 30,
		  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		  CURLOPT_CUSTOMREQUEST => "GET",
		  CURLOPT_POSTFIELDS => "{}",
		));

		$response = curl_exec($curl);
		$err = curl_error($curl);

		curl_close($curl);

		if ($err) {
		  //echo error if request failed
		  echo "cURL Error #:" . $err;
		} else { 
		 //no error: turn json response into a php readable associative array
		  $response_assoc = json_decode($response, true);
		  
		  $json_str = '{"id" : '.$response_assoc['id'].', "title" : "'.$response_assoc['name'].'", "release_date" : "'.$response_assoc['first_air_date'].'", "poster_path" : "'.$response_assoc['poster_path'].'", "backdrop_path": "'.$response_assoc['backdrop_path'].'", "number_of_seasons" : '.$response_assoc['number_of_seasons'].', "seasons_in_lib" : "';
		  
		//query to get all liked_media
		$sql = "SELECT series_season FROM library WHERE user_id='$uid' AND media_id='$media_id' ORDER BY series_season ASC";
		$result = mysqli_query($db_conx, $sql);
		$tv_lib_check = mysqli_num_rows($result);
		
		if ($tv_lib_check < 1)  {
			
			$json_str = $json_str.'empty" }';
		
		}else {
		    $seasons_in_lib ="";
			while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
					$series_season = $row["series_season"].",";
				$seasons_in_lib = $seasons_in_lib.$series_season;
			}
			
			$seasons_in_lib = substr_replace($seasons_in_lib,'"}',-1);
			
			$json_str = $json_str.$seasons_in_lib; 
			
		}
		echo $json_str;
		exit();
	}
}
?>
<?php 
if (isset($_POST['tv_seasons'])) {
	include_once("php_includes/db_conx.php");
	
	$media_id = preg_replace('#[^0-9]#i', '', $_POST['tv_id']);	
	$uid = preg_replace('#[^0-9]#i', '', $_POST['uid']);
	$selection_str = preg_replace('#[^0-9]#i', ',', $_POST['tv_seasons']); //replaces underscores in tv_seasons selection with commas
	$selection_str = substr_replace($selection_str,"",-1);
	
	
	//instead of comparing user input of selected seasons with whats on the db and then selecting which will be added vs not added
	//delete all from db and add all from user input.
	//advantages: waaaay easier, plus seasons of the same series will always be clustered together in the library tab(easy to find)
	$sql = "DELETE FROM library WHERE user_id='$uid' AND media_id='$media_id' AND media_type='tv'";
	$purge = mysqli_query($db_conx, $sql);
	
	if($selection_str == "") {
		$sql = "SELECT * FROM library WHERE user_id='$uid' AND media_id='$media_id' AND media_type='tv'";
		$result = mysqli_query($db_conx, $sql);
	
		echo mysqli_num_rows($result);
		exit();
	} else {
	
	//turn selected str comma-seperated string of ints representing selected seasons into array
	$selection_arr	= explode(',', $selection_str);
	
	for ($x = 0; $x < sizeOf($selection_arr); $x++) {
		$selected_season = $selection_arr[$x];
		
		$sql = "INSERT INTO library (user_id, media_id, media_type, series_season, entry_date) VALUES ('$uid','$media_id','tv','$selected_season',now())";
		$add = mysqli_query($db_conx, $sql);
	} 
	
	$sql = "SELECT * FROM library WHERE user_id='$uid' AND media_id='$media_id' AND media_type='tv'";
	$result = mysqli_query($db_conx, $sql);
	
	echo mysqli_num_rows($result);
	exit();
	}
}
?>
<?php
if (isset($_POST['coords'])) {
  include_once("php_includes/db_conx.php");
  
  //gather variables 
  $uid = preg_replace('#[^0-9]#i', '', $_POST['uid']);
  $g_name = mysqli_real_escape_string($db_conx, $_POST['g_name']);
  $g_bio = mysqli_real_escape_string($db_conx, $_POST['g_bio']);
  $coords =  preg_replace('#[^0-9.,-]#i', '', $_POST['coords']); //mainly removes brackets from coords string leaving coma-seperated lat and long coordinates
		
  $coords_arr = explode(',', $coords); //stores lat and long values individually in an array
  
  $lat = $coords_arr[0];
  $lng = $coords_arr[1];
  
  if ($g_name == "") {
	  echo "group_name_missing";
	  exit();
  } else if ($coords == "") {
	  echo "coordinates_missing";
	  exit();
	  
  } else {
	  //all good
	  $sql = "INSERT INTO groups (group_name, lat, lng, group_description, creation_date) VALUES ('$g_name', '$lat', '$lng', '$g_bio', now())";
	  $add_group = mysqli_query($db_conx, $sql);
	  
	  $g_id = mysqli_insert_id($db_conx); //gets id of the group created ^above^
	  
	  $sql = "INSERT INTO group_follows (group_id, user_id, user_rank, g_follow_date) VALUES ('$g_id', '$uid', '1', now())"; //user_rank is set to 1 meaning the group creator is automatically the group admin (0 is normal group member)
	  $add_g_follow = mysqli_query($db_conx, $sql);
	  
	  if ($add_g_follow === TRUE) {
	  echo "group_created: id(".$g_id.")";
	  exit();
	  } else {
	  echo "group_creation_failed";
	  exit();
	  }
  }
}
		
?>