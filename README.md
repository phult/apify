# Apify

A RESTful API dynamic generator in order to help developers to create RESTful API service lightly, quickly and flexible

## Features

* Generates RESTful API for MySql database
  * Pagination 
  * Sorting
  * Selection - Column, SQL Aggregate function
  * Groups
  * Filtering - Row, Relationships
  * Relationships
  * Meta-data response
  * Message communication

Use HTTP clients like [Postman](https://www.getpostman.com/) or [similar tools](https://chrome.google.com/webstore/search/http%20client?_category=apps) to invoke RESTful API calls

Combining with [API Gateway](https://github.com/megaads-vn/api-gateway) is also recommended to build a completely development environment for microservice

## API Overview

| HTTP Method | API URL                          | Description                                            |
|-------------|----------------------------------|--------------------------------------------------------- 
| GET         | /api/table_name                  | List all rows of table                                 | 
| GET         | /api/table_name/:id              | Retrieve a row by primary key :id                      |
| POST        | /api/table_name                  | Create a new row                                       |
| PUT         | /api/table_name/:id              | Update row element by primary key                      |
| DELETE      | /api/table_name/:id              | Delete a row by primary key                            |

## Pagination

```
For example: /api/post?page_id=2&page_size=20
```

| Parameter   | Required    | Default    | Description                                                      |
|-------------|-------------|------------|------------------------------------------------------------------|
| page_id     | no          | 0          | Page index, start with 0
| page_size   | no          | 50         | Number of rows to retrieve per page

## Metrics

## Sorting

| Parameter   | Required    | Default    | Description                                                      |
|-------------|-------------|------------|------------------------------------------------------------------|
| sorts       | no          |            | order by multiple columns. 


#### Sort ascending

```
/api/post?sorts=user_id
```
eg: sorts ascending

#### Sort descending

```
/api/post?sorts=-created_at
```

### Sort by multiple columns

```
/api/post?sorts=user_id,-created_at
```

## Selection

| Parameter   | Required    | Default    | Description                                                      |
|-------------|-------------|------------|------------------------------------------------------------------|
| sorts       | no          |            | Select columns in response of each record. SQL aggregate functions such as Sum, Count, Max are also avaiabled


```
For example: /api/post?fields=id,content,user_id,sum(view_count)
```

## Selection combines Group

## Filtering


| Expression   | Condition          |  For example                                         
|--------------|--------------------|----------------------------------
| =            |  equal to          | /api/post?filters=user_id=1
| !=           |  not equal         | /api/post?filters=user_id!=1
| >            |  greater           | /api/post?filters=user_id>1
| >=           |  greater or equal  | /api/post?filters=user_id>=1
| <            |  less              | /api/post?filters=user_id<1
| <=           |  less or equal     | /api/post?filters=user_id<=1
| <>           |  in                | /api/post?filters=user_id<>1:2:3
| !<>          |  not in            | /api/post?filters=user_id!<>1:2:3
| []           |  between           | /api/post?filters=user_id[]1:20
| ![]          |  not between       | /api/post?filters=user_id![]1:20
| ~            |  like              | /api/post?filters=title~hello
| !~           |  not like          | /api/post?filters=title!~hello


## Relations

## Meta-data response

## License

The Apify is open-sourced software licensed under the [MIT license](http://opensource.org/licenses/MIT)
