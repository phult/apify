# Apify

A pretty library to help developers build `RESTful API services` lightly, quickly and properly even without writing code.

It's always easy to customize to suit any need such as defining data relationships, authorization, caching, communicating or integrating into other systems.

## Features

* Serves RESTful APIs for any MySql database
  * Pagination 
  * Sorting
  * Selection
  * Grouping, Having
  * Filtering
  * Relationships
  * Metadata
* Supports Event Bus

Use HTTP clients like [Postman](https://www.getpostman.com/) to invoke RESTful API calls.

Combine with [API Gateway](https://github.com/megaads-vn/api-gateway) is also recommended to build a completely development environment for microservice.

## Installation

Apify is packed as a composer package. So it's installed quickly in 2 steps
1. Require the composer package

    `composer require megaads/apify`

2. Register the provider: 

    `Megaads\Apify\ApifyServiceProvider`

## System requirements
 - PHP: >= 5.6
 - Laravel/ Lumen Framework: 5.4.*
 - MySQL
 - Message queue server: optional

## API Overview

| HTTP Method | API URL                          | Description                                            |
|-------------|----------------------------------|--------------------------------------------------------- 
| GET         | /api/table_name                  | List all rows of table that match the query                                 | 
| GET         | /api/table_name/:id              | Retrieve a row by primary key :id                      |
| POST        | /api/table_name                  | Insert a new row, bulk insertion is also avaiable                                       |
| PUT         | /api/table_name/:id              | Replaces existed row with new one                      |
| PATCH       | /api/table_name/:id              | Update row element by primary key                      |
| DELETE      | /api/table_name/:id              | Delete a row by primary key                            |
| DELETE      | /api/table_name                  | Delete bulk that match the query                                            |
| POST      | /api/upload                  | Upload a file                                            |

## Pagination (`?page_size= &page_id=`)

| Parameter   | Required    | Default    | Description                                                      |
|-------------|-------------|------------|------------------------------------------------------------------|
| page_id     | No          | 0          | Page index, start at 0
| page_size   | No          | 50         | Number of rows to retrieve per page

```
/api/post?page_id=2&page_size=20
```

## Sorting (`?sorts=`)

Order by multiple columns using **`sorts`** parameter

### Sort ascending

```
/api/post?sorts=user_id
```

### Sort descending

```
/api/post?sorts=-created_at
```

### Sort by multiple columns

```
/api/post?sorts=user_id,-created_at
```

## Selection (`?fields=`)

Select columns from the results using **`fields`** parameter. SQL aggregate functions such as `COUNT`, `MAX`, `MIN`, `SUM`, `AVG`, SQL aliases are also available

```
/api/post?fields=id,content,user_id,sum(view_count) as view_sum
```

## Group By (`?groups=`)

Group the result-set by one or more columns using **`groups`** parameter and combine with aggregate functions using `Selection`

```
/api/post?fields=user_id,sum(view_count)&groups=user_id
```

## Filtering (`?filters=`)

| Operator   | Condition          |  For example                                         
|--------------|--------------------|----------------------------------
| =            |  Equal to          | /api/post?filters=user_id=1
| !=           |  Not equal         | /api/post?filters=user_id!=1
| >            |  Greater           | /api/post?filters=user_id>1
| >=           |  Greater or equal  | /api/post?filters=user_id>=1
| <            |  Less              | /api/post?filters=user_id<1
| <=           |  Less or equal     | /api/post?filters=user_id<=1
| ={}          |  In                | /api/post?filters=user_id={1;2;3}
| !={}         |  Not in            | /api/post?filters=user_id!={1;2;3}
| =[]          |  Between           | /api/post?filters=user_id=[1;20]
| !=[]         |  Not between       | /api/post?filters=user_id!=[1;20]
| ~            |  Like              | /api/post?filters=title~hello
| !~           |  Not like          | /api/post?filters=title!~hello

Apify supports filtering records based on more than one `AND` condition by using comma. For example: 

```
/api/post?filters=user_id=1,status={enabled;pending},tile~hello,view_count!=null
```

Complex conditions that combine `AND`, `OR` and `NOT` will be available soon.

## Relationships

Apify is packed into a `Laravel`/ `Lumen` package so relationships also are defined as methods on `Eloquent` model classes.

See Laravel docs for details: https://laravel.com/docs/5.6/eloquent-relationships

Let's consider the following relationship definations:

- A `Nation` has many `City` (one-to-many relationship)

```php
namespace App\Models;
class Nation extends \Apify\Models\BaseModel {
    protected $table = 'location_nation';
    public function cities() {
        return $this->hasMany('App\Models\City', 'nation_id', id);
    }
}
```

- A `City` belongs to a `Nation` (many-to-one relationship)
- A `City` has many `District` (one-to-many relationship)

```php
namespace App\Models;
class City extends \Apify\Models\BaseModel {
    protected $table = 'location_city';
    public function nation() {
        return $this->belongsTo('App\Models\Nation', 'nation_id');
    }
    public function districts() {
        return $this->hasMany('App\Models\District', 'city_id', id);
    }
}
```

- A `District` belongs to a `City` (many-to-one relationship)

```php
namespace App\Models;
class District extends \Apify\Models\BaseModel {
    protected $table = 'location_district';
    public function city() {
        return $this->belongsTo('App\Models\City', 'city_id');
    }
}    
```

### Selection on relationships
Apify provides the ability to embed relational data into the results using `embeds` parameter

For example

```
/api/nation?embeds=cities
```

```
/api/city?embeds=nation,districts
```

```
/api/district?embeds=city
```

Even nested relationships

```
/api/nation?embeds=cities.districts
```

```
/api/district?embeds=city.nation
```

### Filtering on relationships

```
/api/city?filters=nation.location_code=EU,districts.name~land
```

## Metric (`?metric=`)

### ?metric=get (default): Retrieve all results that match the query

```
/api/post
```
or

```
/api/post?metric=get
```

Result format

```json
{
    "meta": {
        "has_next": true,
        "total_count": 100,
        "page_count": 2,
        "page_size": 50,
        "page_id": 0
    },
    "results": [],
    "status": "successful"
}
```

### ?metric=first: Retrieve the first result that matchs the query


```
/api/post?metric=first
```

Result format

```json
{    
    "results": {},
    "status": "successful"
}
```

### ?metric=count: Retrieve the number of results that match the query

```
/api/post?metric=count
```

Result format

```json
{    
    "results": 50,
    "status": "successful"
}
```

### ?metric=increment / decrement: Provides convenient methods for incrementing or decrementing the value of a selected column

```
/api/post?metric=increment&fields=view_count
```

Result format

```json
{    
    "results": 1,
    "status": "successful"
}
```

## Event Bus

Is being updated ...

## .env configurations

| Key | Default value                          | Description                                            |
|-------------|----------------------------------|--------------------------------------------------------- 
| APIFY_PREFIX_URL         | `api`                  | API URL prefix                                 | 
| APIFY_MODEL_NAMESPACE         | `App\Models`                  | Models namespace                                 | 
| APIFY_UPLOAD_PATH         | `/home/upload`                  | Upload path                                 | 
| APIFY_MQ_ENABLE         | `false`                  | Enable / Disable Message queue (Event Bus)                                | 
| APIFY_MQ_HOST         |                  | Message queue server host                                 | 
| APIFY_MQ_PORT         |                   | Message queue server port                                 | 
| APIFY_MQ_USERNAME         |                   | Message queue authentication - username                                | 
| APIFY_MQ_PASSWORD         |                   | Message queue authentication - password                                 |                                 | 
## License

The Apify is open-sourced software licensed under the [MIT license](http://opensource.org/licenses/MIT)
## Contact us/ Instant feedback

Skype: phult.bk

Email: phult.contact@gmail.com