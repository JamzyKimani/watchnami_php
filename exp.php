<!DOCTYPE html>
<html lang="en">
<head>
	
</head>	
<body>


</body>
<script>
	
	    
		var account_id = "f92455711f1d"; // Obtained from your B2 account page
		var application_key = "0016a13b25bec8e3af2c08d737aa85e3fb5b9ef1d3"; // Obtained from your B2 account page
		var credentials =  btoa(account_id +":"+ application_key);
			 
		var request = new XMLHttpRequest();
		var path="https://api.backblazeb2.com/b2api/v1/b2_authorize_account";
		if(request.readyState == 4 && request.status == 200){
			alert(request.responseText.trim());
		}

		request.open("GET", path, true);
		
		//request.setRequestHeader("User-Agent", "Mozilla/5.0");
		request.setRequestHeader("Accept","application/json");
		request.setRequestHeader("Access-Control-Allow-Origin","*");
		request.setRequestHeader("Authorization", "Basic "+credentials);

		request.send(null);
			
</script>
</html>