<?php
if (isset($_POST['coords'])) {
  include_once("php_includes/db_conx.php"); 
  
  $uid = preg_replace('#[^0-9]#i', '', $_POST['uid']);
  $coords =  preg_replace('#[^0-9.,-]#i', '', $_POST['coords']); //mainly removes brackets from coords string leaving coma-seperated lat and long coordinates
  $coords_arr = explode(',', $coords); //stores lat and long values individually in an array
  
  $lat = $coords_arr[0];
  $lng = $coords_arr[1];
   
  $sql ="SELECT z.group_id, z.group_name, z.group_description, z.lat, z.lng, p.distance_unit * DEGREES(ACOS(COS(RADIANS(p.latpoint)) * COS(RADIANS(z.lat)) * COS(RADIANS(p.longpoint) - RADIANS(z.lng)) + SIN(RADIANS(p.latpoint)) * SIN(RADIANS(z.lat)))) AS distance_in_km FROM groups AS z JOIN ( SELECT $lat AS latpoint, $lng AS longpoint, 50.0 AS radius, 111.045 AS distance_unit ) AS p ON 1=1 WHERE z.lat BETWEEN p.latpoint - (p.radius / p.distance_unit) AND p.latpoint + (p.radius / p.distance_unit) AND z.lng BETWEEN p.longpoint - (p.radius / (p.distance_unit * COS(RADIANS(p.latpoint)))) AND p.longpoint + (p.radius / (p.distance_unit * COS(RADIANS(p.latpoint)))) ORDER BY distance_in_km LIMIT 20";
  $markers = mysqli_query($db_conx, $sql);
  $nearby_groups = mysqli_num_rows($markers);
  
  if ($nearby_groups < 1) {
	  //no groups within a 50km radius of users location
	  //consider shortening the radius
	  echo "no_nearby_groups";
	  exit();
	  
  } else {
	  $groups_json = "[";
	  while ($row = mysqli_fetch_array($markers, MYSQLI_ASSOC)) {
		  $group_id = $row["group_id"];
		 $group_name = $row["group_name"];
		 $group_bio = $row["group_description"];
		  $group_lat = $row["lat"];
		  $group_lng = $row["lng"];
		  $distance_in_km = $row["distance_in_km"];
		  
	   $groups_json = $groups_json.'{"id" : '.$group_id.', "group_name" : "'.$group_name.'", "group_description" : "'.$group_bio.'", "latitude" : '.$group_lat.', "longitude" : '.$group_lng.', "distance_in_km" : '.$distance_in_km.', ';  
  
 
		$sql = "SELECT g_follow_id, user_rank FROM group_follows WHERE group_id='$group_id' AND user_id='$uid' LIMIT 1";
		$check_group_membership = mysqli_query($db_conx, $sql);
		$is_member = mysqli_num_rows($check_group_membership);
		
		if ($is_member > 0) {
			
			$membership_row = mysqli_fetch_row($check_group_membership); //returns enumerated array item on index 0 is g_follow_id while index 1 is user_rank
			$user_rank = $membership_row[1];
			$groups_json = $groups_json.'"is_member" : true, "is_admin" : '.$user_rank.' },';
			
			
		} else {
			
			$groups_json = $groups_json.'"is_member" : false, "is_admin" : 0 },';
			
		}

	  }
	  
	  $groups_json = substr_replace($groups_json,"]",-1);
	  
	  echo $groups_json;
	  exit();
  }
}
?>
<?php 
if (isset($_POST['g_door'])) {
  include_once("php_includes/db_conx.php"); 
  
  $uid = preg_replace('#[^0-9]#i', '', $_POST['uid']);
  $g_id = preg_replace('#[^0-9]#i', '', $_POST['g_id']);
  
  //CHECK IF USER IS ALREADY GROUP MEMBER
  $sql = "SELECT g_follow_id, user_rank FROM group_follows WHERE group_id='$g_id' AND user_id='$uid' LIMIT 1";
  $check_membership = mysqli_query($db_conx, $sql);
  $is_member = mysqli_num_rows($check_membership);
  
  if ($is_member < 1) {
    //not member, so add into group
	$sql = "INSERT INTO group_follows(group_id, user_id, user_rank, g_follow_date) VALUES ('$g_id','$uid','0',now())";
    $join_group = mysqli_query($db_conx, $sql);
	echo "joined_group";
	exit();
	
  } else {
    //user is already a member of the group
	$member_info = mysqli_fetch_row($check_membership);
	$g_follow_id = $member_info[0];
	$user_rank = $member_info[1];
    if ($user_rank == 1){ 
	  //group member is admin
	  //are there other admins?
	  $sql = "SELECT g_follow_id FROM group_follows WHERE group_id='$g_id' AND user_rank='1' AND user_id !='$uid'";
	  $result = mysqli_query($db_conx, $sql);
	  $other_admins = mysqli_num_rows($result);
	  
	  if ($other_admins < 1) {
	    //user is sole admin and wants to leave
		//find other members in group to succeed user as admin
		$sql = "SELECT g_follow_id FROM group_follows WHERE group_id=$g_id AND user_id !=$uid ORDER BY g_follow_id ASC";
		$result = mysqli_query($db_conx, $sql);
        $total_members_remaining = mysqli_num_rows($result);
		  
		  if ($total_members_remaining < 1) { 
			 //user is last group member and is leaving group
			 //alert user that group will be destroyed if he leaves
			 echo "last_user_leaving_group";
			 exit();
		  } else { 
             //find admins successor 
			 //fetch array of group follow ids ordered from oldest member first to newest... mysql query before this if statement did the ordering
			$remaining_members = mysqli_fetch_array($result, MYSQLI_ASSOC);
			
			$successor = min($remaining_members); // lowest g_follow_id reps the longest lasting group member left in the group
		    
			$sql = "UPDATE group_follows SET user_rank='1' WHERE g_follow_id=$successor LIMIT 1";
			$new_admin = mysqli_query($db_conx, $sql);
			//remove user, old admin, from group
			$sql="DELETE FROM group_follows WHERE g_follow_id='$g_follow_id' LIMIT 1";
			$exit_group = mysqli_query($db_conx, $sql);
			
			echo "left_group";
			exit();
		  }		
			
	  } else { // there are other admins
	    //remove user from group
	    $sql="DELETE FROM group_follows WHERE g_follow_id='$g_follow_id' LIMIT 1";
		$exit_group = mysqli_query($db_conx, $sql);
			
		echo "left_group";
		exit();
	  }
	} else {
		//group member is not admin and wants to exit group
		$sql="DELETE FROM group_follows WHERE g_follow_id='$g_follow_id' LIMIT 1";
		$exit_group = mysqli_query($db_conx, $sql);
			
		echo "left_group";
		exit();
	}
  }
}

