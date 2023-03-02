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
}
