<?php
class TcFullTextSearchQuery extends TcBase {

	function test(){
		$this->_testValidParse("beer",[
			[
				"term" => "beer",
				"type" => "term",
				"char_position" => 0,
				"occurrence" => "MUST",
				"childs" => [],
			]
		]);

		$this->_testValidParse("not beer",[
			[
				"term" => "beer",
				"type" => "term",
				"char_position" => 4,
				"occurrence" => "NOT",
				"childs" => [],
			],
		]);

		// "beer wine" is same like "beer and wine"

		$beer_and_wine = [
			[
				"term" => "beer",
				"type" => "term",
				"char_position" => 0,
				"occurrence" => "MUST",
				"childs" => [],
			],
			[
				"term" => "wine",
				"type" => "term",
				"char_position" => 5,
				"occurrence" => "MUST",
				"childs" => [],
			],
		];
		$this->_testValidParse("beer wine",$beer_and_wine);

		$beer_and_wine[1]["char_position"] = 9;
		$this->_testValidParse("beer and wine",$beer_and_wine);

		$beer_and_wine[0]["char_position"] = 1;
		$beer_and_wine[1]["char_position"] = 7;
		$this->_testValidParse("+beer +wine",$beer_and_wine);

		// "beer not wine"

		$beer_not_wine = [
			[
				"term" => "beer",
				"type" => "term",
				"char_position" => 0,
				"occurrence" => "MUST",
				"childs" => [],
			],
			[
				"term" => "wine",
				"type" => "term",
				"char_position" => 9,
				"occurrence" => "NOT",
				"childs" => [],
			],
		];
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

	// "not"/"-" bezprostredne pred zavorkou/frazi (bez mezery) drive ztratily
	// negaci - _urci_occurrence_z_posledniho_slova() mela v podmince pro
	// "operator bez mezery" chybne "&&" misto "||" a porovnavala tutez
	// promennou se dvema hodnotami zaroven, takze to nikdy nesedelo a spadlo
	// to na vychozi MUST
	function test_negation_without_space_before_block(){
		$not_zavorka = [
			[
				"term" => "beer",
				"type" => "term",
				"char_position" => 0,
				"occurrence" => "MUST",
				"childs" => [],
			],
			[
				"term" => "wine or juice",
				"type" => "parenthesis",
				"char_position" => 9,
				"occurrence" => "NOT",
				"childs" => [
					[
						"term" => "wine",
						"type" => "term",
						"char_position" => 9,
						"occurrence" => "SHOULD",
						"childs" => [],
					],
					[
						"term" => "juice",
						"type" => "term",
						"char_position" => 17,
						"occurrence" => "SHOULD",
						"childs" => [],
					],
				],
			],
		];
		$this->_testValidParse("beer not(wine or juice)",$not_zavorka);

		// "-" je o dva znaky kratsi nez "not", char_position se tedy posune
		$not_zavorka[1]["char_position"] = 7;
		$not_zavorka[1]["childs"][0]["char_position"] = 7;
		$not_zavorka[1]["childs"][1]["char_position"] = 15;
		$this->_testValidParse("beer -(wine or juice)",$not_zavorka);

		$this->_testValidParse("beer -\"dark wine\"",[
			[
				"term" => "beer",
				"type" => "term",
				"char_position" => 0,
				"occurrence" => "MUST",
				"childs" => [],
			],
			[
				"term" => "dark wine",
				"type" => "phrase",
				"char_position" => 7,
				"occurrence" => "NOT",
				"childs" => [],
			],
		]);
	}

	// drive se escapovani zavorek/uvozovek posuzovalo jen podle jednoho
	// predchoziho znaku, takze "\\(" (escapovane lomitko a za nim realna
	// zavorka) bylo chybne vyhodnoceno jako escapovana zavorka
	function test_escaped_backslash_before_parenthesis(){
		// jedno escapovane lomitko -> zavorka zustava literal, zadna skupina
		$this->_testValidParse("a \\( b",[
			[
				"term" => "a",
				"type" => "term",
				"char_position" => 0,
				"occurrence" => "MUST",
				"childs" => [],
			],
			[
				"term" => "(",
				"type" => "term",
				"char_position" => 3,
				"occurrence" => "MUST",
				"childs" => [],
			],
			[
				"term" => "b",
				"type" => "term",
				"char_position" => 5,
				"occurrence" => "MUST",
				"childs" => [],
			],
		]);

		// dve zpetna lomitka -> prvni escapuje druhe (vznikne jeden literalni
		// backslash), zavorka uz escapovana neni a otevira skutecnou skupinu
		$this->_testValidParse("a \\\\( b)",[
			[
				"term" => "a",
				"type" => "term",
				"char_position" => 0,
				"occurrence" => "MUST",
				"childs" => [],
			],
			[
				"term" => "\\",
				"type" => "term",
				"char_position" => 3,
				"occurrence" => "MUST",
				"childs" => [],
			],
			[
				"term" => " b",
				"type" => "parenthesis",
				"char_position" => 5,
				"occurrence" => "MUST",
				"childs" => [
					[
						"term" => "b",
						"type" => "term",
						"char_position" => 6,
						"occurrence" => "MUST",
						"childs" => [],
					],
				],
			],
		]);
	}

	function _testValidParse($query,$expected_tree){
		$ftsq = new FullTextSearchQuery();

		$this->assertEquals(true,$ftsq->parse($query),"A valid query expected: $query");
		$this->assertEquals($expected_tree,$ftsq->get_tree());
	}
}
