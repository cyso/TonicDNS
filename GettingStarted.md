Getting Started
===============

This document assumes you have some experience using REST APIs. All examples assume you use TonicDNS on a Linux machine, and use MySQL as your backend database. Examples will use cURL, you can find this tool in probably all repositories.

Most of the other documentation is in the source files themselves. Look around in classess/*Resource.class.php. This document is by no means a complete reference on how to use TonicDNS, but it should provide enough examples to get you started and show you where to look for all possible methods.


How to get started
==================

1. Install TonicDNS
2. Setting config files
3. Create TonicDNS user
4. Request token
5. Zone Templates
6. Zones
7. Records
8. Arpa (reverse DNS)
9. Error messages


1\. Install TonicDNS
====================

See the install guide in Readme.md.


2\. Setting config files
========================

After copying the config files, change the DB_DSN, DB_USER and DB_PASS variables in database.conf.php to match the user and database you created for TonicDNS.

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

Also set TOKEN_SECRET to something random.

```bash
$ head -c 30 /dev/urandom | xxd -p
6397a8d8ee707726a837d04af94590752a49c24b07e979b5fe8ec460999b
```

3\. Create TonicDNS user
========================

You can create seperate users to access TonicDNS.

Create user
-----------

You can create a TonicDNS user by inserting a row in the users table. First you need a MD5 hash of the password.

```bash
$ echo -n "mypassword" | md5sum
34819d7beeabb9260a5c854bc85b3e44  -
```

Then execute this INSERT statement.

```sql
mysql> INSERT INTO users VALUES (NULL, 'sampleuser', '34819d7beeabb9260a5c854bc85b3e44','Sample full name','sampleuser@example.org','comment',0,0);
Query OK, 1 row affected (0.00 sec)
```

4\. Request Token
=================

Tokens are required for all REST requests that retrieve or modify. A token is created using the username and password of an existing TonicDNS user, which you prepared earlier.

Prepare a JSON file to request a token.

```json
{
	"username": "sampleuser",
	"password": "samplepw"
}
```

Send this request to /authenticate to request a token.

```bash
$ curl -k -X PUT https://localhost/authenticate -d @./token.json
{"username":"sampleuser","valid_until":1327146727,"hash":"efb9fc406a15bf9bdc60f52b36c14bcc6a1fd041","token":"efb9fc406a15bf9bdc60f52b36c14bcc6a1fd041"}
```
This token will be valid until "1327146727", which equals to "2012-01-21 20:52:07". After the token has expired, request a new one using the same method. Tokens expire by default after 60 seconds. Every request that is executed with a given token refreshes that token, resetting its expiration to 60 seconds.

For exact request and response message formats and error codes, see [AuthenticationResource][authres].

Using a token
-------------

The token must be passed as the x-authentication-token HTTP Header, to all resources that require it. Example usage with curl command,

```bash
$ curl -k -H "x-authentication-token:efb9fc406a15bf9bdc60f52b36c14bcc6a1fd041" \
	-X GET https://localhost/someapi/:identifier
```

5\. Zone templates
==================

Zone templates are used to define the structure of a zone. When creating a zone, you can specify one or more template to apply to it. This copies the records from the template to the new zone, and resolves any variables in the template. Currently, the only supported variable is "[ZONE]", which will be resolved to the name of the zone that is being created.

For exact request and response message formats and error codes, see [TemplationResource][templateres].

Create template
---------------

### API URI ###

* /template/:identifier

### Example ###

Prepare a JSON file (template.json).

```json
{
	"identifier": "sample1",
	"description": "sample template",
	"entries": 
	[ 
		{
			"name": "[ZONE]",
			"type": "SOA",
			"content": "ns.example.org hostmaster.example.org 2012020501 3600 900 86400 3600",
			"ttl": 86400
		},
		{
			"name": "[ZONE]",
			"type": "NS",
			"content": "ns.example.org",
			"ttl": 86400
		},
		{
			"name": "[ZONE]",
			"type": "A",
			"content": "10.0.0.100",
			"ttl": 86400
		},
		{
			"name": "[ZONE]",
			"type": "MX",
			"content": "mx.example.org",
			"ttl": 86400,
			"priority": 10
		},
		{
			"name": "mx.[ZONE]",
			"type": "A",
			"content": "10.0.0.101",
			"ttl": 86400
		}
	]
}
```

Send template.json to URI "/template", using the HTTP PUT method.

```bash
$ curl -s -k -H "x-authentication-token: efb9fc406a15bf9bdc60f52b36c14bcc6a1fd041" \
	-X PUT https://localhost/template \
	-d @'./template.json'
true
```

Retrieve template
-----------------

Retrieves all existing zone templates or a specific existing zone template.

### API URI ###

* /template 

	* retrieving all existing DNS templates.
	
* /template/:identifier

	* retrieving a specific DNS template.

#### Example: Retrieve all templates ####

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

#### Example: Retrieve a specific template ####

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

* /template/:identifier

### Example ###

sample2-template.json

```json
{
	"identifier": "sample2",
	"description": "sample2 template",
	"entries": 
	[ 
		{
			"name": "[ZONE]",
			"type": "SOA",
			"content": "ns.example.com hostmaster.example.com 2012020501 3600 900 86400 3600",
			"ttl": 86400
		},
		{
			"name": "[ZONE]",
			"type": "MX",
			"content": "mx.[ZONE]",
			"ttl": 86400,
			"priority": 20
		},
		{
			"name": "mx.[ZONE]",
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

* /template/:identifier

### Example ###

```bash
$ curl -s -k -H "x-authentication-token: efb9fc406a15bf9bdc60f52b36c14bcc6a1fd041" \
	-X DELETE https://localhost/template/sample2
true
```

6\. Zones
=========

For exact request and response message formats and error codes, see [ZoneResource][zoneres].

Create zone 
-----------

### API URI ###

* /zone 

### Example ###

Prepare a JSON file (zone.json).

```json
{
	"name": "example.org",
	"type": "MASTER",
	"master: null,
	"templates": 
	[
		{
			"identifier": "sample1"
		}
	]
	"records": 
	[ 
		{
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

Retrieve zone
-------------

### API URI ###

* /zone
	
### Example ###

```bash
$ curl -s -k -H "x-authentication-token: efb9fc406a15bf9bdc60f52b36c14bcc6a1fd041" \
 -X GET https://localhost/zone
[
{"name":"example.net","type":"MASTER","master":null,"last_check":null,"notified_serial":2012020601},
{"name":"example.org","type":"MASTER","master":null,"last_check":null,"notified_serial":2012020501}
]
```

Delete zone
-----------

### API URI ###

* /zone/:identifier

### Example ###

```bash
$ curl -s -k -H "x-authentication-token: efb9fc406a15bf9bdc60f52b36c14bcc6a1fd041" \
 -X DELETE https://localhost/zone/example.net
true
```

7\. Records
===========

Records are added and deleted using the Zone Resource. For exact request and response message formats and error codes, see [ZoneResource][zoneres]. The Record Resource can be used to validate record messages, see [RecordResource][recordres].

Create records
--------------

Insert records into an existing DNS zone.

### API URI ###

* /zone/:identifier

### Example ###

Prepare a JSON file (records.json).

```json
{
	"records": 
	[
		{
			"name": "mx2.example.org",
			"type": "A",
			"content": "10.10.10.102"
		},
		{
			"name": "example.org",
			"type": "MX",
			"content": "mx2.example.org",
			"priority": 20
		}
	]
}
```

```bash
$ curl -s -k -H "x-authentication-token: efb9fc406a15bf9bdc60f52b36c14bcc6a1fd041" \
	-X PUT https://localhost/zone/example.org -d@./records.json
true
```

Retrieve records
----------------

### API URI ###

* /zone/:identifier

### Example ###

```bash
$ curl -s -k -H "x-authentication-token: efb9fc406a15bf9bdc60f52b36c14bcc6a1fd041" \
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

Records cannot be updated. To achieve the same result, you must first delete a record, and then recreate it with the desired values.


Delete records
--------------

Delete records from the zone.

### API URI ###

* /zone/:identifier

### Example ###

```json
{
	"name": "example.org",
	"records": 
	[
		{
			"name": "www.example.org",
			"type": "A",
			"content": "172.16.0.1"
		}
	]
}
```

```bash
$ curl -s -k -H "x-authentication-token: efb9fc406a15bf9bdc60f52b36c14bcc6a1fd041" \
 -X DELETE https://localhost/zone/ -d @./record.json

true
```

8\. Arpa (Reverse DNS)
======================

The Arpa Resource can be used to conveniently manage reverse DNS. For exact request and response message formats and error codes, see [ArpaResource][arpares].

Retrieve Arpa
-------------

You can retrieve single Arpa records by supplying its identifier (IP address), or query all Arpa records by providing a comma seperated string of single IPs or IP ranges in CIDR notation: "1.2.3.4,2.3.4.0/24".

### API URI ###

* /arpa/?query=1.2.3.4/24
* /arpa/:identifier

### Example ###

```bash
$ curl -s -k -H "x-authentication-token: efb9fc406a15bf9bdc60f52b36c14bcc6a1fd041" \
 -X GET https://localhost/arpa/1.2.3.4

{"name":"4.3.2.1.in-addr.arpa","ip":"1.2.3.4","reverse_dns":"example.com","arpa_zone":"3.2.1.in-addr.arpa"}
```

Create Arpa
-----------

### API URI ###

* /arpa/:identifier

### Example ###

Prepare a JSON file (arpa.json)

```json
{
	"reverse_dns": "example.org"
}
```

```bash
$ curl -s -k -H "x-authentication-token: efb9fc406a15bf9bdc60f52b36c14bcc6a1fd041" \
 -X PUT https://localhost/arpa/1.2.3.4 -d@./records.json

true
```

Modify Arpa
-----------

As with Zone Records, to modify an Arpa record, delete and recreate it.


Delete Arpa
-----------

Delete an Arpa record.

### API URI ###

* /arpa/:identifier

### Example ###

```bash
$ curl -s -k -H "x-authentication-token: efb9fc406a15bf9bdc60f52b36c14bcc6a1fd041" \
 -X DELETE https://localhost/arpa/1.2.3.4

true
```

9\. Error messages
==================

When an error occurs while executing any function, this will be reflected in the returned HTTP status code. The body of the response will also contain a JSON object containing a human readable error message, and a detailed array containing error codes.

```json
{ 
	"error": "Human Readable message",
	"details": 
	[
	    {
		"code": [ "AUTH_USERNAME_INVALID" ]
	    },
	    {
		"code": [ "RECORD_BOTH_AAAA_TRAILINGDOT" ]
		"id": 5
	    },
	    {
		"code": [ "RECORD_LHS_CNAME_INVALID" ]
		"id": 6
	    }
	]
}
```

The "id" property is only set by the Record validator in the case that a request is validated which contains multiple records. The id is zero based, and signifies for which record the given code(s) are relevant.

For all available codes, see [codes.txt][codes]. 



[authres]: https://github.com/Cysource/TonicDNS/blob/develop/classes/AuthenticationResource.class.php#L52
[arpares]: https://github.com/Cysource/TonicDNS/blob/develop/classes/ArpaResource.class.php#L26
[recordres]: https://github.com/Cysource/TonicDNS/blob/develop/classes/RecordResource.class.php#L25
[templateres]: https://github.com/Cysource/TonicDNS/blob/develop/classes/TemplateResource.class.php#L27
[zoneres]: https://github.com/Cysource/TonicDNS/blob/develop/classes/ZoneResource.class.php#L27
[codes]: https://github.com/Cysource/TonicDNS/blob/develop/codes.txt
