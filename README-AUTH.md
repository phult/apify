# Apify Auth Plugin

## Introduction

This is an `Apify` plugin that support you configure `Apify` authentication & authorization.

## How it works?

This plugin will read config from file when every request to `Apify`. It also get `api token` from request to authenticate user, authorization that user, then decide this request is valid or not.

## Getting started

Copy apify config from Config.example directory inside package

```cp vendor/megaads/apify/src/Config.example/apify.php Config/apify.php ```

Now you can see an apify authentication file in `Config` folder.

Edit
`'token' => 'full'` to your `api_token` which can has full accesss to your database.

Edit
`'token' => 'read'` to your `api_token` which can read all your database.

User without token has no access to your database.

Now every with api_token like this `/product?api_token=read` can read product, an every request without api_token will do nothing end return

```
{
"status": "fail",
"message": "Access denied!"
}
```


## Customize apify auth plugins

### Disable apify auth plugins?

Change `'enable' => true`, to `'enable' => false`.

### Add, edit, remove user?

Just add, edit, remove config of that user inside `users`

```
[
 'token' => 'yourApiTokenHere',
 'permissions' => [
 ]
]
```

### How about user without api_token?

Just edit user with `'token' = ''`

### Edit permission of one user

Edit permissions inside `permissions`. To grant an access to user, just add that permission to permissions array.


| Endpoint | Method | Permission | Description |
| :------------- | :------------- | :------------- |:------------- |
| /api/entity | get | read | List all records of table that match the query |
| /api/entity/:id | get | read | Retrieve a record by primary key :id |
| /api/entity | post | create | Insert a new record, bulk inserting is also avaiable |
| /api/entity/:id | put | update | Replaces existed record with new one |
| /api/entity/:id | patch | update | Update record element by primary key |
| /api/entity/:id | delete | delete | Delete a record by primary key |
| /api/entity | delete | delete |Delete bulk records that match the query |

To grant where by raw sql, add `raw` permission.

### Grant access to extract tables or eloquents?

Replace `*` with your table name or eloquent name.

Example :
```
[
    'token' => 'someApiToken',
    'permissions' => [
        'user' => ['create', 'read', 'update', 'delete'],
        'order' => ['create', 'read'],
    ]
]
```

User with api_token can create, read, update, delete user eloquent / table; but he just create / read order only.

## Change apify api_token field to avoid conflict your database ?

Change ```'api_token_field' => 'api_token'``` to ```'api_token_field' => 'your_api_token_field'```


## Contact us/ Instant feedback

Email: phult.contact@gmail.com or quanbka.cntt@gmail.com
