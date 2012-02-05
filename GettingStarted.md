How to get started
==================

1. Install (see more "Quick Install Guide" of README.markdown)
2. Setting config files
3. create user (User and Token)
4. create token (User and Token)
5. create template (Template)
6. create zone (Zone)
7. create records (Records)


Setting config files
====================

After copy config files, change below parameter of database.conf.php

```php
<?php
(snip)
const DB_DSN = "mysql:dbname=powerdns;host=localhost";
const DB_USER = "pdnsdbusername";
const DB_PASS = "pdnsdbpassword";
(snip)
const TOKEN_SECRET = "randomstrings";
(snip)
?>
```

User and Token
==============

Create user
-----------

You create user to use TonicDNS firstly, you must generate md5 hashed password. You prepare below temporary PHP script.

```php
<?php
	$password = "samplepw";
	printf("%s\n", md5($password));
?>
```

Then execute this script with php command-line interpreter. (That is included as php5-cli package in Debian systems.)

```bash
$ php generatePassword.php
5829ac9c32b97e95f1e3703d714d5743
```

Execute SQL to SQLite or MySQL directly.

```sql
mysql> INSERT INTO users
VALUES (NULL, `sampleuser`, `5829ac9c32b97e95f1e3703d714d5743`,
`Sample full name`,`sampleuser@example.org`,`comment`,0,0);
Query OK, 1 row affected (0.00 sec)
```

Create Token
------------

Prepare below JSON file (token.json).

### Request format ###

```json
{
	"username": "<username>",
	"password": "<password>",
	"local_user": "<username>"
}
```

### Response ###

```json
{
    "username": "<string>",
	"valid_until": <int>,
	"hash": "<string>",
	"token": "<string>"
}
```

### Errors ###

* 503

	* Invalid request or missing username/password.

* 403

	* Username/password incorrect.

### example ###

```json
{
	"username": "sampleuser",
	"password": "samplepw",
	"local_user": "sampleuser"
}
```
Create token with curl command.

```bash
$ curl -k -X PUT https://localhost/authenticate -d @./token.json
{"username":"sampleuser","valid_until":1327146727,"hash":"efb9fc406a15bf9bdc60f52b36c14bcc6a1fd041","token":"efb9fc406a15bf9bdc60f52b36c14bcc6a1fd041"}
```
This token will be valid until "1327146727" as "2012-01-21 20:52:07". After the token will have expiered, create token with save method again.

Usage token
-----------

Specify x-authentication-token as HTTP Header. example usage with curl command,

```bash
$ curl -k -H "x-authentication-token:efb9fc406a15bf9bdc60f52b36c14bcc6a1fd041" \
	-X GET https://localhost/someapi/:identifier
```

Template
========

Create template
---------------

Create a new DNS template.

### API URI ###

/template/:identifier

### Request format ###

```json
{
	"identifier": "<string>",
	"description": "<string>",
	"entries": [ {
		"name": "<string>",
		"type": "<string>",
		"content": "<string>",
		"ttl": <int optional>,
		"priority": <int optional>
	},0..n
}
```

### Response ###

true

### Errors ###

* 508

	* Invalid request, missing required parameters or input validation failed.
	
* 500

	* Failed to connect to database or query execution error.
	
* 409

	* Template already exists.

### example ###

Prepare a below json file(template.json).

```json
{
	"identifier": "sample1",
	"description": "sample template",
	"entries": [ {
		"name": "example.org",
		"type": "SOA",
		"content": "ns.example.org hostmaster.example.org 2012020501 3600 900 86400 3600",
		"ttl": 86400
	},{
		"name": "example.org",
		"type": "NS",
		"content": "ns.example.org",
		"ttl": 86400
	},{
		"name": "ns.example.org",
		"type": "A",
		"content": "10.0.0.100",
		"ttl": 86400
	},{
		"name": "example.org",
		"type": "MX",
		"content": "mx.example.org",
		"ttl": 86400,
		"priority": 10
	},{
		"name": "mx.example.org",
		"type": "A",
		"content": "10.0.0.101",
		"ttl": 86400
	}
]
}
```
Send template.json to URI "/template/:identifier" via HTTP PUT method like nexe example.

