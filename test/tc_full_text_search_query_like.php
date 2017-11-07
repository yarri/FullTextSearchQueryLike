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
}