?>
<?php 
if (isset($_POST['g_delete'])) {
  include_once("php_includes/db_conx.php"); 
  
  $uid = preg_replace('#[^0-9]#i', '', $_POST['uid']);
  $g_id = preg_replace('#[^0-9]#i', '', $_POST['g_id']);
  
  $sql = "DELETE FROM group_follows WHERE group_id='$g_id'; DELETE FROM groups WHERE group_id='$g_id' LIMIT 1";
  $result= mysqli_query($db_conx, $sql);
  
  echo 'group_deleted';
  exit();
  
  //The user_id was not used but I might want to do something with it later... a log or smth
} 
?>
<?php
if (isset($_POST['u_groups'])) {
include_once("php_includes/db_conx.php");

  $uid = preg_replace('#[^0-9]#i', '', $_POST['uid']);
	
  $sql = "SELECT group_id, user_rank FROM group_follows WHERE user_id = $uid";
  if ($result = mysqli_query($db_conx, $sql)) {
	  //check if user is a member of at least one group
	  $total_groups = mysqli_num_rows($result);
	  
	  if ($total_groups < 1) {
		//not a member of any group
		echo "no_group_joined";
		exit();
	  }

	  $json_str = '[';
	  while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
		  $g_id = $row['group_id'];
		  $user_rank = $row['user_rank'];
		  
		  $sql = "SELECT G.group_name, G.group_description, G.lat, G.lng, COUNT(DISTINCT group_follows.user_id) AS total_members, COUNT(DISTINCT L.media_id) as total_titles FROM group_follows JOIN users U ON group_follows.user_id = U.id JOIN library L ON group_follows.user_id = L.user_id JOIN groups G ON group_follows.group_id = G.group_id WHERE group_follows.group_id = $g_id";
		  
		  if ($excecute = mysqli_query($db_conx, $sql)) {
			 //ideally, query returns only one row, hence fetch_row function
			 $g_row = mysqli_fetch_row($excecute); 
			  
			 $g_name = $g_row[0];
			 $g_description = $g_row[1];
			 $g_lat = $g_row[2];
			 $g_lng = $g_row[3];
			 $total_members = $g_row[4];
			 $total_movies = $g_row[5];
			 
			 $json_str = $json_str.'{"group_id" : '.$g_id.', "group_name" : "'.$g_name.'", "group_bio" : "'.$g_description.'", "lat" : '.$g_lat.', "lng" : '.$g_lng.', "total_members" : '.$total_members.', "movies_in_group" : '.$total_movies.', "is_member" : true, "is_admin" : '.$user_rank.'},';
		  
		  } else { echo "database_error"; exit();}
 
	  } //end of while loop
	  $json_str = substr_replace($json_str,"]",-1); //replaces last loop comma with ]
	  echo $json_str;
	  exit();
  } else { echo "database_error"; exit(); }
}
?>
<?php
if (isset($_POST['g_by_id'])) {
 include_once("php_includes/db_conx.php");
 
  $uid = preg_replace('#[^0-9]#i', '', $_POST['uid']);
  $g_id = preg_replace('#[^0-9]#i', '', $_POST['g_id']);

  $sql = "SELECT G.group_name, G.group_description, G.lat, G.lng, COUNT(DISTINCT group_follows.user_id) AS total_members, COUNT(DISTINCT L.media_id) as total_titles FROM group_follows JOIN users U ON group_follows.user_id = U.id JOIN library L ON group_follows.user_id = L.user_id JOIN groups G ON group_follows.group_id = G.group_id WHERE group_follows.group_id = $g_id";
  
  if ($excecute = mysqli_query($db_conx, $sql)) {
	 //ideally, query returns only one row, hence fetch_row function
	 $g_row = mysqli_fetch_row($excecute); 
	  
	 $g_name = $g_row[0];
	 $g_description = $g_row[1];
	 $g_lat = $g_row[2];
	 $g_lng = $g_row[3];
	 $total_members = $g_row[4];
	 $total_movies = $g_row[5];
	 
	 $json_str = '{"group_id" : '.$g_id.', "group_name" : "'.$g_name.'", "group_bio" : "'.$g_description.'", "lat" : '.$g_lat.', "lng" : '.$g_lng.', "total_members" : '.$total_members.', "movies_in_group" : '.$total_movies.', ';
	 
	 $sql = "SELECT user_rank FROM group_follows WHERE user_id='$uid' AND group_id='$g_id' LIMIT 1";
	 if ($result = mysqli_query($db_conx, $sql)) {
		//check if user is a member of at least one group
		$is_member = mysqli_num_rows($result);
		
		if ($is_member == 1) {
		  $row = mysqli_fetch_row($result);
		  $user_rank = $row[0];
		  $json_str .= '"is_member" : true, "is_admin" : '.$user_rank.' }';
		
		}else {
		  $json_str .= '"is_member" : false, "is_admin" : 0 }';	
		}
		
		echo $json_str;
		exit();
	 
	 } else { echo "database_error"; exit();}
  } else { echo "database_error"; exit();}
}
?>