```bash
$ curl -s -k -H "x-authentication-token: efb9fc406a15bf9bdc60f52b36c14bcc6a1fd041" \
	-X PUT https://localhost/template/sample \
	-d @'./template.json'
true
```

Read template
-------------

Retriesves all existing DNS templates or a specific existing DNS template.

### API URI ###

* /template 

	* retrieving all existing DNS templates.
	
* /template/:identifier

	* retrieving a specific DNS template.

### Response ###

#### all templates ####

```json
[
	{
        "identifier": "<string>",
		"description": "<string>",
		"entries": [ {
			"name": "<string>",
			"type": "<string>",
			"content": "<string>",
			"ttl": "<int>",
			"priority": "<int>"
		},0..n ]
	},0..n
]
```

#### a sipecific template ####

```json
{
    "identifier": "<string>",
	"description": "<string>",
	"entries": [ {
		"name": "<string>",
		"type": "<string>",
		"content": "<string>",
		"ttl": "<int>",
		"priority": "<int>"
	},0..n ]
}
```
### Error ###

#### all tempates ####

* 500

	* Failed to connect to database or query execution error.

#### a spacific template ####

* 500

	* Failed to connect to database or query execution error.
	
* 404

	* Could not find template.

### example ###

#### retrieve all templates ####

```bash
$ curl -s -k -H "x-authentication-token: efb9fc406a15bf9bdc60f52b36c14bcc6a1fd041" \
	-X GET https://localhost/template
[
{"identifier":"sample1","entries":[
{"name":"example.org","type":"SOA","content":"ns.example.org hostmaster.example.org 2012020501 3600 900 86400 3600","ttl":"86400","priority":null},
{"name":"example.org","type":"NS","content":"ns.example.org","ttl":"86400","priority":null},
{"name":"ns.example.org","type":"A","content":"10.0.0.100","ttl":"86400","priority":null},
{"name":"example.org","type":"MX","content":"mx.example.org","ttl":"86400","priority":"10"},
{"name":"mx.example.org","type":"A","content":"10.0.0.101","ttl":"86400","priority":null}],"description":"sample template"},
{"identifier":"sample2","entries":[
{"name":"example.com","type":"SOA","content":"","ttl":"86400","priority":null},
{"name":"example.com","type":"NS","content":"ns.example.com","ttl":"86400","priority":null},
{"name":"example.com","type":"A","content":"192.168.0.100","ttl":"86400","priority":null}],"description":"sample2 template"}]
```

#### retrieve a specific template ####

```bash
$ curl -s -k -H "x-authentication-token: efb9fc406a15bf9bdc60f52b36c14bcc6a1fd041" \
	-X GET https://localhost/template/sample1
{"identifier":"sample1","entries":[
{"name":"example.org","type":"SOA","content":"ns.example.org hostmaster.example.org 2012020501 3600 900 86400 3600","ttl":"86400","priority":null},
{"name":"example.org","type":"NS","content":"ns.example.org","ttl":"86400","priority":null},
{"name":"ns.example.org","type":"A","content":"10.0.0.100","ttl":"86400","priority":null},
{"name":"example.org","type":"MX","content":"mx.example.org","ttl":"86400","priority":"10"},
{"name":"mx.example.org","type":"A","content":"10.0.0.101","ttl":"86400","priority":null}],"description":"sample template"}
```

Update template
---------------

Updating an existing DNS template. This method will overwrite the entire template.

### API URI ###

/template/:identifier

### Request format ###

```json
{
	"identifier": "<string>",
	"description": "<string>",
	"entries": [ {
		"name": "<string>",
		"type": "<string>",
		"content": "<string>",
		"ttl": <int optional>,
		"priority": <int optional>
	},0..n
}
```
### Response ###

true

### Errors ###

* 508

	* Invalid request, missing required parameters or input validation failed.
	
* 500

	* Failed to connect to database or query execution error.
	
* 404

	* Could not find template.

### Example ###

sample2-template.json

```json
{
	"identifier": "sample2",
	"description": "sample2 template",
	"entries": [ {
		"name": "example.com",
		"type": "SOA",
		"content": "ns.example.com hostmaster.example.com 2012020501 3600 900 86400 3600",
		"ttl": 86400
	},{
		"name": "example.com",
		"type": "MX",
		"content": "mx.example.com",
		"ttl": 86400,
		"priority": 20
	},{
		"name": "mx.example.com",
		"type": "A",
		"content": "192.168.0.101",
		"ttl": 86400
	}
]
}
```

