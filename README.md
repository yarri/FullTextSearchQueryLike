FullTextSearchQueryLike
=======================

[![Build Status](https://travis-ci.org/yarri/FullTextSearchQueryLike.svg?branch=master)](https://travis-ci.org/atk14/Files)

A PHP class which transforms search strings into clever SQL conditions with the LIKE operator

Transformation examples
-----------------------

| Query string         | SQL LIKE condition                                                         |
|----------------------|----------------------------------------------------------------------------|
| beer                 | title LIKE '%beer%'                                                        |
| beer burger          | title LIKE '%beer%' AND title LIKE '%burger%'                              |
| beer and burger      | title LIKE '%beer%' AND title LIKE '%burger%'                              |
| beer or burger       | title LIKE '%beer%' OR title LIKE '%burger%'                               |
| beer not burger      | title LIKE '%beer%' AND NOT title LIKE '%burger%'                          |
| +beer +burger -pizza | title LIKE '%beer%' AND title LIKE '%burger%' AND NOT title LIKE '%pizza%' |

Basic usage
-----------

Consider a table articles with a field title in which we would like to let users search.

    $q = $_GET["search"]; // Here comes a user query string, e.g. "beer and wine"

    $ftsql = new FullTextSearchQueryLike("title");
    if($ftsql->parse($q)){
      $search_condition = "WHERE ".$ftsql->get_formatted_query(); // e.g. "WHERE title LIKE '%beer%' AND title LIKE '%wine%'"
    }

    $query = "SELECT * FROM articles $search_condition ORDER BY created_at DESC";

Searching in more fields
------------------------

    $ftsql = new FullTextSearchQueryLike("title||' '||body||' '||author");

Case insensitive searching
--------------------------

    $ftsql = new FullTextSearchQueryLike("UPPER(title||' '||body||' '||author)");
    if($ftsql->parse(strtoupper($q))){
      $search_condition = "WHERE ".$ftsql->get_formatted_query();
    }


Installation
------------

Use the Composer to install the FullTextSearchQueryLike.

    composer require yarri/full-text-search-query-like dev-master

Licence
-------

FullTextSearchQueryLike is free software distributed [under the terms of the MIT license](http://www.opensource.org/licenses/mit-license)
