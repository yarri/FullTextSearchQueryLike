<?php
/**
 *	$FT = new FullTextSearchQueryLike("article_name");
 *	$FT->set_like_match_both();
 *	if($FT->parse("vino AND pivo")){
 *		$result = $FT->get_formatted_query_with_binds($bind_ar);
 *		var_dump($bind_ar);
 *		echo $result;
 *	}
 *
 *	$condition = FullTextSearchQueryLike::GetQuery("UPPER(name||' '||description)",$q);
 *	$condition = FullTextSearchQueryLike::GetQuery("UPPER(name||' '||description)",$q,$bind_ar);
 */
class FullTextSearchQueryLike extends FullTextSearchQuery{
	/**
	* Jmeno pole, ve kterem se ma vyhledavat.
	*/
	var $_field_name = "";
	/**
	* Z jake strany vyhledavaneho terminu se ma pripojit like.
	* Paklize, je nastaveno $_search_whole_words_only na true, ignoruje se.
	*/
	var	$_like_match = "both"; //"left","right","both", "none"
	/**
	* Cela slova.
	* Pokud bude nastaveno na true, budou se hledat jen cela slova.
	*/
	var	$_search_whole_words_only = false;

	function __construct($field_name = ""){
		$this->set_field_name($field_name);
	}

	static function GetQuery($field,$query,&$bind_ar = null){
		$ft = new FullTextSearchQueryLike($field);
		if($ft->parse($query)){
			if(isset($bind_ar)){
				return $ft->get_formatted_query_with_binds($bind_ar);
			}else{
				return $ft->get_formatted_query();
			}
		}
	}
		
	function set_field_name($field_name){
		settype($_field_name,"string");
		$this->_field_name = $field_name;
	}
	function set_like_match_both(){
		$this->_like_match = "both";
	}
	function set_like_match_left(){
		$this->_like_match = "left";
	}
	function set_like_match_right(){
		$this->_like_match = "right";
	}
	function set_like_match_none(){
		$this->_like_match = "none";
	}
	function set_search_whole_words_only(){
		$this->_search_whole_words_only = true;
	}
	function valid_term(&$slovo,$cislo_znaku,&$error_message){
		return $this->_zpracuj_slovo($slovo,$error_message);
	}
	function valid_phrase(&$fraze,$cislo_znaku,&$error_message){
		$out_ar = array();
		$_ar = explode(" ",$fraze);
		for($i=0;$i<sizeof($_ar);$i++){
			$slovo = $_ar[$i];
			if($slovo==""){
				continue;
			}
			$_stat = $this->_zpracuj_slovo($slovo,$error_message);
			if(!$_stat){
				$fraze = "";
				return false;
			}
			if($slovo!=""){
				$out_ar[] = $slovo;
			}
		}
		$fraze = join(" ",$out_ar);
		return true;
	}

	function parse($query){
		if(parent::parse($query)){
			return strlen($this->get_formatted_query())>0;
		}
		return false;
	}

	function _zpracuj_slovo(&$slovo,&$error_message){

		$slovo = $this->_removeDangerousSymbols($slovo);

		return true;
	}

  protected function _removeDangerousSymbols($slovo){
		$slovo = strtr($slovo,
			array(
				"{" =>  " ",
				"}" =>  " ",
				"%" =>  " ",
				"*" =>  " ",
				"_" =>  " ",
				"$" =>  " ",
				"?" =>  " ",
				"!" =>  " ",
				"(" =>  " ",
				")" =>  " ",
				"|" =>  " ",
				"&" =>  " ",				
				"~" =>  " ",
				"=" =>  " ",
				">" => " ",
				"<" => " ",
				"+" => " ",
				"$" => " ",
				":" => " ",
				"," => " ",
				"[" => " ",
				"]" => " ",
				"|" => " ",
				"#" => " ",
				"^" => " ",
				";" => " ",
				//"/" => "",
				"\\" => " ",
				chr(0) => " ",
				//"accum" =>  "",
				"'" => " ",
				'"' => " "
			)
		);
    $slovo = trim($slovo);
		return $slovo;
  }

