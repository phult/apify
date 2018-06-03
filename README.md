# Apify

A RESTful API library in order to help developers to create RESTful API services lightly, quickly even without writing code.

It's always easy to customize to suit any need such as define data relationships, modify/ create new APIs, communicate or integrate into other systems.

And it's pretty!

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

Combining with [API Gateway](https://github.com/megaads-vn/api-gateway) is also recommended to build a completely development environment for microservice.

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
| GET         | /api/table_name                  | List all rows of table                                 | 
| GET         | /api/table_name/:id              | Retrieve a row by primary key :id                      |
| POST        | /api/table_name                  | Create a new row                                       |
| PUT         | /api/table_name/:id              | Replaces existed row with new one                      |
| PATCH       | /api/table_name/:id              | Update row element by primary key                      |
| DELETE      | /api/table_name/:id              | Delete a row by primary key                            |

## Pagination: `?page_size= &page_id=`

| Parameter   | Required    | Default    | Description                                                      |
|-------------|-------------|------------|------------------------------------------------------------------|
| page_id     | No          | 0          | Page index, start at 0
| page_size   | No          | 50         | Number of rows to retrieve per page

```
/api/post?page_id=2&page_size=20
```

## Sorting `?sorts=`

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

## Selection: `?fields=`

Select columns from the results using **`fields`** parameter. SQL aggregate functions such as `COUNT`, `MAX`, `MIN`, `SUM`, `AVG`, SQL aliases are also available

```
/api/post?fields=id,content,user_id,sum(view_count) as view_sum
```

## Group By: `?groups=`

Group the result-set by one or more columns using **`groups`** parameter and combine with aggregate functions using `Selection`

```
/api/post?fields=user_id,sum(view_count)&groups=user_id
```

## Filtering: `?filters=`

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
/api/post?filters=user_id=1,status={enabled;pending},tile~hello
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
    protected $table = 'nation';
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
    protected $table = 'city';
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
    protected $table = 'district';
    public function city() {
        return $this->belongsTo('App\Models\City', 'city_id');
    }
}    
```

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

Filtering on relationships

```
/api/city?filters=nation.id=1
```

instead of

```
/api/city?filters=nation_id=1
```

## Metric: `?metric=`

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

### ?metric=increment / decrement: Pprovides convenient methods for incrementing or decrementing the value of a selected columns

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

## License

The Apify is open-sourced software licensed under the [MIT license](http://opensource.org/licenses/MIT)
## Contact us/ Instant feedback

Skype: phult.bk

Email: info@megaads.vn or phult.contact@gmail.com