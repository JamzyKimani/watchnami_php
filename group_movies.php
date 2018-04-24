<?php
  include_once("php_includes/db_conx.php");
  
  $uid = 1;
  
  //first find all the groups the user is a member of
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
		   //order by media id descending is because it is assumed the highier the id the more recent the movie is
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
				$tv_json = '{"no_series" : true}';
			} else if ($movie_json == "") { 
			    //means no groupmate has added a movie to their home library list yet. 
				$movie_json = '{"no_movie" : true}';
			}
			
			
			     $json_str = '{"groupmates_movies" : ['. $movie_json .', "groupmates_series" : ['. $tv_json .' }';
        //substr_replace() already added closing square bracket ^^ aaaaaaaaaaaaaaaaaaaaaaaaaaaaand here ^^.... don't be consfused.
		
		    echo $json_str;
			exit();
        } else { echo "database_error"; exit(); }
	
	} else { echo "database_error"; exit(); }

?>