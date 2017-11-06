FullTextSearchQueryLike
=======================

FullTextSearchQueryLike is a PHP class which helps build smarty LIKE conditions for SQL query according to a custom search query string.

If you ever created a web application in which table rows are being selected using the SQL operator LIKE,
with this class you can boost up your application to nearly like "profi full-text search engine" feeling :)

Basic usage
-----------

Consider table articles with a field title in which we would like to let users search.

    $q = $_GET["search"]; // Here comes a user query string, e.g. "beer and wine"

    $ftsql = new FullTextSearchQueryLike("title");
    if($ftsql->parse($q)){
      $search_condition = "WHERE ".$ftsql->get_formatted_query(); // e.g. "WHERE title LIKE '%beer%' AND title LIKE '%wine%'"
    }

    $query = "SELECT * FROM articles $search_condition ORDER BY created_at DESC";

Installation
------------

Use the Composer to install the FullTextSearchQueryLike.

    composer require yarri/full-text-search-query-like dev-master

Licence
-------

FullTextSearchQueryLike is free software distributed [under the terms of the MIT license](http://www.opensource.org/licenses/mit-license)
