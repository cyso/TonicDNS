TemplateResource
================

GET
---


Retrieves an existing DNS template.

### Response: ###

```json
{
     {
           "identifier": <string>,
           "description": <string>,
           "entries": [ {
                 "name": <string>,
                 "type": <string>,
                 "content": <string>,
                 "ttl": <int>,
                 "priority": <int>
           },0..n ]
     },0..n
]
```

### Errors (request without identifier): ###

* 500 - Failed to connect to database or query execution error.

### Errors (request with identifier): ###

* 500 - Failed to connect to database or query execution error.
* 404 - Could not find template.

	 


PUT
---


Create a new DNS template.

### Request: ###

```json
{
     "identifier": <string>,
     "description": <string>,
     "entries": [ {
           "name": <string>,
           "type": <string>,
           "content": <string>,
           "ttl": <int>,
           "priority": <int>
     },0..n ]
}
```

### Response: ###

```
true
```

### Errors: ###

* 508 - Invalid request, missing required parameters or input validation failed.
* 500 - Failed to connect to database or query execution error.
* 409 - Template already exists.

	 


POST
----


Update an existing DNS template. This method will overwrite the entire Template.

### Request: ###

```json
{
    "identifier": <string>,
    "description": <string>,
    "entries": [ {
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

### Errors: ###

* 508 - Invalid request, missing required parameters or input validation failed.
* 500 - Failed to connect to database or query execution error.
* 404 - Could not find template.

	 


DELETE
------


Delete an existing DNS template.

### Response: ### 

```
true
```

### Errors: ###

* 508 - Invalid request, missing required parameters or input validation failed.
* 500 - Failed to connect to database or query execution error.
* 404 - Could not find template.

	 


