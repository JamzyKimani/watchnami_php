<?php 
if (isset($_POST['findUserByName'])) {
include_once("php_includes/db_conx.php");

  $search_str = preg_replace('#[^a-z_0-9]#i', '', $_POST['query']);
  $stmt = $db_conx->prepare("SELECT id, full_name, username, avatar FROM users WHERE username LIKE '%$search_str%' OR email LIKE '%$search_str%' OR full_name LIKE '%$search_str%'"); /* START PREPARED STATEMENT */
  $stmt->execute(); /* EXECUTE THE QUERY */
  $stmt->store_result();
  if ($stmt->num_rows < 1) {
	echo '{"none_found" : true}';
    exit();	
  } else {
	  $stmt->bind_result($id, $full_name, $username, $avatar); /* BIND THE RESULT TO THIS VARIABLE */
	  while($stmt->fetch()){ /* FETCH ALL RESULTS */
		$found_users[] = array("id" => $id, "full_name" => "$full_name", "username" => "$username", "avatar" => "$avatar"); /* STORE EACH RESULT TO THIS VARIABLE IN ARRAY */
	  } /* END OF WHILE LOOP */

	  echo json_encode($found_users); /* ECHO ALL THE RESULTS */
	  exit();
  }
}
?>
