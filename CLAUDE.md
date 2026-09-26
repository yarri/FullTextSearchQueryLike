# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What this is

A small, dependency-free PHP library (`yarri/full-text-search-query-like`) that parses a user-entered
search string (e.g. `beer and wine`, `+beer +burger -pizza`, `"exact phrase"`) into a tree, then renders
that tree as a SQL `WHERE` condition built from `LIKE` operators (with optional parameter binding).
Supports PHP 5.6 through 8.x.

## Commands

Install dependencies (dev dependency is `atk14/tester`, a minimal custom test runner — not PHPUnit):

    composer update --dev

Run the full test suite (must `cd test` first — the runner scans the current directory for `tc_*.php` files):

    cd test
    ../vendor/bin/run_unit_tests

Run a single test file:

    cd test
    ../vendor/bin/run_unit_tests tc_full_text_search_query_like

There is no lint/static-analysis tooling configured in this repo.

## Architecture

Two classes in `src/`, one extending the other:

- `src/full_text_search_query.php` — `FullTextSearchQuery`. Generic query parser with **no SQL knowledge**.
  Tokenizes a query string into a tree of nodes (`term`, `phrase`, `parenthesis`), each tagged with an
  `occurrence` of `MUST` / `SHOULD` / `NOT` (derived from `AND`/`OR`/`NOT`/`+`/`-` operators and quoted
  phrases). Recursive-descent-ish parsing lives in `_zpracuj()` / `_rozdel_fraze_a_zovorky()` (splits into
  phrases/parentheses) → `_zpracuj_term()` / `_zpracuj_frazi()` (assigns occurrence per term/phrase).
  Exposes `parse()`, `get_tree()`, `get_last_error_message()`, and two extension points meant to be
  overridden by subclasses: `valid_term()` and `valid_phrase()` (called during tree validation; can
  mutate/sanitize the term and reject it by returning `false`).
  On a parse error, `parse()` auto-retries once with parentheses/quotes stripped out
  (`AUTO_AVOID_ERROR`, default on) rather than failing outright.

- `src/full_text_search_query_like.php` — `FullTextSearchQueryLike extends FullTextSearchQuery`. Adds the
  SQL-rendering layer:
  - `_removeDangerousSymbols()` strips SQL-metacharacters/operators (`%`, `_`, quotes, parens, etc.) out of
    each term — this is the main injection-safety mechanism, run via `valid_term`/`valid_phrase` during
    tree validation, so sanitize/extend it there rather than at the rendering step.
  - `get_formatted_query()` walks the tree and turns MUST/SHOULD/NOT + term/phrase/parenthesis nodes into
    `field LIKE '%word%' AND ...` (grouping mirrors `(` `)` nesting).
  - `get_formatted_query_with_binds(&$bind_ar)` does the same but emits `:search_word_NNN` placeholders and
    fills `$bind_ar` instead of interpolating literals — the counter is a `static` inside `_add_bind()`, so
    it keeps incrementing across calls within a single process/test run (visible in
    `test/tc_full_text_search_query_like.php`, where expected bind keys are `:search_word_021` etc., not
    `_001`).
  - `set_like_match_left/right/both/none()` controls where the `%` wildcard goes; `set_search_whole_words_only()`
    switches to a boundary-approximation strategy using multiple `OR`'d `LIKE` patterns (see comment in
    `_get_formatted_query()` — this is a heuristic, not exact word-boundary matching).
  - Static convenience wrapper: `FullTextSearchQueryLike::GetQuery($field, $query, &$bind_ar = null)`.

Source comments are in Czech (a deliberate, long-standing choice per the README — not something to "fix").

## Testing conventions

Test files live in `test/tc_*.php` and each defines a class named by converting the filename to
StudlyCaps (e.g. `tc_full_text_search_query_like.php` → `TcFullTextSearchQueryLike`), extending `TcBase`
from `atk14/tester`. `test/initialize.php` is the bootstrap the runner loads before each test file — it
just requires the two `src/` files directly (no autoloading in tests).