Update specific template.

```bash
$ curl -s -k -H "x-authentication-token: efb9fc406a15bf9bdc60f52b36c14bcc6a1fd041" \
	-X POST https://localhost/template/sample2 -d @./sample2-template.json
true
```

Delete template
---------------

### API URI ###

/template/:identifier

### Response ###

true

### Errors ###

* 508

	* Invalid request, missing required parameters or input validation failed.
	
* 500

	* Failed to connect to database or query execution error.
	
* 404

	* Could not find template.

### Example ###

```bash
$ curl -s -k -H "x-authentication-token: efb9fc406a15bf9bdc60f52b36c14bcc6a1fd041" \
	-X DELETE https://localhost/template/sample2
true
```

Zone
====

Create zone 
-----------

### API URI ###

/zone 

### Request format ###

```json
{
	"name": "<string>",
	"type": "MASTER|SLAVE|NATIVE",
	"master": "<ipv4 optional>",
	"templates": [ {
		"identifier": "<string>"
		},0..n ]
	    "records": [ {
			"name": "<string>",
			"type": "<string>",
			"content": "<string>",
			"ttl": <int optional>,
			"priority": <int optional>
			},0..n ]
}
```


### Response ###

true

### Errors ###

* 508

	* Invalid request, missing required parameters or input validation failed.
	
* 500

	* Failed to connect to database or query execution error.
	
* 409

	* Zone already exists, or trying to insert records into a SLAVE zone.

### Example ###

prepare a below json file(zone.json).

```json
{
	"name": "example.org",
	"type": "MASTER",
	"master: null,
	"templates": [ {
		"identifier": "sample1"
	}]
	"records": [ {
		"name": "www.example.org",
		"type": "A",
		"content": "10.10.10.200",
		"ttl": 86400
	}
]
}
```

```bash
$ curl -s -k -H "x-authentication-token: efb9fc406a15bf9bdc60f52b36c14bcc6a1fd041" \
	-X PUT https://localhost/zone/ -d@./zone.json
true
```

Read zone
---------

### API URI ###

/zone
	
### Response ###

```json
[
{
    "name": "<string>",
	"type": "MASTER|SLAVE|NATIVE",
	"master": "<ipv4 optional>",
	"last_check": <int optional>,
	"notified_serial": "<int optional>
},0..n
]
```


### Errors ###

* 508

	* Invalid request, missing required parameters or input validation failed.

* 500

	* Failed to connect to database or query execution error. 

### Example ###

This method cannot work now, then excepted result is below.

```bash
$ curl -s -k -H "x-authentication-token: "efb9fc406a15bf9bdc60f52b36c14bcc6a1fd041" \
 -X GET https://localhost/zone
[
{"name":"example.net","type":"MASTER","master":null,"last_check":null,"notified_serial":2012020601},
{"name":"example.org","type":"MASTER","master":null,"last_check":null,"notified_serial":2012020501}
]
```

Update zone
-----------

### API URI ###

/zone/:identifier

### Request format ###

```json
{
	"name": "<string>",
    "type": "MASTER|SLAVE|NATIVE",
	"master": "<ipv4 optional>",
}
```

### Resource ###

true

### Errors ###

* 508

	* Invalid request, missing required parameters or input validation failed.
	
* 500

	* Failed to connect to database or query execution error.
	
* 404

	* Could not find zone.
	
### Example ###

Delete zone
-----------

### API URI ###

/zone/:identifier

### Response ###

true

### Errors ###

* 508 

	* Invalid request, missing required parameters or input validation failed.
	
* 500

	* Failed to connect to database or query execution error.
	
* 409

	* Cannot delete records from a SLAVE zone.

* 404

	* Could not find zone.
	
### Examples ###

```bash
$ curl -s -k -H "x-authentication-token: efb9fc406a15bf9bdc60f52b36c14bcc6a1fd041" \
 -X DELETE https://localhost/zone/example.net
true
```

Records
==========

