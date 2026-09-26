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

	// hranice PRED a ZA slovem se drive overovaly ve dvou na sobe nezavislych
	// OR skupinach spojenych pres AND - stacilo, aby "pred" vyhovel jinemu
	// vyskytu podretezce v poli nez "za", a vzniknul false-positive
	// (napr. "cat" v "educat, catering", i kdyz tam cele slovo "cat" neni)
	function test_search_whole_words_only(){
		$ftsql = new FullTextSearchQueryLike("title");
		$ftsql->set_search_whole_words_only();
		$this->assertEquals(true,$ftsql->parse("cat"));
		$condition = $ftsql->get_formatted_query();

		$this->assertEquals(false,$this->_like_condition_matches($condition,"educat, catering"));
		$this->assertEquals(false,$this->_like_condition_matches($condition,"concatenate"));
		$this->assertEquals(true,$this->_like_condition_matches($condition,"cat"));
		$this->assertEquals(true,$this->_like_condition_matches($condition,"the cat sat"));
		$this->assertEquals(true,$this->_like_condition_matches($condition,"(cat)"));
	}

	// v tomto rezimu se drive vsechny hodnoty vkladaly rovnou do SQL jako
	// literaly - get_formatted_query_with_binds() vratilo prazdne $bind_ar
	function test_search_whole_words_only_with_binds(){
		$ftsql = new FullTextSearchQueryLike("title");
		$ftsql->set_search_whole_words_only();
		$ftsql->parse("cat");
		$bindings = array();
		$condition = $ftsql->get_formatted_query_with_binds($bindings);

		$this->assertEquals(49,sizeof($bindings));
		$this->assertEquals(true,in_array("cat",array_values($bindings)));
		$this->assertEquals(0,substr_count($condition,"'")); // v $condition uz nejsou zadne SQL literaly, jen bind placeholdery

		$this->assertEquals(false,$this->_like_condition_matches(array_values($bindings),"educat, catering"));
		$this->assertEquals(true,$this->_like_condition_matches(array_values($bindings),"the cat sat"));
	}

	// set_search_word_beginnings_only() - term nemusi byt cele slovo, staci
	// aby se shodoval se zacatkem nejakeho slova v poli
	function test_search_word_beginnings_only(){
		$ftsql = new FullTextSearchQueryLike("title");
		$ftsql->set_search_word_beginnings_only();
		$this->assertEquals(true,$ftsql->parse("cat"));
		$condition = $ftsql->get_formatted_query();

		$this->assertEquals(true,$this->_like_condition_matches($condition,"A green caterpillar"));
		$this->assertEquals(true,$this->_like_condition_matches($condition,"cat"));
		$this->assertEquals(true,$this->_like_condition_matches($condition,"a cat"));

		$ftsql2 = new FullTextSearchQueryLike("title");
		$ftsql2->set_search_word_beginnings_only();
		$this->assertEquals(true,$ftsql2->parse("pill"));
		$condition2 = $ftsql2->get_formatted_query();

		// "pill" je jen uprostred slova "caterpillar", ne na jeho zacatku
		$this->assertEquals(false,$this->_like_condition_matches($condition2,"A green caterpillar"));
	}

	// binding musi fungovat i v tomto rezimu (viz oprava chybejiciho _add_bind
	// v rezimu set_search_whole_words_only())
	function test_search_word_beginnings_only_with_binds(){
		$ftsql = new FullTextSearchQueryLike("title");
		$ftsql->set_search_word_beginnings_only();
		$ftsql->parse("cat");
		$bindings = array();
		$condition = $ftsql->get_formatted_query_with_binds($bindings);

		$this->assertEquals(7,sizeof($bindings));
		$this->assertEquals(true,in_array("cat%",array_values($bindings)));
		$this->assertEquals(0,substr_count($condition,"'"));

		$this->assertEquals(true,$this->_like_condition_matches(array_values($bindings),"A green caterpillar"));
	}

	// jednoducha emulace SQL LIKE (jen "%" jako divoka karta) nad vzory
	// "title LIKE '...'" vygenerovanymi knihovnou pro jedno pole a jeden term,
	// nebo primo nad polem bind hodnot - vyhodnoti se jako OR vsech vzoru
	function _like_condition_matches($condition,$value){
		if(is_array($condition)){
			$patterns = $condition;
		}else{
			preg_match_all("/title LIKE '([^']*)'/",$condition,$m);
			$patterns = $m[1];
		}
		foreach($patterns as $pattern){
			$re = "/^".str_replace("%",".*",preg_quote($pattern,"/"))."$/s";
			if(preg_match($re,$value)){
				return true;
			}
		}
		return false;
	}
}
