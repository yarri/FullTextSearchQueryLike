Change Log
==========

All notable changes to this project will be documented in this file.

[0.3] - 2026-09-26
------------------

* a8d0813 - Fixed parentheses and exact-phrase ("...") grouping being silently stripped from queries before parsing, and a related fatal crash when parentheses were combined with `get_formatted_query_with_binds()`
* d0edc0f - Fixed false-positive matches in `set_search_whole_words_only()` mode
* 3dbff70 - Fixed `set_search_whole_words_only()` not populating bind parameters
* a8b685b - Fixed lost NOT/"-" negation when written directly against a parenthesis or phrase with no space, e.g. `not(wine)`
* 2e12aac - Fixed incorrect handling of an escaped backslash directly before a parenthesis or quote, e.g. `a \\( b)`
* 9c53a86 - Fixed `set_field_name()` not actually casting its argument to a string
* 76dbe97 - Added `set_search_word_beginnings_only()` - match a term against the start of a word, not just whole words
* a779030 - Added an `$options` parameter to the static `GetQuery()` shortcut (`like_match`, `search_whole_words_only`, `search_word_beginnings_only`), also fixing a crash when `search_word_beginnings_only` was used through it
* Extended tested PHP compatibility through PHP 8.5

[0.2] - 2019-03-23
------------------

* 7751688 - Fields for searching can be defined as an array

[0.1] - 2018-02-02
------------------

This is just first officially tagged release.
