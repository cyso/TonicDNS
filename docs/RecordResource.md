RecordResource
==============

VALIDATE
--------


Validates a set of records.

### Request: ###

```json
{
		entries: [ {
             "name": <string>,
             "type": <string>,
             "content": <string>,
             "ttl": <int optional>,
             "priority: <int optional>,
             "change_date": <int optional>
     },0..n ],
		records: [ {
             "name": <string>,
             "type": <string>,
             "content": <string>,
             "ttl": <int optional>,
             "priority: <int optional>,
             "change_date": <int optional>
     },0..n ]
}
```

### Response: ###

```
true
```

### Errors: ###

* 508 - Invalid request, missing required parameters or input validation failed.

	 


POST
----


This method provides legacy support, and is internally redirected to validate(). Older 
versions used the POST method instead of VALIDATE.

	 


