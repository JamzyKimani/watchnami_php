<?php 
//if (isset($_POST['media_pg'])) {

	include_once("php_includes/db_conx.php");
	//$media_type = preg_replace('#[^a-z]#i', '', $_POST['mt']);	
	//$media_id = preg_replace('#[^0-9]#i', '', $_POST['m_id']);
	//$series_season = preg_replace('#[^0-9]#i', '', $_POST['m_id']);	
	//$uid = preg_replace('#[^0-9]#i', '', $_POST['uid']);
	
	$uid = 1;
	$media_type = 'movie';
	$media_id = 296096;
	$series_season = "";
     
	//first find all the groups the user is a member of
	$sql = "SELECT group_id FROM group_follows WHERE user_id='$uid'";
    if ($result = mysqli_query($db_conx, $sql)) {
		$total_groups = mysqli_num_rows($result);
		echo $total_groups;
		if ($total_groups < 1) {
		  //not a member of any group
		  echo "no_group_joined";
		  exit();
		  
		}else if ($total_groups == 1) {
		  $row = mysqli_fetch_row($result);
          $group_id = $row[0];	
		  echo "== 1 is excecuting";
		  $query_str = "SELECT * FROM (SELECT library.lib_id AS lib_id, users.id AS user_id, users.username AS username, groups.group_name AS group_name, library.media_id AS media_id, library.media_type AS media_type, library.series_season AS series_season FROM group_follows JOIN groups ON group_follows.group_id = groups.group_id JOIN library ON group_follows.user_id = library.user_id JOIN users ON group_follows.user_id = users.id WHERE group_follows.group_id = $group_id GROUP BY library.lib_id) AS group_movies WHERE media_id = $media_id ";
		  if ($media_type == 'tv') {
		  $query_str .= "AND series_season = $series_season";
		  }
		}else if ($total_groups > 1) {
		  $first_row = mysqli_fetch_row($result);
		  
		  $first_gid = $first_row[0];	
		  
		  $query_str = "SELECT * FROM (SELECT library.lib_id AS lib_id, users.id AS user_id, users.username AS username, groups.group_name AS group_name, library.media_id AS media_id, library.media_type AS media_type, library.series_season AS series_season FROM group_follows JOIN groups ON group_follows.group_id = groups.group_id JOIN library ON group_follows.user_id = library.user_id JOIN users ON group_follows.user_id = users.id WHERE group_follows.group_id = $first_gid ";
		  $i = 1;
		  while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
			  if ($i == 1) { 
				$i++;
			  } else {
			  $gid = $row['group_id'];
			  $query_str .= "OR group_follows.group_id = $gid ";
			  $i++;
			  }
		  }
		  
		  $query_str .= "GROUP BY library.lib_id) AS group_movies WHERE media_id = $media_id ";
		  if ($media_type == 'tv') {
			$query_str .= "AND series_season = $series_season";
		  }
		  
		}
		echo $query_str;
		if ($result = mysqli_query($db_conx, $query_str)) {
		  $matches = mysqli_num_rows($result);
		  if ($matches < 1) {
			  //no mutual group member has movie
			  echo "no_media_matches_in_group";
			  exit();
		  
		  } else {
			  $json_str ="[";
			  while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
				  if ($row['media_type'] == 'tv') {
				    $json_str .= '{"lib_id" : '.$row['lib_id'].', "user_id" : '.$row['user_id'].', "username" : "'.$row['username'].'", "group_name" : "'.$row['group_name'].'", "media_id" : '.$row['media_id'].', "media_type" : "'.$row['media_type'].'", "series_season" : '.$row['series_season'].'},';
				  } else if ($row['media_type'] == 'movie'){
				    $json_str .= '{"lib_id" : '.$row['lib_id'].', "user_id" : '.$row['user_id'].', "username" : "'.$row['username'].'", "group_name" : "'.$row['group_name'].'", "media_id" : '.$row['media_id'].', "media_type" : "'.$row['media_type'].'", "series_season" : null },';
			      }
			  }
			  
			  $json_str = substr_replace($json_str,"]",-1); //replaces last loop comma with ]
			  
			  echo $json_str;
			  exit();
		  }
			  
		} else { echo "database_error"; exit(); }
	} else { echo "database_error"; exit(); }
	
	
//}
?>