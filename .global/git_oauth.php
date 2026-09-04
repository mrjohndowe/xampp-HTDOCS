<?
	//https://[YOUR-GITEA-URL]/login/oauth/authorize?client_id=CLIENT_ID&redirect_uri=REDIRECT_URI& response_type=code&state=STATE
	
	//https://[REDIRECT_URI]?code=RETURNED_CODE&state=STATE
	
	//POST https://[YOUR-GITEA-URL]/login/oauth/access_token
	/*
		{
		  "client_id": "YOUR_CLIENT_ID",
		  "client_secret": "YOUR_CLIENT_SECRET",
		  "code": "RETURNED_CODE",
		  "grant_type": "authorization_code",
		  "redirect_uri": "REDIRECT_URI"
		}
		
		Response:
		
		{
			"access_token": "eyJhbGciOiJIUzUxMiIsInR5cCI6IkpXVCJ9.eyJnbnQiOjIsInR0IjowLCJleHAiOjE1NTUxNzk5MTIsImlhdCI6MTU1NTE3NjMxMn0.0-iFsAwBtxuckA0sNZ6QpBQmywVPz129u75vOM7wPJecw5wqGyBkmstfJHAjEOqrAf_V5Z-1QYeCh_Cz4RiKug",
			"token_type": "bearer",
			"expires_in": 3600,
			"refresh_token": "eyJhbGciOiJIUzUxMiIsInR5cCI6IkpXVCJ9.eyJnbnQiOjIsInR0IjoxLCJjbnQiOjEsImV4cCI6MTU1NzgwNDMxMiwiaWF0IjoxNTU1MTc2MzEyfQ.S_HZQBy4q9r5SEzNGNIoFClT43HPNDbUdHH-GYNYYdkRfft6XptJBkUQscZsGxOW975Yk6RbgtGvq1nkEcklOw"
		}
		
	Endpoints
		Endpoint						URL
		OpenID Connect Discovery		/.well-known/openid-configuration
		Authorization Endpoint			/login/oauth/authorize
		Access Token Endpoint			/login/oauth/access_token
		OpenID Connect UserInfo			/login/oauth/userinfo
		JSON Web Key Set				/login/oauth/keys
	
	*/


?>