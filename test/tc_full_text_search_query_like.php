<?php
class TcFullTextSearchQueryLike extends TcBase {

	function test(){
		$ftsql = new FullTextSearchQueryLike("body");

		$this->assertEquals(true,$ftsql->parse("beer"));
		$this->assertEquals("body LIKE '%beer%'",$ftsql->get_formatted_query());
	
		$this->assertEquals(true,$ftsql->parse("beer wine"));
		$this->assertEquals("body LIKE '%beer%' AND body LIKE '%wine%'",$ftsql->get_formatted_query());

		$this->assertEquals(true,$ftsql->parse("beer and wine"));
		$this->assertEquals("body LIKE '%beer%' AND body LIKE '%wine%'",$ftsql->get_formatted_query());

		$this->assertEquals(true,$ftsql->parse("beer not wine"));
		$this->assertEquals("body LIKE '%beer%' AND NOT body LIKE '%wine%'",$ftsql->get_formatted_query());
	}
}
