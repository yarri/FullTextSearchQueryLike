<?php
class TcFullTextSearchQueryLike extends TcBase {

	function test(){
		$ftsql = new FullTextSearchQueryLike("title");

		$this->assertEquals(true,$ftsql->parse("beer wine"));
		$this->assertEquals("title LIKE '%beer%' AND title LIKE '%wine%'",$ftsql->get_formatted_query());

		$this->assertEquals(true,$ftsql->parse("beer and wine"));
		$this->assertEquals("title LIKE '%beer%' AND title LIKE '%wine%'",$ftsql->get_formatted_query());

		$this->assertEquals(true,$ftsql->parse("beer not wine"));
		$this->assertEquals("title LIKE '%beer%' AND NOT title LIKE '%wine%'",$ftsql->get_formatted_query());

		$this->assertEquals(true,$ftsql->parse("+beer +burger -pizza"));
		$this->assertEquals("title LIKE '%beer%' AND title LIKE '%burger%' AND NOT title LIKE '%pizza%'",$ftsql->get_formatted_query());
	}

	function test_set_field_name(){
		$ftsql = new FullTextSearchQueryLike(array("title","description"));

		$prev_f = $ftsql->set_field_name(array("title"));
		$this->assertEquals("title||' '||description",$prev_f);

		$prev_f = $ftsql->set_field_name("name");
		$this->assertEquals("title",$prev_f);
	}

	function test_bindings(){
		$ftsql = new FullTextSearchQueryLike("title");
		$bindings = [];
		$ftsql->parse("beer and wine");
		$search_condition = "WHERE ".$ftsql->get_formatted_query_with_binds($bindings); // e.g. "WHERE title LIKE '%beer%' AND title LIKE '%wine%'"

		$this->assertEquals("WHERE title LIKE :search_word_021 AND title LIKE :search_word_022",$search_condition);
		$this->assertEquals(array(":search_word_021" => "%beer%", ":search_word_022" => "%wine%"),$bindings);
	}

	// zavorky drive zmizely uz pri tokenizaci, protoze _removeDangerousSymbols()
	// se aplikovala na cely surovy dotaz jeste pred rozdelenim na termy
	function test_parentheses(){
		$ftsql = new FullTextSearchQueryLike("title");
		$this->assertEquals(true,$ftsql->parse("beer and (wine or juice)"));
		$this->assertEquals("title LIKE '%beer%' AND (title LIKE '%wine%' OR title LIKE '%juice%')",$ftsql->get_formatted_query());

		$ftsql = new FullTextSearchQueryLike("title");
		$this->assertEquals(true,$ftsql->parse("not (beer or wine)"));
		$this->assertEquals("NOT (title LIKE '%beer%' OR title LIKE '%wine%')",$ftsql->get_formatted_query());

		$ftsql = new FullTextSearchQueryLike("title");
		$this->assertEquals(true,$ftsql->parse("a and (b or (c and d))"));
		$this->assertEquals("title LIKE '%a%' AND (title LIKE '%b%' OR (title LIKE '%c%' AND title LIKE '%d%'))",$ftsql->get_formatted_query());
	}

	// fraze v uvozovkach byla ze stejneho duvodu rozpadana na jednotliva slova
	function test_quoted_phrase(){
		$ftsql = new FullTextSearchQueryLike("title");
		$this->assertEquals(true,$ftsql->parse("\"dark beer\" burger"));
		$this->assertEquals("title LIKE '%dark beer%' AND title LIKE '%burger%'",$ftsql->get_formatted_query());
	}

	// _get_formatted_query() volala samu sebe pro obsah zavorky bez predani
	// $bind_ar, coz na PHP 7.1+/8.x konci fatalni chybou ArgumentCountError
	function test_bindings_with_parentheses(){
		$ftsql = new FullTextSearchQueryLike("title");
		$ftsql->parse("beer and (wine or juice)");
		$bindings = array();
		$search_condition = $ftsql->get_formatted_query_with_binds($bindings);

		$this->assertEquals(3,sizeof($bindings));
		$this->assertEquals(array("%beer%","%wine%","%juice%"),array_values($bindings));

		$keys = array_keys($bindings);
		$this->assertEquals(
			"title LIKE $keys[0] AND (title LIKE $keys[1] OR title LIKE $keys[2])",
			$search_condition
		);
	}
}