Create records
--------------

Insert records into an existing DNS zone.

### API URI ###

/zone/:identifier

### Request format ###

```json
{
	"records": [ {
		"name": "<string>",
		"type": "<string>",
		"content": "<string>",
		"ttl": <int optional>,
		"priority": <int optional>
	},0..n ]
}
```


### Response ###

true

### Errors ###

* 508

	* Invalid request, missing required parameters or input validation failed.
	
* 500

	* Failed to connect to database or query execution error.
	
* 409

	* Cannot insert records into a SLAVE zone.
	
* 404

	* Could not find zone.

### Example ###

prepare a below json file(records.json).

```json
{
"records": [
{
 "name": "mx2.example.org",
 "type": "A",
 "content": "10.10.10.102"
},{
 "name": "example.org",
 "type": "MX",
 "content": "mx2.example.org",
 "priority": 20
}]
}
```

```bash
$ curl -s -k -H "x-authentication-token: efb9fc406a15bf9bdc60f52b36c14bcc6a1fd041" \
	-X PUT https://localhost/zone/example.org -d@./records.json
true
```

Read records
------------

### API URI ###

/zone/:identifier

### Response ###

```json
{
    "name": "<string>",
	"type": "MASTER|SLAVE|NATIVE",
	"master": "<ipv4>",
	"last_check": <int>,
	"records": [ {
		"name": "<string>",
		"type": "<string>",
		"content": "<string>",
		"ttl": <int optional>,
		"priority: <int optional>,
		"change_date": <int optional>
	},0..n ]
}
```

### Errors ###

* 508

	* Invalid request, missing required parameters or input validation failed.

* 500

	* Failed to connect to database or query execution error.
	
* 404

	* Could not find zone. 


### Example ###

```bash
$ curl -s -k -H "x-authentication-token: "efb9fc406a15bf9bdc60f52b36c14bcc6a1fd041" \
 -X GET https://localhost/zone/example.org
{"name":"example.org","type":"MASTER","records":[
{"name":"example.org","type":"SOA","content":"ns.example.org hostmaster.example.org 2012020501 3600 900 86400 3600","ttl":"86400","priority":null,"change_date":"1328449038"},
{"name":"example.org","type":"NS","content":"ns.example.org","ttl":"86400","priority":null,"change_date":"1328449038"},
{"name":"example.org","type":"MX","content":"mx.example.org","ttl":"86400","priority":"10","change_date":"1328449038"},
{"name":"example.org","type":"MX","content":"mx2.example.org","ttl":"86400","priority":"20","change_date":"1328449038"},
{"name":"mx.example.org","type":"A","content":"10.0.0.101","ttl":"86400","priority":null,"change_date":"1328449038"},
{"name":"mx2.example.org","type":"A","content":"10.10.0.102","ttl":"86400","priority":null,"change_date":"1328449038"},
{"name":"ns.example.org","type":"A","content":"10.0.0.100","ttl":"86400","priority":null,"change_date":"1328449038"},
{"name":"www.example.org","type":"A","content":"172.16.0.1","ttl":"86400","priority":null,"change_date":"1328449038"}]} 
```

Update records
--------------

This method is not work.


Delete records
--------------

Delete records from the zone.

### API URI ###

/zone/:identifier

### Request format ###

```json
{
	"name": "<string>",
	"records": [ {
		"name": "<string>",
		"type": "<string>",
		"content": "<string>",
		"priority": <int optional>
	},1..n ]
}
```

### Response ###

true

### Erros ###

* 508

	* Invalid request, missing required parameters or input validation failed.
	
* 500 

	* Failed to connect to database or query execution error.
	
* 404

	* Could not find zone.
    
* 409 

	* Cannot delete records from a SLAVE zone.

* 404

	* Could not find zone.

### Example ###

```json
{
	"name": "example.org",
	"records": [{
		"name": "www.example.org",
		"type": "A",
		"content": "172.16.0.1"
}]
}
```

```bash
$ curl -s -k -H "x-authentication-token: efb9fc406a15bf9bdc60f52b36c14bcc6a1fd041" \
 -X DELETE https://localhost/zone/ -d @./record.json
true
```

Reverse DNS Resoece
===================

This method is only worked in develop branch.


