AuthenticationResource
======================

PUT
---


Corresponds to login.

### Request: ###

```json
{
	"username": <username>,
	"password": <password>,
	"local_user": <username>
}
```

### Response: ###

```json
{
     "username": <string>,
     "valid_until": <int>,
     "hash": <string>,
     "token": <string>
}
```

### Errors: ###

* 500 - Invalid request or missing username/password.
* 403 - Username/password incorrect.

	 


POST
----


Corresponds to session validation. 

If the session is valid, the duration is refreshed. If it is 
not, but it does exist, it will be destroyed.

### Response: ###

```
true
```

### Errors: ###

* 500 - Missing or token with invalid format.
* 403 - Invalid token.

	 


DELETE
------


Corresponds to session logout.

### Response: ###

```
true
```

### Errors: ###

* 500 - Missing or token with invalid format.
* 500 - Could not destroy token.
* 403 - Invalid token.

	 


