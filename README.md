FullTextSearchQueryLike
=======================

[![Build Status](https://travis-ci.com/yarri/FullTextSearchQueryLike.svg?branch=master)](https://travis-ci.com/yarri/FullTextSearchQueryLike)
[![Downloads](https://img.shields.io/packagist/dt/yarri/full-text-search-query-like.svg)](https://packagist.org/packages/yarri/full-text-search-query-like)

A PHP class which transforms search strings into clever SQL conditions with the LIKE operator.

The FullTextSearchQueryLike is fully tested in PHP from version 5.3 to 8.0.

Basic usage
-----------

Consider a table articles with a field title in which we would like to let users search.

    $q = $_GET["search"]; // Here comes a user query string, e.g. "beer and wine"

    $ftsql = new FullTextSearchQueryLike("title");
    if($ftsql->parse($q)){
      $search_condition = "WHERE ".$ftsql->get_formatted_query(); // e.g. "WHERE title LIKE '%beer%' AND title LIKE '%wine%'"
    }

    $query = "SELECT * FROM articles $search_condition ORDER BY created_at DESC";


Transformation examples
-----------------------

| Query string         | Formatted query                                                            |
|----------------------|----------------------------------------------------------------------------|
| beer                 | title LIKE '%beer%'                                                        |
| beer burger          | title LIKE '%beer%' AND title LIKE '%burger%'                              |
| beer and burger      | title LIKE '%beer%' AND title LIKE '%burger%'                              |
| beer or burger       | title LIKE '%beer%' OR title LIKE '%burger%'                               |
| beer not burger      | title LIKE '%beer%' AND NOT title LIKE '%burger%'                          |
| +beer +burger -pizza | title LIKE '%beer%' AND title LIKE '%burger%' AND NOT title LIKE '%pizza%' |

Some other specialities...

| Query string               | Formatted query                                                                                   |
|----------------------------|---------------------------------------------------------------------------------------------------|
| 'beer'                     | title LIKE '%beer%'                                                                               |
| or                         |                                                                                                   |
| ' OR ''='                  |                                                                                                   |
| '; DROP TABLE articles; -- | title LIKE '%DROP%' AND title LIKE '%TABLE%' AND title LIKE '%articles%' AND NOT title LIKE '%-%' |


Searching in more fields
------------------------

    $q = $_GET["search"];

    $ftsql = new FullTextSearchQueryLike("title||' '||body||' '||author");
    // or
    // $ftsql = new FullTextSearchQueryLike(["title","body","author"]);
    if($ftsql->parse($q)){
      $search_condition = "WHERE ".$ftsql->get_formatted_query();
    }

    $query = "SELECT * FROM articles $search_condition ORDER BY created_at DESC";


Case insensitive searching
--------------------------

    $q = $_GET["search"];

    $ftsql = new FullTextSearchQueryLike("UPPER(title||' '||body||' '||author)");
    if($ftsql->parse(strtoupper($q))){
      $search_condition = "WHERE ".$ftsql->get_formatted_query();
    }

    $query = "SELECT * FROM articles $search_condition ORDER BY created_at DESC";


Installation
------------

Use the Composer to install the FullTextSearchQueryLike.

    composer require yarri/full-text-search-query-like

Testing
-------

In the package directory run:

    composer update --dev
    cd test
    ../vendor/bin/run_unit_tests

Final notice
------------

Let's be honest. The code style is not great and all comments are in Czech language. The source was extracted from the very old project. But all the time it works well and reliably.

I really like it and install it to every new project i'm starting. It will be my pleasure when you find it useful too.

Licence
-------

FullTextSearchQueryLike is free software distributed [under the terms of the MIT license](http://www.opensource.org/licenses/mit-license)

[//]: # ( vim: set ts=2 et: )
