<?php 
if (isset($_POST['media_pg'])) {

	include_once("php_includes/db_conx.php");
	$media_type = preg_replace('#[^a-z]#i', '', $_POST['mt']);	
	$media_id = preg_replace('#[^0-9]#i', '', $_POST['m_id']);	
	$uid = preg_replace('#[^0-9]#i', '', $_POST['uid']);

	$curl = curl_init();

	curl_setopt_array($curl, array(
	  CURLOPT_URL => "https://api.themoviedb.org/3/$media_type/$media_id?language=en-US&api_key=95404890f3069fa998b428bd35e8ac0a&append_to_response=credits,external_ids,videos",
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
	 
	 $json_str = substr_replace($response,",",-1);
	 
	 $sql = "SELECT lib_id FROM library WHERE user_id='$uid' AND media_id='$media_id' LIMIT 1";
	 if ($result = mysqli_query($db_conx, $sql)) {
	 
		$in_lib = mysqli_num_rows($result);
		if ($in_lib == 1) {
			$json_str .= ' "in_lib" : true, '; 
		} else { $json_str .= ' "in_lib" : false, '; }
		
		$sql = "SELECT opinion FROM opinions WHERE user_id='$uid' AND media_id='$media_id' LIMIT 1";
		if ($result = mysqli_query($db_conx, $sql)) {
			$has_opinion = mysqli_num_rows($result);
			if ($has_opinion == 1) {
				$row = mysqli_fetch_row($result); 
				$opinion = $row[0];
			} else { $opinion = 3; }
			
		    $json_str .= '"user_opinion" : '.$opinion.', ';
			
			$sql = "SELECT count(opinion_id) AS total_boos FROM opinions WHERE media_id='$media_id' AND opinion = '0'";
			if ($result = mysqli_query($db_conx, $sql)) {
				//total boos
				$row = mysqli_fetch_row($result);
				$total_boos = $row[0];
				
				$json_str .= '"total_boos" : '.$total_boos.', ';
				
				$sql = "SELECT count(opinion_id) AS total_likes FROM opinions WHERE media_id='$media_id' AND opinion = '1'";
				if ($result = mysqli_query($db_conx, $sql)) {
					//total likes
					$row = mysqli_fetch_row($result);
					$total_likes = $row[0];
					
					$json_str .= '"total_likes" : '.$total_likes.' }';
					
					echo $json_str;
					exit();
					
			    } else { echo "database_error"; exit(); }
			} else { echo "database_error"; exit(); }
		} else { echo "database_error"; exit(); }
	 } else { echo "database_error"; exit(); }

	}
}
?><?php
if (isset($_POST['getGroupmatesMedia'])) {
  include_once("php_includes/db_conx.php");
  
  $uid = preg_replace('#[^0-9]#i', '', $_POST['uid']);
  
  /*/first find all the groups the user is a member of
	$sql = "SELECT group_id FROM group_follows WHERE user_id='$uid'";
    if ($result = mysqli_query($db_conx, $sql)) { 
	  //store total number of groups the user is a member of in total_groups variable.
	  $total_groups = mysqli_num_rows($result);
	  
	  if ($total_groups < 1) {
		  //if user is not a member of any group
		  echo "no_group_joined";
		  exit();
		  
		}else if ($total_groups == 1) {
			
		  //if user is a member of only one group
		  $row = mysqli_fetch_row($result);
		  
          $group_id = $row[0]; //store group id of sole group in a variable	
		  
		  $query_str = "SELECT library.media_id AS media_id, library.media_type AS media_type FROM group_follows JOIN groups ON group_follows.group_id = groups.group_id JOIN library ON group_follows.user_id = library.user_id JOIN users ON group_follows.user_id = users.id WHERE group_follows.group_id = '$group_id' GROUP BY library.media_id";
		  
		}else if ($total_groups > 1) { 
		  //if user is a member of more than one group
		  $first_row = mysqli_fetch_row($result);  //gets first row of the results array
		  
		  $first_gid = $first_row[0]; //gets the group id field from the first row
		  
		  $query_str = "SELECT library.media_id AS media_id, library.media_type AS media_type FROM group_follows JOIN groups ON group_follows.group_id = groups.group_id JOIN library ON group_follows.user_id = library.user_id JOIN users ON group_follows.user_id = users.id WHERE group_follows.group_id = '$first_gid' ";
		  
		  
		  //i is not set to 0 because we've already collected the first group id above
		  //the first gid is collected seperately bcoz the rest will be concatnated with an OR statement where as the first one is not
		  while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			  $gid = $row['group_id'];
			  if ($gid == $first_gid) { 
			    $query_str .= ""; 
			  } else {
				$query_str .= "OR group_follows.group_id = '$gid' ";
			  }  
		  }
		   //group by media id to ensure unique entries
		   //order by media id descending is because it is ASSUMED (not always the case) the highier the id the more recent the movie is
		   $query_str .= "GROUP BY library.media_id ORDER BY library.media_id DESC";
  
		}
		
		//execute query generated above to get all media owned by people following the same share groups as the user
        if ($result = mysqli_query($db_conx, $query_str)) {
			$tv_json ="";
			$movie_json ="";
			
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
				  
				   //distill fields of interest from the associative array to form json string
				  if ($media_type == 'tv'){
					  
					$tv_json = $tv_json.'{"id" : '.$media_id.', "title" : "'.$response_assoc['name'].'", "media_type" : "'.$media_type.'", "release_date" : "'.$response_assoc['first_air_date'].'", "poster_path" : "'.$response_assoc['poster_path'].'", "backdrop_path": "'.$response_assoc['backdrop_path'].'", "imdb_id": "'.$response_assoc['external_ids']['imdb_id'].'" },';
				  
				  } else if ($media_type == 'movie'){
					  
					$movie_json = $movie_json.'{"id" : '.$media_id.', "title" : "'.$response_assoc['title'].'", "media_type" : "'.$media_type.'", "release_date" : "'.$response_assoc['release_date'].'", "poster_path" : "'.$response_assoc['poster_path'].'", "backdrop_path": "'.$response_assoc['backdrop_path'].'", "imdb_id": "'.$response_assoc['imdb_id'].'" },';
				  
				  }
				
				}
			}
			
			//remove the trailing loop comma and replace with closing square bracket
			$tv_json = substr_replace($tv_json,"]",-1);  
			$movie_json = substr_replace($movie_json,"]",-1);
			
			if ($tv_json == "") {
				//means no groupmate has added a tv series to their home library list yet. 
				$tv_json = '{"no_series" : true}]';
			} else if ($movie_json == "") { 
			    //means no groupmate has added a movie to their home library list yet. 
				$movie_json = '{"no_movie" : true}]';
			}
			
			
			     $json_str = '{"groupmates_movies" : ['. $movie_json .', "groupmates_series" : ['. $tv_json .' }';
        //substr_replace() already added closing square bracket ^^ aaaaaaaaaaaaaaaaaaaaaaaaaaaaand here ^^.... don't be consfused.
		
		    echo $json_str;
			exit();
        } else { echo "database_error"; exit(); }
	
	} else { echo "database_error"; exit(); } */
	
	//Get groups the user follows
  $sql = "SELECT group_id FROM group_follows WHERE user_id='$uid'";
  $stmt = $db_conx->prepare($sql); /* START PREPARED STATEMENT */
  $stmt->execute(); /* EXECUTE THE QUERY */
  $stmt->store_result(); /*STORE THE RESULTS IN THE $stmt VARIABLE*/
  
  
  if ($stmt->num_rows < 1) {  
    //user isn't a member of any group
	echo "no_group_joined";
    exit();
  
  
  } else if ($stmt->num_rows > 0) {
	//user is a member of one or more groups
	$stmt->bind_result($group_id); //binds the group_id field to the $group_id variable
	$i = 1;
	
    while ($stmt->fetch()) { //iterate through all results 
	  if ($i == 1) { //represents the first iteration of the loop
		$final_sql = "SELECT media_id, media_type, GROUP_CONCAT(DISTINCT series_season ORDER BY series_season) as available_seasons FROM (SELECT library.lib_id AS lib_id, library.media_id AS media_id, library.media_type AS media_type, library.series_season AS series_season FROM group_follows JOIN groups ON group_follows.group_id = groups.group_id JOIN library ON group_follows.user_id = library.user_id WHERE group_follows.group_id = '$group_id' ";
		
	  }else if ($i>1){ //all other subsequent iterations concatnate an SQL 'OR' statement 
		$final_sql .= " OR  group_follows.group_id='$group_id' ";
		  
	  }
	  
	  $i++;
	}
    //concat last part of query
	$final_sql .= " GROUP BY library.lib_id) AS available_media GROUP BY media_id ORDER BY media_id DESC"; //order by media_id puts recent movies first *ideally... not always*
  }
 
  
  //execute final query
  $final_stmt = $db_conx->prepare($final_sql);
  $final_stmt->execute();
  $final_stmt->store_result();
   
	if ($final_stmt->num_rows > 0) { 
	 //there is atleast one media in the groupmates movie pool
	 //store each field in a corresponding variable... !important: follow SELECT order in sql statement
	 $final_stmt->bind_result($media_id, $media_type, $available_seasons);
	  
	 //construct seperate array for movies and series... each will be encoded into JSON later
	 $tv_arr = array();
	 $movie_arr = array();
	 while($final_stmt->fetch()){
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
		  
		  //distill fields of interest from the associative array and push 'em into the respective media arrays
		  if ($media_type == 'tv'){
			  
			$tv_arr[] = array("id" => $media_id, "title" => $response_assoc['name'], "media_type" => $media_type, "number_of_seasons" => $response_assoc['number_of_seasons'], "available_seasons" => $available_seasons, "release_date" => $response_assoc['first_air_date'], "poster_path" => $response_assoc['poster_path'], "backdrop_path" => $response_assoc['backdrop_path'], "imdb_id" => $response_assoc['external_ids']['imdb_id'] );
		  } else if ($media_type == 'movie'){
			  
			$movie_arr[] = array("id" => $media_id, "title" => $response_assoc['title'], "media_type" => $media_type, "release_date" => $response_assoc['release_date'], "poster_path" => $response_assoc['poster_path'], "backdrop_path" => $response_assoc['backdrop_path'], "imdb_id" => $response_assoc['imdb_id'] );

		  }
		  
		}
	 }
  	
	} 
	
	if (empty($tv_arr)){ $tv_arr = array("no_series" => true);} //means no groupmate has added a tv series to their home library list yet. 
	 
	if (empty($movie_arr)) { $movie_arr = array("no_movie" => true);} //means no groupmate has added a movie to their home library list yet.
	

	$final_arr = array("groupmates_movies" => $movie_arr, "groupmates_series" => $tv_arr);
    echo json_encode($final_arr); /* ECHO ALL THE RESULTS AS A JSON STRING*/
	exit();
}
?><?php 
if (isset($_POST['op'])) {
	include_once("php_includes/db_conx.php");
	
	
	//store data into local vars
	$media_type = preg_replace('#[^a-z]#i', '', $_POST['mt']);	
	$media_id = preg_replace('#[^0-9]#i', '', $_POST['m_id']);	
	$uid = preg_replace('#[^0-9]#i', '', $_POST['uid']);
	$opinion = preg_replace('#[^0-9]#i', '', $_POST['op']);
	
	$sql = "SELECT * FROM users WHERE id='$uid' LIMIT 1";
    $query = mysqli_query($db_conx, $sql);
	$id_check = mysqli_num_rows($query);
	
	if($media_type == "" || $media_id == "" || $uid == "" || $opinion == ""){
		echo "values_missing";
        exit();
		
	} else if ($id_check < 1){ //ensures the uid exists in db
		echo "uid_not_in_db"; 
        exit();
	
	} else {
		//data from client is all good so we check if user has already opined on media
		$sql = "SELECT opinion_id, opinion, opinion_date FROM opinions WHERE user_id='$uid' AND media_id='$media_id' LIMIT 1";
		$query = mysqli_query($db_conx, $sql);
		$opinion_check = mysqli_num_rows($query);
		
		if ($opinion_check < 1 ) {
			//user doesn't yet have an opinion on this media
			$sql = "INSERT INTO opinions (user_id, media_id, media_type, opinion, opinion_date) VALUES ('$uid','$media_id','$media_type','$opinion', now())";
			$query = mysqli_query($db_conx, $sql); 
			
			if ($opinion == 1) {
				echo "liked";
				exit();
			} else if($opinion == 0) {
				echo "booed";
				exit();
			}
			
		} else {
			//user already has an opinion on this media
			$row = mysqli_fetch_row($query);
			
			
			$opinion_id = $row[0];
			$db_opinion = $row[1];
			$db_opinion_date = $row[2];
			
			
			if ($db_opinion == $opinion ) {
				//if database opinion is equal to opinion clicked by user, the opinion is deleted
				$sql = "DELETE FROM opinions WHERE user_id='$uid' AND media_id='$media_id' AND opinion='$db_opinion' LIMIT 1";
				$query = mysqli_query($db_conx, $sql); 
				
				echo $opinion."_deleted";
				exit();
				
			} else if ($db_opinion !== $opinion) {
				$sql = "UPDATE opinions SET opinion='$opinion' WHERE opinion_id='$opinion_id' LIMIT 1";
				$query = mysqli_query($db_conx, $sql); 
				
				echo "update_to_".$opinion;
				exit();
			}
		}
	}
}	
?><?php 
if (isset($_POST['opinion_totals'])) {
 include_once("php_includes/db_conx.php");
 
 $media_id = preg_replace('#[^0-9]#i', '', $_POST['m_id']);	
 
 $sql = "SELECT opinion, COUNT(*) AS opinion_total FROM opinions WHERE media_id = '$media_id' GROUP BY opinion";
 $result = mysqli_query( $db_conx, $sql);
 
 $count = mysqli_num_rows($result);
 $json = '{ ';
 if ($count == 1) {
	 $row = mysqli_fetch_row($result);
	 if ($row[0] == 1){
		 //sole opinion is like
		$json .= '"likes" : '.$row[1].', "boos" : 0 }';
	 } else if ($row[0] == 0) {
		$json .= '"likes" : 0, "boos" : '.$row[1].' }'; 
	 }
	 
	 echo $json;
	 exit();
 } else if ($count > 1) {
 
 while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) { 
    
	if ($row['opinion'] == 1) {
      $op = "likes";
	} else if ($row['opinion'] == 0) {
		$op = "boos";
	}
	
	$json .= '"'. $op .'" : '.$row['opinion_total'].',';
	
 
 }
    $json = substr_replace($json,"}",-1);
	echo $json;
	exit();
 } else {
	$json = '{"likes" : 0, "boos" : 0}';
	echo $json;
	exit();
 }
} 
?><?php 
if (isset($_POST['removeUserMedia'])) {
  include_once("php_includes/db_conx.php");
 
  $media_id = preg_replace('#[^0-9]#i', '', $_POST['m_id']);
  $user_id = preg_replace('#[^0-9]#i', '', $_POST['uid']);
  $tab = preg_replace('#[^a-z]#i', '', $_POST['tab']);

	if ($tab == 'library') {
		$sql = "DELETE FROM library WHERE user_id='$user_id' AND media_id='$media_id'";
	} else if ($tab == 'likes') {
		$sql = "DELETE FROM opinions WHERE user_id='$user_id' AND media_id='$media_id' AND opinion='1' LIMIT 1";
	} else if ($tab == 'boos') { 
		$sql = "DELETE FROM opinions WHERE user_id='$user_id' AND media_id='$media_id' AND opinion='0' LIMIT 1";
	}

	/* prepare statement */
	if ($stmt = $db_conx->prepare($sql)) {

	  /* execute statement */
	  $stmt->execute();
	  $stmt->store_result(); 

	  if ($stmt->affected_rows > 0) {
	    echo "user_media_deleted";
	    exit();
	  }
	}

}
?><?php
if (isset($_POST['groupmatesWithMedia'])) {
  include_once("php_includes/db_conx.php");
  
  $media_type = preg_replace('#[^a-z]#i', '', $_POST['mt']);	
  $media_id = preg_replace('#[^0-9]#i', '', $_POST['m_id']);	
  $uid = preg_replace('#[^0-9]#i', '', $_POST['uid']);
  $series_season = $_POST['s_season'];
  
  if ($media_type == 'tv') {
	$sql_series_season = "= '".$series_season."'";
  } else if($media_type == 'movie') {
	$sql_series_season = 'IS NULL';  
  }
  
  //Get groups the user follows
  $sql = "SELECT group_id FROM group_follows WHERE user_id='$uid'";
  $stmt = $db_conx->prepare($sql); /* START PREPARED STATEMENT */
  $stmt->execute(); /* EXECUTE THE QUERY */
  $stmt->store_result(); /*STORE THE RESULTS IN THE $stmt VARIABLE*/
  
  
  if ($stmt->num_rows < 1) {  
    //user isn't a member of any group
	echo "no_group_joined";
    exit();
  
  
  } else if ($stmt->num_rows > 0) {
	//user is a member of one or more groups
	$stmt->bind_result($group_id); //binds the group_id field to the $group_id variable
	$i = 1;
	
    while ($stmt->fetch()) { //iterate through all results 
	  if ($i == 1) { //represents the first iteration of the loop
		$final_sql = "SELECT * FROM (SELECT users.id AS user_id, users.avatar AS avatar, users.full_name, users.username AS username, groups.group_name AS group_name, library.lib_id AS lib_id, library.media_id AS media_id, library.media_type AS media_type, library.series_season AS series_season FROM group_follows JOIN users ON group_follows.user_id = users.id JOIN groups ON group_follows.group_id = groups.group_id JOIN library ON group_follows.user_id = library.user_id WHERE group_follows.group_id = '$group_id' ";
		
	  }else if ($i>1){ //all other subsequent iterations concatnate an SQL 'OR' statement 
		$final_sql .= " OR  group_follows.group_id='$group_id' ";
		  
	  }
	  
	  $i++;
	}
    //concat last part of query
	$final_sql .= " GROUP BY library.lib_id) AS all_groupmates_media WHERE media_type = '$media_type' AND media_id='$media_id' AND series_season $sql_series_season";
  }
 
  
  //execute final query
  $final_stmt = $db_conx->prepare($final_sql);
  $final_stmt->execute();
  $final_stmt->store_result();
    $user_has_media = false; //used to check if the requester has the media in their own lib list
	if ($final_stmt->num_rows > 0) { 
		//a match was found... a groupmate has the requested media_id
		//store each field in a corresponding variable... !important: follow SELECT order in sql statement
		$final_stmt->bind_result($user_id, $avatar, $full_name, $username, $group_name, $lib_id, $lib_media_id, $lib_media_type, $lib_series_season);
	  
	 
	 //construct array that will be encoded into JSON
	 $pwm_arr = array();
	 while($final_stmt->fetch()){
	  if ($user_id == $uid) { //ensures user doesn't see h(im/er)self in the results list
		$user_has_media = true; 
		
	  } else {
		//$pwm = people with media/movie
		$pwm_arr[] = array("user_id" => $user_id, "avatar" => $avatar, "full_name" => $full_name, "username" => $username, "group_name" => $group_name, "lib_id" => $lib_id);
	    
	  }
	 }
	 //!important: line bellow checks if user is the only one with movie and sets $pwm_arr
	 if (sizeOf($pwm_arr) == 0 && $user_has_media) {$pwm_arr = array("no_match" => true);}
	  	
	} else {
		//no groupmate has the requested media_id
		$pwm_arr = array("no_match" => true);
	}
  
  
	$final_arr = array("requester_id" => $uid, "user_has_media" => $user_has_media, "media_id" => $media_id, "media_type" => $media_type, "series_season" => $series_season, "results" => $pwm_arr);
    echo json_encode($final_arr); /* ECHO ALL THE RESULTS AS A JSON STRING*/
	exit();
} 
?>