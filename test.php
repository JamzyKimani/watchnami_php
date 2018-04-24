<?php
 //Search  all media in all groupmates libraries for a media match among
 // SELECT media_id, media_type, GROUP_CONCAT(DISTINCT series_season) as available_seasons FROM (SELECT library.lib_id AS lib_id, library.media_id AS media_id, library.media_type AS media_type, library.series_season AS series_season FROM group_follows JOIN groups ON group_follows.group_id = groups.group_id JOIN library ON group_follows.user_id = library.user_id WHERE group_follows.group_id = '20' OR group_follows.group_id = '21' OR group_follows.group_id = '22' OR group_follows.group_id = '18' GROUP BY library.lib_id) AS available_media GROUP BY media_id ORDER BY media_id DESC
 
 //List all available media (with concatnated series_season)
 // SELECT media_id, media_type, GROUP_CONCAT(DISTINCT series_season) as available_seasons FROM (SELECT library.lib_id AS lib_id, library.media_id AS media_id, library.media_type AS media_type, library.series_season AS series_season FROM group_follows JOIN groups ON group_follows.group_id = groups.group_id JOIN library ON group_follows.user_id = library.user_id WHERE group_follows.group_id = '20' OR group_follows.group_id = '21' OR group_follows.group_id = '22' OR group_follows.group_id = '18' GROUP BY library.lib_id) AS available_media GROUP BY media_id

  include_once("php_includes/db_conx.php");
  
  $uid = 1;
  
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
			  
			$tv_arr[] = array("media_id" => $media_id, "title" => $response_assoc['name'], "media_type" => $media_type, "number_of_seasons" => $response_assoc['number_of_seasons'], "available_seasons" => $available_seasons, "release_date" => $response_assoc['first_air_date'], "poster_path" => $response_assoc['poster_path'], "backdrop_path" => $response_assoc['backdrop_path'], "imdb_id" => $response_assoc['external_ids']['imdb_id'] );
		  } else if ($media_type == 'movie'){
			  
			$movie_arr[] = array("media_id" => $media_id, "title" => $response_assoc['title'], "media_type" => $media_type, "release_date" => $response_assoc['release_date'], "poster_path" => $response_assoc['poster_path'], "backdrop_path" => $response_assoc['backdrop_path'], "imdb_id" => $response_assoc['imdb_id'] );

		  }
		  
		}
	 }
  	
	} 
	
	if (empty($tv_arr)){ $tv_arr = array("no_series" => true);} //means no groupmate has added a tv series to their home library list yet. 
	 
	if (empty($movie_arr)) { $movie_arr = array("no_movie" => true);} //means no groupmate has added a movie to their home library list yet.
	

	$final_arr = array("groupmates_movies" => $movie_arr, "groupmates_series" => $tv_arr);
    echo json_encode($final_arr); /* ECHO ALL THE RESULTS AS A JSON STRING*/
	exit();
   
   
?>