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

		$beer_and_wine[0]["char_position"] = 1;
		$beer_and_wine[1]["char_position"] = 7;
		$this->_testValidParse("+beer +wine",$beer_and_wine);

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

		$beer_not_wine[0]["char_position"] = 1;
		$beer_not_wine[1]["char_position"] = 7;
		$this->_testValidParse("+beer -wine",$beer_not_wine);

		// Invalid queries

		$ftsq = new FullTextSearchQuery();

		$this->assertFalse($ftsq->parse(""));
		$this->assertFalse($ftsq->parse("  "));
		$this->assertFalse($ftsq->parse(null));
	}

	// drive se escapovani zavorek/uvozovek posuzovalo jen podle jednoho
	// predchoziho znaku, takze "\\(" (escapovane lomitko a za nim realna
	// zavorka) bylo chybne vyhodnoceno jako escapovana zavorka
	function test_escaped_backslash_before_parenthesis(){
		// jedno escapovane lomitko -> zavorka zustava literal, zadna skupina
		$this->_testValidParse("a \\( b",array(
			array(
				"term" => "a",
				"type" => "term",
				"char_position" => 0,
				"occurrence" => "MUST",
				"childs" => array(),
			),
			array(
				"term" => "(",
				"type" => "term",
				"char_position" => 3,
				"occurrence" => "MUST",
				"childs" => array(),
			),
			array(
				"term" => "b",
				"type" => "term",
				"char_position" => 5,
				"occurrence" => "MUST",
				"childs" => array(),
			),
		));

		// dve zpetna lomitka -> prvni escapuje druhe (vznikne jeden literalni
		// backslash), zavorka uz escapovana neni a otevira skutecnou skupinu
		$this->_testValidParse("a \\\\( b)",array(
			array(
				"term" => "a",
				"type" => "term",
				"char_position" => 0,
				"occurrence" => "MUST",
				"childs" => array(),
			),
			array(
				"term" => "\\",
				"type" => "term",
				"char_position" => 3,
				"occurrence" => "MUST",
				"childs" => array(),
			),
			array(
				"term" => " b",
				"type" => "parenthesis",
				"char_position" => 5,
				"occurrence" => "MUST",
				"childs" => array(
					array(
						"term" => "b",
						"type" => "term",
						"char_position" => 6,
						"occurrence" => "MUST",
						"childs" => array(),
					),
				),
			),
		));
	}

	function _testValidParse($query,$expected_tree){
		$ftsq = new FullTextSearchQuery();

		$this->assertEquals(true,$ftsq->parse($query),"A valid query expected: $query");
		$this->assertEquals($expected_tree,$ftsq->get_tree());
	}
}
