FullTextSearchQueryLike
=======================

FullTextSearchQueryLike is a PHP class which helps build smarty LIKE conditions for SQL query according to a custom search query string.

If you ever created a web application in which table rows are being selected using the SQL operator LIKE,
with this class you can boost up your application to nearly like "profi full-text search engine" feeling :)

Basic usage
-----------

    $conditions = [];
    // e.g. $conditions[] = "deleted=FALSE"

    $q = $_GET["search"];

    $search_condition = FullTextSearchQueryLike::GetQuery("UPPER(title||' '||body||' '||author)",strtoupper($q));
    if($search_condition){
      $conditions[] = $search_condition;
    }

    $query = "SELECT * FROM articles";
    if($conditions){
      $query .= " WHERE ".join(") AND (",$conditions).")";
    }
    $query .= " ORDER BY created_at DESC";

Installation
------------

Use the Composer to install the FullTextSearchQueryLike.

    composer require yarri/full-text-search-query-like dev-master

Licence
-------

FullTextSearchQueryLike is free software distributed [under the terms of the MIT license](http://www.opensource.org/licenses/mit-license)
