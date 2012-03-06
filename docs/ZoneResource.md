ZoneResource
============

GET
---


Retrieves an existing DNS zone.

If no identifier is specified and no body is supplied, all zones will be
retrieved without records.

### Response: ###

```json
{
     {
           "name": <string>,
           "type": MASTER|SLAVE|NATIVE,
           "master": <ipv4 optional>,
           "last_check": <int optional>,
           "notified_serial": <int optional>
     },0..n
]
```

If a query is specified in the URL,  all zones matching the given wildcard
are returned. The * wildcard is supported.

If an identifier is specified, one zone will be retrieved with records.

### Response: ###

```json
{
     "name": <string>,
     "type": MASTER|SLAVE|NATIVE,
     "master": <ipv4>,
     "last_check": <int>,
     "records": [ {
             "name": <string>,
             "type": <string>,
             "content": <string>,
             "ttl": <int optional>,
             "priority: <int optional>,
             "change_date": <int optional>
     },0..n ]
}
```

### Errors (request without identifier): ###

* 508 - Invalid request, missing required parameters or input validation failed.
* 500 - Failed to connect to database or query execution error.

### Errors (request with identifier): ###

* 508 - Invalid request, missing required parameters or input validation failed.
* 500 - Failed to connect to database or query execution error.
* 404 - Could not find zone.

	 


PUT
---


Create a new DNS zone, or insert records into an existing DNS zone.

If no identifier is specified, a new DNS zone is created.

### Request: ###

```json
{
    "name": <string>,
    "type": MASTER|SLAVE|NATIVE,
    "master": <ipv4 optional>,
    "templates": [ {
            "identifier": <string>
    },0..n ]
    "records": [ {
            "name": <string>,
            "type": <string>,
            "content": <string>,
            "ttl": <int optional>,
            "priority": <int optional>
    },0..n ]
}
```

### Response: ###

```
true
```

If an identifier is specified, records will be inserted into an existing DNS zone.

### Request: ###

```json
{
    "records": [ {
            "name": <string>,
            "type": <string>,
            "content": <string>,
            "ttl": <int optional>,
            "priority": <int optional>
    },0..n ]
}
```

### Response: ###

```
true
```

### Errors (request without identifier): ###

* 508 - Invalid request, missing required parameters or input validation failed.
* 500 - Failed to connect to database or query execution error.
* 409 - Zone already exists, or trying to insert records into a SLAVE zone.

### Errors (request with identifier): ###

* 508 - Invalid request, missing required parameters or input validation failed.
* 500 - Failed to connect to database or query execution error.
* 409 - Cannot insert records into a SLAVE zone.
* 404 - Could not find zone.

	 


POST
----


Update an existing DNS zone. Also allows adding and deleting records in one transaction.

Requires that at least one DNS Zone field be modified.

Addition and deletion of records is optional. If specified, the requirement that at least one DNS Zone field has to be modified
is lifted.

If both DNS zone modifications and record modifications are specified, the DNS zone modification is carried out first. If this fails,
the entire transaction will abort. If the zone type changes from MASTER or NATIVE to SLAVE, and records are specified to added or
deleted, this will also result in an error, because SLAVE zones are not allowed to modify records.

### Request: ###

```json
{
    "name": <string>,
    "type": MASTER|SLAVE|NATIVE,
    "master": <ipv4 optional>,
    "records": [ {
            "name": <string>,
            "type": <string>,
            "content": <string>,
            "priority": <int>,
            "mode": add|delete       # optional, default = add
    },0..n ]
}
```


### Response: ###

```
true
```

### Errors (request with identifier): ###

* 508 - Invalid request, missing required parameters or input validation failed.
* 500 - Failed to connect to database or query execution error.
* 404 - Could not find zone.

	 


DELETE
------


Delete an existing DNS zone, or delete a record from the zone.

If an identifier is specified, the entire zone will be deleted.

### Response: ###

```
true
```

If a body is specified, but no identifier, the specified entries will be deleted from the zone.

### Request: ###

```json
{
    "name": <string>,
    "records": [ {
            "name": <string>,
            "type": <string>,
            "content": <string>,
            "priority": <int>
    },1..n ]
}
```

### Response: ###

```
true
```

### Errors (request without identifier): ###

* 508 - Invalid request, missing required parameters or input validation failed.
* 500 - Failed to connect to database or query execution error.
* 409 - Cannot delete records from a SLAVE zone.
* 404 - Could not find zone.

### Errors (request with identifier): ###

* 508 - Invalid request, missing required parameters or input validation failed.
* 500 - Failed to connect to database or query execution error.
* 404 - Could not find zone.

	 


VALIDATE
--------


Validates the properties of a zone.

To validator Zone records, use the RecordResource.

### Request: ###

```json
{
    "name": <string>,
    "type": MASTER|SLAVE|NATIVE,
    "master": <ipv4 optional>
}
```

### Response: ###

```
true
```

### Errors: ###

* 508 - Invalid request, missing required parameters or input validation failed.

	 


