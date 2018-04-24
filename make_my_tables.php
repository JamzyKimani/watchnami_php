<?php 
	include_once("php_includes/db_conx.php");
	
	$uid = preg_replace('#[^0-9]#i', '', $_POST['uid']);
	
	if($uid == ""){
		echo "values_missing";
        exit();
		
	} else {
		//query to get all liked_media
		$sql = "SELECT media_id, media_type FROM opinions WHERE user_id='$uid' AND opinion=1 ORDER BY opinion_date DESC";
		$result = mysqli_query($db_conx, $sql);
		$likes_check = mysqli_num_rows($result);
		
		$json_str = '{"liked_media" : ';
		
		if ($likes_check < 1) {
			//if the user hasn't liked any media
			$json_str = $json_str.'{"msg" : "user has no liked media" }, ';
		}
		
		//implied else to the above if statement
		
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
			  
			$json_str = $json_str.'{"id" : '.$media_id.', "title" : "'.$response_assoc['name'].'", "media_type" : "'.$media_type.'", "release_date" : "'.$response_assoc['first_air_date'].'", "poster_path" : "'.$response_assoc['poster_path'].'", "backdrop_path": "'.$response_assoc['backdrop_path'].'", "imdb_id": '.$response_assoc['external_ids']['imdb_id'].' },';
		  
		  } else if ($media_type == 'movie'){
			  
			$json_str = $json_str.'{"id" : '.$media_id.', "title" : "'.$response_assoc['title'].'", "media_type" : "'.$media_type.'", "release_date" : "'.$response_assoc['release_date'].'", "poster_path" : "'.$response_assoc['poster_path'].'", "backdrop_path": "'.$response_assoc['backdrop_path'].'", "imdb_id": '.$response_assoc['imdb_id'].' },';
		  
		  }
		}
	}
	
	$while_str = substr_replace($json_str,"]",-1)
	
	$json_str = $json_str . $while_str;
	echo $json_str;	
 }
}	
?>