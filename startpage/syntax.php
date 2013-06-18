<?php
/**
 * Plugin start page
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Nicolas James <nicolas.james@gmail.com>
 * @based_on   "pageindex" plugin by Kite <Kite@puzzlers.org>
 */
 
if(!defined('DOKU_INC')) {
	define('DOKU_INC',realpath(dirname(__FILE__).'/../../').'/');
}
if(!defined('DOKU_PLUGIN')) {
	define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
}
 
require_once(DOKU_PLUGIN.'syntax.php');
require_once(DOKU_INC.'inc/search.php');

 
function search_list_index2(&$data,$base,$file,$type,$lvl,$opts){
	global $ID;
	//we do nothing with directories
	if($type == 'd') return false;
	if(preg_match('#\.txt$#',$file)){
		$id = pathID($file);

		//check ACL if we don't want to display private pages in the list.
		/*
		if(auth_quickaclcheck($id) < AUTH_READ){
			return false;
		}
		*/

		if($opts['ns'].":$id" <> $ID) {
			$data[] = array( 
				'id'    => $opts['ns'].":$id",
				'type'  => $type,
				'level' => $lvl );
		}
	}
	return false;
}

function html_buildlist2($data,$class,$func,$lifunc='html_li_default'){
     $level = 0;
     $opens = 0;
     $ret   = '';

     foreach ($data as $item){
	/*
	I don't know why, but for pages with the empty namespace, the ID written like this :mpeg7 rather than mpeg7 (no colon), changes the
	behavior of the function auth_quickaclcheck(...). The colon is not allowed for the empty namespace.
	*/
	if(substr($item['id'], 0, 1) != ':'){
		$testacl = auth_quickaclcheck($item['id']);
	} else {
		$testacl = auth_quickaclcheck(substr($item['id'], 1));
	}

	if($testacl < AUTH_READ){
		continue;
	}

       if( $item['level'] > $level ){
         //open new list
         for($i=0; $i<($item['level'] - $level); $i++){
           if ($i) $ret .= "<li class=\"clear\">\n";
           $ret .= "\n<ul class=\"$class\">\n";
         }
       }elseif( $item['level'] < $level ){
         //close last item
         $ret .= "</li>\n";
         for ($i=0; $i<($level - $item['level']); $i++){
           //close higher lists
           $ret .= "</ul>\n</li>\n";
         }
       }else{
         //close last item
         $ret .= "</li>\n";
       }
   
       //remember current level
       $level = $item['level'];
   
       //print item
       $ret .= call_user_func($lifunc,$item);
       $ret .= '<div class="li">';
   
       $ret .= call_user_func($func,$item);

	/*
	if($testacl < AUTH_READ){
		$ret .= '&nbsp;<img src=\''.DOKU_BASE.'lib/plugins/startpage/private_icon4.png\'/>';
	}
	*/

       $ret .= '</div>';
     }
   
     //close remaining items and lists
     for ($i=0; $i < $level; $i++){
       $ret .= "</li></ul>\n";
     }
   
     return $ret;
  }

 
 
 
 
/**
 * All DokuWiki plugins to extend the parser/rendering mechanism
 * need to inherit from this class
 */
class syntax_plugin_startpage extends DokuWiki_Syntax_Plugin {
 
    /**
     * return some info
     */
    function getInfo(){
        return array(
            'author' => 'Nicolas James',
            'email'  => 'nicolas.james@gmail.com',
            'date'   => '2009-02-19',
            'name'   => 'start page',
            'desc'   => '',
            'url'    => '',
        );
    }
 
    /**
     * What kind of syntax are we?
     */
    function getType(){
        return 'substition';
    }
 
    // Just before build in links
    function getSort(){ return 299; }
 
    /**
     * What about paragraphs?
     */
    function getPType(){
        return 'block';
    }
 
    function connectTo($mode) {
       $this->Lexer->addSpecialPattern('~~STARTPAGE[^~]*~~',$mode,'plugin_startpage');
       //$this->Lexer->addSpecialPattern('~~STARTPAGE~~',$mode,'plugin_startpage');
    }
 
 
    /**
     * Handle the match
     */
    function handle($match, $state, $pos, &$handler){
		$match = preg_replace("%~~STARTPAGE(=(.*))?~~%", "\\2", $match);
		//echo "\n\t<!-- syntax_plugin_pageindex.handle() found >> $match << -->\n";
        return $match;
    }
 
    /**
     * Create output
     */
    function render($mode, &$renderer, $data) {
        if($mode == 'xhtml'){
            $text=$this->_startpage($renderer, $data);
            $renderer->doc .= $text;
            return true;
        }
        return false;
    }
 
	function _startpage(&$renderer, $data) {
	global $conf;

	$dir = $conf['datadir']. DIRECTORY_SEPARATOR .str_replace(':',DIRECTORY_SEPARATOR,'');

	$pages = array();

	search($pages, $dir, 'search_list_index2', array('ns' => ''));

	$renderer->doc .= html_buildlist2($pages,'idx','html_list_index','html_li_index');

	$opts1 = array();
	$opts1['ns'] = '';
	$namespaces = array();
	search($namespaces,$conf['datadir'],'search_namespaces',$opts1);

	foreach ($namespaces as $item){

		 $curns = $item['id'];
		 $curns = str_replace(DIRECTORY_SEPARATOR,':',$curns);

		 $dir = $conf['datadir']. DIRECTORY_SEPARATOR .str_replace(':',DIRECTORY_SEPARATOR,$curns);

		 $testacl = auth_quickaclcheck($item['id'].':*');
		 if($testacl < AUTH_READ){
		 continue;
		 }

		 $renderer->doc .= '<h1>'.$curns.'</h1>';

		 $pages = array();

		 search($pages, $dir, 'search_list_index2', array('ns' => $curns));

		 $renderer->doc .= html_buildlist2($pages,'idx','html_list_index','html_li_index');

	}

	} // _startpage()
} // syntax_plugin_startpage