	function get_formatted_query(){
		$tree = $this->get_tree();
		$bind_ar = array();
		$out = $this->_get_formatted_query($tree,$bind_ar);
		foreach($bind_ar as $key => &$value){ $value = "'$value'"; }
		return strtr($out,$bind_ar);
	}

	/**
	* $bind_ar(array(":imported" => "Y", ":redaction_id" => 1));
	*
	*
	* $condition = $ft->get_formatted_query_with_binds($bind_ar); 
	*/
	function get_formatted_query_with_binds(&$bind_ar){
		$tree = $this->get_tree();
		return $this->_get_formatted_query($tree,$bind_ar);
	}

	function _get_formatted_query($tree,&$bind_ar){
		settype($bind_ar,"array");
		$_left = "";
		$_right = "";
		if($this->_like_match=="left" || $this->_like_match=="both"){
			$_left = "%";
		}
		if($this->_like_match=="right" || $this->_like_match=="both"){
			$_right = "%";
		}
		if($this->_search_whole_words_only){
			$_left = "";
			$_right = "";
		}
		$out = "";
		for($i=0;$i<sizeof($tree);$i++){
			$item = $tree[$i];
			
			//occurrence
			if($i==0 && $item["occurrence"]=="NOT"){
				$out .= " NOT ";
			}
			if($i>0){
				if($item["occurrence"]=="NOT"){
					$out .= " AND NOT ";
				}
				if($item["occurrence"]=="MUST"){
					$out .= " AND ";
				}
				if($item["occurrence"]=="SHOULD"){
					$out .= " OR ";
				}
			}
		
			if(!$this->_search_whole_words_only && ($item["type"]=="term" || $item["type"]=="phrase")){
				$key = $this->_add_bind("$_left$item[term]$_right",$bind_ar);
				$out .= "$this->_field_name LIKE $key";
			}
			/* Vyhledavani celych slov jenom pomoci LIKE je docela nemozne. Nasledujici reseni to jaksi resi. */
			/* Muze se stat, ze bude nalezeno neco, co nalezeneno byt nemelo */
			if($this->_search_whole_words_only && ($item["type"]=="term" || $item["type"]=="phrase")){
				$out .= "(";
					$out .= "(";
						$out .= "$this->_field_name LIKE '%$item[term]'";
						$out .= " OR ";
						$out .= "$this->_field_name LIKE '%$item[term] %'";
						$out .= " OR ";
						$out .= "$this->_field_name LIKE '%$item[term].%'";
						$out .= " OR ";
						$out .= "$this->_field_name LIKE '%$item[term],%'";
						$out .= " OR ";
						$out .= "$this->_field_name LIKE '%$item[term]/%'";
						$out .= " OR ";
						$out .= "$this->_field_name LIKE '%$item[term])%'";
						$out .= " OR ";
						$out .= "$this->_field_name LIKE '%$item[term]-%'";
					$out .= ") AND (";
						$out .= "$this->_field_name LIKE '$item[term]%'";
						$out .= " OR ";
						$out .= "$this->_field_name LIKE '% $item[term]%'";
						$out .= " OR ";
						$out .= "$this->_field_name LIKE '%.$item[term]%'";
						$out .= " OR ";
						$out .= "$this->_field_name LIKE '%,$item[term]%'";
						$out .= " OR ";
						$out .= "$this->_field_name LIKE '%/$item[term]%'";
						$out .= " OR ";
						$out .= "$this->_field_name LIKE '%($item[term]%'";
						$out .= " OR ";
						$out .= "$this->_field_name LIKE '%-$item[term]%'";
					$out .= ")";
				$out .= ")";
			}

			if($item["type"] == "parenthesis"){
				$out .= " ".'('.$this->_get_formatted_query($item["childs"]).')';
			}
		}
		return trim($out);
	}

	function _add_bind($word,&$bind_ar){
		static $counter;
		if(!isset($counter)){ $counter = 0; }
		$counter++;

		$key = sprintf(":search_word_%03d",$counter);
		$bind_ar[$key] = $word;
		return $key;
	}
}
