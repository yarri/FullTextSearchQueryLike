<?php
class TcFullTextSearchQuery extends TcBase {

	function test(){
		$this->_testValidParse("beer",array(
			array(
				"term" => "beer",
				"type" => "term",
				"char_position" => 0,
				"occurrence" => "MUST",
				"childs" => array (),
			)
		));

		$this->_testValidParse("not beer",array(
			array (
				"term" => "beer",
				"type" => "term",
				"char_position" => 4,
				"occurrence" => "NOT",
				"childs" => array (),
			),
		));

		// "beer wine" is same like "beer and wine"

		$beer_and_wine = array(
			array (
				"term" => "beer",
				"type" => "term",
				"char_position" => 0,
				"occurrence" => "MUST",
				"childs" => array (),
			),
			array (
				"term" => "wine",
				"type" => "term",
				"char_position" => 5,
				"occurrence" => "MUST",
				"childs" => array (),
			),
		);
		$this->_testValidParse("beer wine",$beer_and_wine);

		$beer_and_wine[1]["char_position"] = 9;
		$this->_testValidParse("beer and wine",$beer_and_wine);

		// "beer not wine"

		$beer_not_wine = array(
			array (
				"term" => "beer",
				"type" => "term",
				"char_position" => 0,
				"occurrence" => "MUST",
				"childs" => array (),
			),
			array (
				"term" => "wine",
				"type" => "term",
				"char_position" => 9,
				"occurrence" => "NOT",
				"childs" => array (),
			),
		);
		$this->_testValidParse("beer not wine",$beer_not_wine);

		// Invalid queries

		$ftsq = new FullTextSearchQuery();

		$this->assertFalse($ftsq->parse(""));
		$this->assertFalse($ftsq->parse("  "));
		$this->assertFalse($ftsq->parse(null));
	}

	function _testValidParse($query,$expected_tree){
		$ftsq = new FullTextSearchQuery();

		$this->assertEquals(true,$ftsq->parse($query),"A valid query expected: $query");
		$this->assertEquals($expected_tree,$ftsq->get_tree());
	}
}
