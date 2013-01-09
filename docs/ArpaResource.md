ArpaResource
============

GET
---


Retrieves an existing reverse DNS record.

If a query is specified in the URL, all records matching the IPs or ranges
are returned. Multiple queries may be specified, and must be comma separated.

If an identifier is specified, the reverse DNS record for one IP will be 
retrieved.

### Response: ###

```json
{
     {
           "name": <string>,
           "ip": <ip>,
           "reverse_dns": <string>|null,
           "arpa_zone": <string>|null
     },0..n
]
```

### Errors (request without identifier): ###

* 508 - Invalid request, missing required parameters or input validation failed.
* 500 - Failed to connect to database or query execution error.

### Errors (request with identifier): ###

* 508 - Invalid request, missing required parameters or input validation failed.
* 500 - Failed to connect to database or query execution error.
* 404 - Could not find IP.

	 


PUT
---


Inserts a new Arpa record in an existing Arpa zone.

The record will be inserted in the most specific Arpa zone available. If no zone is 
available, an error is returned. If a record already exists, an error is returned.

### Request: ###

```json
{
    "reverse_dns": <string>,
}
```

### Response: ###

```
true
```

### Errors: ###

* 508 - Invalid request, missing required parameters or input validation failed.
* 500 - Failed to connect to database or query execution error.
* 409 - Record already exists, or tried to insert a record in a SALVE Arpa zone.
* 404 - Could not find suitable zone.

	 


DELETE
------


Delete an existing Arpa record.

### Response: ###

```
true
```

### Errors: ###

* 508 - Invalid request, missing required parameters or input validation failed.
* 500 - Failed to connect to database or query execution error.
* 404 - Could not find Arpa zone.

	 


VALIDATE
--------


Validates a set of Arpa requests.

### Request: ###

```json
{
    "arpa": [ {
        "identifier": <string>,
        "reverse_dns": <string>
    },0..n ]
}
```

### Response: ###

```
true
```

### Errors: ###

* 508 - Invalid request, missing required parameters or input validation failed.

	 


