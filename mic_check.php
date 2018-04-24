<?php 
if (isset($_POST['userLib'])) {
	include_once("php_includes/db_conx.php");
	
	$uid = preg_replace('#[^0-9]#i', '', $_POST['uid']);
	
	if($uid == ""){
		echo "values_missing";
        exit();
		
	} else {
		
	  //query to get all liked_media
	  $sql = "SELECT opinion_id, media_id, media_type FROM opinions WHERE user_id='$uid' AND opinion='1' ORDER BY opinion_date DESC LIMIT 5";
	  $stmt = $db_conx->prepare($sql); /* START PREPARED STATEMENT */
	  $stmt->execute(); /* EXECUTE THE QUERY */
	  $stmt->store_result(); /*STORE THE RESULTS IN THE $stmt VARIABLE*/
	  if ($stmt->num_rows < 1) {
		$liked_media = array("no_likes" => true); 	
	  } else {
	    $stmt->bind_result($l_opinion_id, $media_id, $media_type); /* BIND RESULT FIELDS TO CORRESPONDING VARIABLES */
	    while($stmt->fetch()){ /* FETCH ALL RESULTS */
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
			  $liked_media[] = array("tab_id" => $l_opinion_id, "media_id" => $media_id, "media_type" => $media_type, "title" => $response_assoc['name'], "genre" => $genre_str, "release_date" => $response_assoc['first_air_date'], "poster_path" => $response_assoc['poster_path'], "backdrop_path" => $response_assoc['backdrop_path'], "imdb_id" => $response_assoc['external_ids']['imdb_id']);
			 } else if ($media_type == 'movie'){
			  $liked_media[] = array("tab_id" => $l_opinion_id, "media_id" => $media_id, "media_type" => $media_type, "title" => $response_assoc['title'], "genre" => $genre_str, "release_date" => $response_assoc['release_date'], "poster_path" => $response_assoc['poster_path'], "backdrop_path" => $response_assoc['backdrop_path'], "imdb_id" => $response_assoc['imdb_id']);
			 }
			}  
		
	    } /* END OF WHILE LOOP */
	  }

###################################### END OF GATHERING LIKED MEDIA #######################################################
								//NOW GATHERING BOOED(DISLIKED) MEDIA\\
	
	//query to get all booed media
	  $sql = "SELECT opinion_id, media_id, media_type FROM opinions WHERE user_id='$uid' AND opinion='0' ORDER BY opinion_date DESC LIMIT 5";
	  $stmt = $db_conx->prepare($sql); /* START PREPARED STATEMENT */
	  $stmt->execute(); /* EXECUTE THE QUERY */
	  $stmt->store_result(); /*STORE THE RESULTS IN THE $stmt VARIABLE*/
	  if ($stmt->num_rows < 1) {
		$booed_media = array("no_boos" => true); 	
	  } else {
	    $stmt->bind_result($b_opinion_id, $media_id, $media_type); /* BIND RESULT FIELDS TO CORRESPONDING VARIABLES */
	    while($stmt->fetch()){ /* FETCH ALL RESULTS */
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
			  $booed_media[] = array("tab_id" => $b_opinion_id, "media_id" => $media_id, "media_type" => $media_type, "title" => $response_assoc['name'], "genre" => $genre_str, "release_date" => $response_assoc['first_air_date'], "poster_path" => $response_assoc['poster_path'], "backdrop_path" => $response_assoc['backdrop_path'], "imdb_id" => $response_assoc['external_ids']['imdb_id']);
			 } else if ($media_type == 'movie'){
			  $booed_media[] = array("tab_id" => $b_opinion_id, "media_id" => $media_id, "media_type" => $media_type, "title" => $response_assoc['title'], "genre" => $genre_str, "release_date" => $response_assoc['release_date'], "poster_path" => $response_assoc['poster_path'], "backdrop_path" => $response_assoc['backdrop_path'], "imdb_id" => $response_assoc['imdb_id']);
			 }
			}  
		
	    } /* END OF WHILE LOOP */
	  }
  
