<?php
class fullTextSearchQuery{
	var
		//
		$AUTO_AVOID_ERROR = true,
		$_error_number = 0,
		$_error_char_position = null,
		$_error_messages = array(
			//byla nalezena prava zavorka, ale chybi ji leva
			"1" => "not opened parenthesis at char %char_position%",
			//zavorka nebyla uzavrena
			"2" => "not closed parenthesis at char %char_position%",
			//fraze nebyla uzvarena
			"3" => "not closed phrase at char %char_position%"
		),
		$_custom_error_message = "",
		$_TREE = array();
		
	function __construct(){

	}

	/*****************************************
		@function parse

		@param $query dotaz do fulltextu
			napr.: "praha and ostrava"

		@return true v pripade uspechu
		@return false v pripade neuspechu
	*****************************************/
	function parse($query){
		settype($query,"string");

		$query = $this->_removeDangerousSymbols($query);

		$query = preg_replace('/([^\s])[+]+([^\s])/','\1 \2',$query);

		//nastaveni do startovaci polohy
		$this->_error_number = 0;
		$this->_error_char_position = null;
		$this->_custom_error_message = "";
		$this->_TREE = array();

		//zpracovani dotazu
		$out = array();
		$_stat = $this->_zpracuj($query,$out);
		if(!$_stat){
			if($this->AUTO_AVOID_ERROR && (is_int(strpos($query,"(")) || is_int(strpos($query,")")) || is_int(strpos($query,'"')))){
				$query = strtr($query,
					array(
						"(" => " ",
						")" => " ",
						'"' => " "
					)
				);
				return $this->parse($query);
			}
			return false;
		}

		//smazani prazednych termu
		$this->_smaz_prazdne_termy($out);
		if(sizeof($out)==0){ return false; }

		//validace stromu
		//volaji se metody valid_term a valid_phrase (tyto metody je fajn redefinovat v dedicne tride, jinak se vse zvaliduje jako spravne)
		$_stat = $this->_validuj_TREE($out);
		if(!$_stat){
			if($this->AUTO_AVOID_ERROR && (is_int(strpos($query,"(")) || is_int(strpos($query,")")) || is_int(strpos($query,'"')))){
				$query = strtr($query,
					array(
						"(" => " ",
						")" => " ",
						'"' => " "
					)
				);
				return $this->parse($query);
			}
			return false;
		}

		//smazani prazednych termu (asi pro jistotu)
		$this->_smaz_prazdne_termy($out);

		//hotovy strom je zde
		$this->_TREE =$out;
		return true;
	}

	protected function _removeDangerousSymbols($query){
		return $query;
	}

	/*****************************************
		@function get_tree

		@return vyparsovany strom
	*****************************************/
	function get_tree(){
		return $this->_TREE;
	}

	/*****************************************
		@function get_last_error_message

		@return textovy popis chyby
	*****************************************/
	function get_last_error_message(){
		if(strlen($this->_custom_error_message)>0){
			return $this->_custom_error_message;
		}
		if(!isset($this->_error_messages["$this->_error_number"])){
			return "";
		}
		return strtr($this->_error_messages["$this->_error_number"],
			array(
				"%char_position%" => ($this->_error_char_position+1)
			)
		);
	}


	/*****************************************
		@function valid_term
			funkce je volana behem validace
			stromu. redefinovat v dedicne tride.
			zcela legitimni postup je nastavit
			term na pradzny string a vratit true.

		@param &$term: string, slovo, ktere se
			ma zkontrolovat
		@param $char_position: integer, poradi
			znaku v dotazu, na kterem je slovo
			z prvniho parametru
		@param &$error_message: string, naplnit
			nejakou hlaskou v pripade chyby	

		@return true v pripade uspechu
		@return false v pripade neuspechu	
	*****************************************/
	function valid_term(&$term,$char_position,&$error_message){
		return true;
	}

	/*****************************************
		@function valid_phrase
			funkce je volana behem validace
			stromu. redefinovat v dedicne tride.
			zcela legitimni postup je nastavit
			term na pradzny string a vratit true.

		@param &$term: string, slova oddelene
			mezerama, ktere se ma zkontrolovat
		@param $char_position: integer, poradi
			znaku v dotazu, na kterem je slovo
			z prvniho parametru
		@param &$error_message: string, naplnit
			nejakou hlaskou v pripade chyby	

		@return true v pripade uspechu
		@return false v pripade neuspechu	
	*****************************************/
	function valid_phrase(&$term,$char_position,&$error_message){
		return true;
	}
	function _validuj_TREE(&$in){
		$_out = array();
		for($i=0;$i<sizeof($in);$i++){
			$this->_validuj_TREE($in[$i]["childs"]);
			if($in[$i]["type"]=="term"){
				$_error_message = "";
				$_stat = $this->valid_term($in[$i]["term"],$in[$i]["char_position"],$_error_message);
				settype($in[$i]["term"],"string");
				if(!$_stat){
					settype($_error_message,"string");
					$this->_custom_error_message = $_error_message;
					return false;
				}
			}
			if($in[$i]["type"]=="phrase"){
				$_error_message = "";
				$_stat = $this->valid_phrase($in[$i]["term"],$in[$i]["char_position"],$_error_message);
				settype($in[$i]["term"],"string");
				if(!$_stat){
					settype($_error_message,"string");
					$this->_custom_error_message = $_error_message;
					return false;
				}
			}
			if(strlen($in[$i]["term"])==0){
				continue;
			}
			$_out[] = $in[$i];
		}
		$in = $_out;
		return true;
	}
	function _smaz_prazdne_termy(&$in){
		$_out = array();
		for($i=0;$i<sizeof($in);$i++){
			$this->_smaz_prazdne_termy($in[$i]["childs"]);
			if($in[$i]["type"]=="parenthesis" && sizeof($in[$i]["childs"])==0){
				continue;
			}
			if($in[$i]["type"]=="phrase" && sizeof($in[$i]["term"])==""){
				continue;
			}
			if($in[$i]["type"]=="term" && sizeof($in[$i]["term"])==""){
				continue;
			}
			$_out[] = $in[$i];
		}
		$in = $_out;
	}
	function _zpracuj($query,&$out,$offset = 0){
		settype($out,"array");
		settype($offset,"integer");
		if(!$this->_rozdel_fraze_a_zovorky($query,$out,$offset)){
			return false;
		}
		$_out = array();
		for($i=0;$i<sizeof($out);$i++){
			//typ zavorky -> rekursivni volani stejne fce
			if($out[$i]["type"]=="parenthesis"){
				$_out[] = $out[$i];
				$_key = sizeof($_out) - 1;
				//rekurse
				if(!$this->_zpracuj($_out[$_key]["term"],$_out[$_key]["childs"],$_out[$_key]["char_position"])){
					return false;
				}
			}
			
			//typ term -> "ucesat vystup"
			//pokud bude "term" po zpracovani prazdny, nic se do pole $_out neprida
			if($out[$i]["type"]=="term"){
				$_temp_ar = array();
				$_temp_ar = $this->_zpracuj_term($out[$i]);
				for($ii=0;$ii<sizeof($_temp_ar);$ii++){
					$_out[] = $_temp_ar[$ii];
				}
			}

			//typ phrase -> "ucesat vystup"
			//pokud bude "term" po zpracovani prazdny, nic se do pole $_out neprida
			if($out[$i]["type"]=="phrase"){
				$_temp_ar = array();
				$_temp_ar = $this->_zpracuj_frazi($out[$i]);
				for($ii=0;$ii<sizeof($_temp_ar);$ii++){
					$_out[] = $_temp_ar[$ii];
				}
			}
		}
		$out = $_out;
		return true;
	}
	function _rozdel_fraze_a_zovorky($query,&$out,$offset){
		settype($query,"string");
		settype($offset,"integer");
		settype($out,"array");
		$error = false;

		$_within_parentheses = false;
		$_parentheses_counter = 0;
		$_within_phrase = false;
		$_item = "";

		$prev_char = "";
		$_last_word_harvest = "";
		$_last_word = "";
		$_occurrence = "MUST";

		//--- zacatek cyklu
		for($i=0;$i<strlen($query);$i++){
			//aktualni znak
			$char = $query[$i];
			//predchozi znak
			if($i>=1){
				$prev_char = $query[$i-1];
			}

			//zacatek zavorky
			if($char=="(" && $prev_char!="\\" && !$_within_parentheses && !$_within_phrase){

				$out[] = array(
					"term" => $_item,
					"type" => "term",
					"char_position" => (($i + $offset) - strlen($_item)), //akt znak se nepocita
					"occurrence" => $_occurrence,
					"childs" => array()
				);
				$_within_parentheses = true;
				$_parentheses_counter++;
				$_item = "";

				if(
					((strtoupper($_last_word)=="AND" || strtoupper($_last_word)=="+") && strlen($_last_word_harvest)==0) ||
					(strtoupper($_last_word_harvest)=="AND" && strtoupper($_last_word_harvest)=="+")
				){
					$_occurrence = "MUST";
				}elseif(
					((strtoupper($_last_word)=="NOT" || strtoupper($_last_word)=="-") && strlen($_last_word_harvest)==0) ||
					(strtoupper($_last_word_harvest)=="NOT" && strtoupper($_last_word_harvest)=="-")
				){
					$_occurrence = "NOT";
				}elseif(
					((strtoupper($_last_word)=="OR") && strlen($_last_word_harvest)==0) ||
					(strtoupper($_last_word_harvest)=="OR")
				){
					$_occurrence = "SHOULD";
				}else{
					$_occurrence = "MUST";
				}
				continue;
			}

			//zvyseni pocitace zavorek uvnitr zavorek
			if($char=="(" && $prev_char!="\\" && $_within_parentheses){
				$_parentheses_counter++;
			}

			//konec zavorky (pokud vyjde $_parentheses_counter==0)
			if($char==")" && $prev_char!="\\" && !$_within_phrase){
				//pokud nejsme v zavorkach
				if(!$_within_parentheses){
					$this->_error_number = 1;
					$this->_error_char_position = $i+$offset;
					return false;
				}
				$_parentheses_counter--;
				if($_parentheses_counter==0){
					$_within_parentheses = false;
					$out[] = array(
						"term" => $_item,
						"type" => "parenthesis",
						"char_position" => (($i + $offset) - strlen($_item)), 
						"occurrence" => $_occurrence,
						"childs" => array()
					);
					$_item = "";
					$_occurrence = "MUST";
					continue;
				}
			}

			//zacatek fraze
			if($char=="\"" && $prev_char!="\\" && !$_within_parentheses && !$_within_phrase){
				$out[] = array(
					"term" => $_item,
					"type" => "term",
					"char_position" => (($i + $offset) - strlen($_item)), //akt znak se nepocita
					"occurrence" => $_occurrence,
					"childs" => array()
				);
				$_within_phrase = true;
				$_item = "";

				if(
					((strtoupper($_last_word)=="AND" || strtoupper($_last_word)=="+") && strlen($_last_word_harvest)==0) ||
					(strtoupper($_last_word_harvest)=="AND" && strtoupper($_last_word_harvest)=="+")
				){
					$_occurrence = "MUST";
				}elseif(
					((strtoupper($_last_word)=="NOT" || strtoupper($_last_word)=="-") && strlen($_last_word_harvest)==0) ||
					(strtoupper($_last_word_harvest)=="NOT" && strtoupper($_last_word_harvest)=="-")
				){
					$_occurrence = "NOT";
				}elseif(
					((strtoupper($_last_word)=="OR") && strlen($_last_word_harvest)==0) ||
					(strtoupper($_last_word_harvest)=="OR")
				){
					$_occurrence = "SHOULD";
				}else{
					$_occurrence = "MUST";
				}

				continue;
			}

			//konec fraze
			if($char=="\"" && $prev_char!="\\" && $_within_phrase){
				$out[] = array(
					"term" => $_item,
					"type" => "phrase",
					"char_position" => (($i + $offset) - strlen($_item)), //akt znak (") se nepocia
					"occurrence" => $_occurrence,
					"childs" => array()
				);
				$_within_phrase = false;
				$_item = "";
				$_occurrence = "MUST";
				continue;
			}

			$_item .= $char;

			//sbirani posledniho slova
			if(!$_within_parentheses && !$_within_phrase){
				if($this->_is_white_char($char)){
					if(strlen($_last_word_harvest)>0){
						$_last_word = $_last_word_harvest;
						$_last_word_harvest = "";
					}
				}else{
					$_last_word_harvest .= $char;
				}
			}else{
				$_last_word_harvest = "";
				$_last_word = "";
			}
		}
		//--- konec cyklu

		//pokud jsme na konci cyklu v zavorkach
		if($_within_parentheses){
			$this->_error_number = 2;
			$this->_error_char_position = ((strlen($query)-1) - strlen($_item)) + $offset;
			return false;
		}

		//pokud jsme na konci cyklu ve frazi
		if($_within_phrase){
			$this->_error_number = 3;
			$this->_error_char_position = ((strlen($query)-1) - strlen($_item)) + $offset;
			return false;
		}

		//posledni term pridame nakonec (i kdyby byl zcela prazdny)
		$out[] = array(
				"term" => $_item,
				"type" => "term",
				"char_position" => ((strlen($query)) - strlen($_item)) + $offset,
				"occurrence" => $_occurrence,
				"childs" => array()
		);
		return true;
	}
	function _zpracuj_term($in){
		settype($in,"array");
		$out = array();
		$query = $in["term"];
		$offset = $in["char_position"];

		$prev_char = "";
		//--- zacatek cyklu
		//Pokud se zpracovava term "jogurt or maslo", mel by byt jogurt podle ocekavani s occurrence nastavenou na SHOULD.
		//V cyklu se implicitne uvazuje s occurrence MUST, proto se zkouma, zda je prvni occurrence nastavena. A v pripade, ze neni nastavena a druha occurrence nastavena je a je zaroven rovna SHOULD, prepise se occurrence u prvni item na SHOULD.
		$_item = "";
		$_occurrence = "MUST";
		$prev_char = "";
		$_first_occurrence_set = false;
		$_second_occcurence = null;
		for($i=0;$i<strlen($query);$i++){
			$char = $query[$i];
			if($i>0){
				$prev_char = $query[$i-1];
			}

			if($this->_is_white_char($char)){
				if(strlen($_item)>0){
					if($_item=="+" || strtoupper($_item)=="AND"){
						$_occurrence = "MUST";
						if(sizeof($out) == 0){ $_first_occurrence_set = true;}
					}elseif($_item=="-" || strtoupper($_item)=="NOT"){
						$_occurrence = "NOT";
						if(sizeof($out) == 0){ $_first_occurrence_set = true;} 
					}elseif(strtoupper($_item)=="OR"){
						$_occurrence = "SHOULD";
						if(sizeof($out) == 0){ $_first_occurrence_set = true;} 
					}else{
						if($_item[0]=="+"){
							$_occurrence = "MUST";
							$_item = substr($_item,1);
							if(sizeof($out) == 0){ $_first_occurrence_set = true;} 
						}elseif($_item[0]=="-"){
							$_occurrence = "NOT";
							$_item = substr($_item,1);
							if(sizeof($out) == 0){ $_first_occurrence_set = true;} 
						}
						if(sizeof($out) == 1){ $_second_occcurence = $_occurrence;}
						$out[] = array(
							"term" => $_item,
							"type" => "term",
							"char_position" => ($i + $offset) - strlen($_item),
							"occurrence" => $_occurrence,
							"childs" => array()
						);
						//nastavit $_occurrence na defaultni hodnotu
						$_occurrence = "MUST";
					}
					$_item = "";
				}
				continue;
			}

			//nevsimat si zpetneho lomitka, pokud predchozi znak nebyl zpetne lomitko
			//jen to mirne posunuje pozice slov v char_position
			if($char=="\\" && $prev_char!="\\"){
				continue;
			}
			$_item .= $char;
		}
		//--- konec cyklu	
		if(strlen($_item)>0){
			if($_item=="+" || strtoupper($_item)=="AND"){
				$_occurrence = "MUST";
			}elseif($_item=="-" || strtoupper($_item)=="NOT"){
				$_occurrence = "NOT";
			}elseif(strtoupper($_item)=="OR"){
				$_occurrence = "SHOULD";
			}else{
				if($_item[0]=="+"){
					$_occurrence = "MUST";
					$_item = substr($_item,1);
				}elseif($_item[0]=="-"){
					$_occurrence = "NOT";
					$_item = substr($_item,1);
				}
				if(sizeof($out) == 1){ $_second_occcurence = $_occurrence;}
				$out[] = array(
					"term" => $_item,
					"type" => "term",
					"char_position" => ($i + $offset) - strlen($_item),
					"occurrence" => $_occurrence,
					"childs" => array()
				);
			}
		}

		if(sizeof($out)>=1 && $_first_occurrence_set==false && $_second_occcurence=="SHOULD"){
			$out[0]["occurrence"] = $_second_occcurence;
		}


		//setrideni termu podle occurrence
		$_out = array();
		$_ar = array("MUST","SHOULD","NOT");
		while(list(,$_occurrence) = each($_ar)){
			for($i=0;$i<sizeof($out);$i++){
				if($out[$i]["occurrence"]==$_occurrence){
					$_out[] = $out[$i];
				}
			}
		}
		return $_out;
	}
	function _zpracuj_frazi($in){
		settype($in,"array");
		$query = $in["term"];
		$offset = $in["char_position"];
		$_occurrence = $in["occurrence"];

		$prev_char = "";
		$_item = "";
		$output = "";
		$_occurrence = "MUST";
		$prev_char = "";
		for($i=0;$i<strlen($query);$i++){
			$char = $query[$i];
			if($i>0){
				$prev_char = $query[$i-1];
			}
			if($this->_is_white_char($char)){
				if(strlen($_item)>0){
					if(strlen($output)>0){
						$output .= " ";
					}
					$output .= $_item;
					$_item = "";
				}
				continue;
			}

			//nevsimat si zpetneho lomitka, pokud predchozi znak nebyl zpetne lomitko
			//jen to mirne posunuje pozice slov v char_position
			if($char=="\\" && $prev_char!="\\"){
				continue;
			}
			$_item .= $char;
		}

		if(strlen($_item)>0){
			if(strlen($output)>0){
				$output .= " ";
			}
			$output .= $_item;
		}

		if(strlen($output)==0){
			return array();
		}
		$in["term"] = $output;
		return array($in);
	}
	function _is_white_char($char){
		settype($char,"string");
		if(strlen($char)!=1){
			return false;
		}
		if(in_array($char,array(" ","\n","\r","\t"))){
			return true;
		}
		return false;
	}
}
?>