###################################### END OF GATHERING booED MEDIA #######################################################
								//NOW GATHERING MEDIA IN HOME LIBRARY\\
	
      //query to get media in users home library
	  //Query below looks overley complexified but it works best with infinite scrolling (load on scroll) even on client
	  //functionally better than simply: "SELECT lib_id, media_id, media_type FROM library WHERE user_id='1' GROUP BY media_id ORDER BY lib_id DESC" because of max_id below... avoids redundant loads during scroll event loading
	  
	  $sql = "SELECT library.lib_id, library.media_id, library.media_type FROM (SELECT MAX(lib_id) AS max_id, media_id, media_type FROM library WHERE user_id='$uid' GROUP BY media_id) AS user_lib INNER JOIN library ON user_lib.max_id=library.lib_id ORDER BY lib_id DESC LIMIT 5";
	  $stmt = $db_conx->prepare($sql); /* START PREPARED STATEMENT */
	  $stmt->execute(); /* EXECUTE THE QUERY */
	  $stmt->store_result(); /*STORE THE RESULTS IN THE $stmt VARIABLE*/
	  if ($stmt->num_rows < 1) {
		$booed_media = array("library_empty" => true); 	
	  } else {
	    $stmt->bind_result($lib_id, $media_id, $media_type); /* BIND RESULT FIELDS TO CORRESPONDING VARIABLES */
	    while($stmt->fetch()){ /* FETCH ALL RESULTS */
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
			  $library_media[] = array("tab_id" => $lib_id, "media_id" => $media_id, "media_type" => $media_type, "title" => $response_assoc['name'], "genre" => $genre_str, "release_date" => $response_assoc['first_air_date'], "poster_path" => $response_assoc['poster_path'], "backdrop_path" => $response_assoc['backdrop_path'], "imdb_id" => $response_assoc['external_ids']['imdb_id']);
			 } else if ($media_type == 'movie'){
			  $library_media[] = array("tab_id" => $lib_id, "media_id" => $media_id, "media_type" => $media_type, "title" => $response_assoc['title'], "genre" => $genre_str, "release_date" => $response_assoc['release_date'], "poster_path" => $response_assoc['poster_path'], "backdrop_path" => $response_assoc['backdrop_path'], "imdb_id" => $response_assoc['imdb_id']);
			 }
			}  
		
	    } /* END OF WHILE LOOP */
	  }
	  
	  $full_result = array("liked_media" => $liked_media, "booed_media" => $booed_media, "home_library" => $library_media);
	  echo json_encode($full_result); /* ECHO ALL THE RESULTS AS A JSON STRING*/
	  exit();
 }
}	
?><?php 
if (isset($_POST['libScrollLoad'])) {
	include_once("php_includes/db_conx.php");
	
	$uid = preg_replace('#[^0-9]#i', '', $_POST['uid']);
	$tab = preg_replace('#[^a-z]#i', '', $_POST['tab']);
	$last_id = preg_replace('#[^0-9]#i', '', $_POST['last_id']);
	
	if ($tab == 'library') {
		$sql = "SELECT library.lib_id, library.media_id, library.media_type FROM (SELECT MAX(lib_id) AS max_id, media_id, media_type FROM library WHERE user_id='$uid' GROUP BY media_id ) AS user_lib INNER JOIN library ON user_lib.max_id=library.lib_id WHERE lib_id < $last_id ORDER BY lib_id DESC LIMIT 5 ";
		
	} else if ($tab == 'likes') {
		
		$sql = "SELECT opinion_id, media_id, media_type FROM opinions WHERE user_id='$uid' AND opinion='1' AND opinion_id < $last_id ORDER BY opinion_date DESC LIMIT 5";
	} else if ($tab == 'boos') { 
		$sql = "SELECT opinion_id, media_id, media_type FROM opinions WHERE user_id='$uid' AND opinion='0' AND opinion_id < $last_id ORDER BY opinion_date DESC LIMIT 5";

	}

	$stmt = $db_conx->prepare($sql); /* START PREPARED STATEMENT */
	$stmt->execute(); /* EXECUTE THE QUERY */
	$stmt->store_result(); /*STORE THE RESULTS IN THE $stmt VARIABLE*/
	if ($stmt->num_rows < 1) {
	    $tab_media = array("no_more" => true); 
    	echo json_encode($tab_media);
		exit();
	} else {
	$stmt->bind_result($tab_id, $media_id, $media_type); /* BIND RESULT FIELDS TO CORRESPONDING VARIABLES */
	while($stmt->fetch()){ /* FETCH ALL RESULTS */
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
		  $tab_media[] = array("tab_id" => $tab_id, "media_id" => $media_id, "media_type" => $media_type, "title" => $response_assoc['name'], "genre" => $genre_str, "release_date" => $response_assoc['first_air_date'], "poster_path" => $response_assoc['poster_path'], "backdrop_path" => $response_assoc['backdrop_path'], "imdb_id" => $response_assoc['external_ids']['imdb_id']);
		 } else if ($media_type == 'movie'){
		  $tab_media[] = array("tab_id" => $tab_id, "media_id" => $media_id, "media_type" => $media_type, "title" => $response_assoc['title'], "genre" => $genre_str, "release_date" => $response_assoc['release_date'], "poster_path" => $response_assoc['poster_path'], "backdrop_path" => $response_assoc['backdrop_path'], "imdb_id" => $response_assoc['imdb_id']);
		 }
		}  

	} /* END OF WHILE LOOP */

	echo json_encode($tab_media);
	}
}
?>