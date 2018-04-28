<?php

/**
 * Generate a random sequence useable for key
 * @param type $length
 * @return string
 */
function generateRandomString($length = 10) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    return $randomString;
}
/**
 * 
 * @param type $bytes
 * @param type $precision
 * @return a string with most readable size units
 */
function formatBytes($bytes, $precision = 2)
{
    $units = array('B','KB','MB','GB','TB');
    
    $bytes = max($bytes,0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    
    $bytes /= pow(1024, $pow);
    
    return round($bytes,$precision) . ' ' . $units[(int) $pow];
    
}
/**
 * Turn a title into a URL
 * @param type $str
 * @return string 
 */
function url_slug($str)
{	
	#convert case to lower
	$str = strtolower($str);
	#remove special characters
	$str = preg_replace('/[^a-zA-Z0-9]/i',' ', $str);
	#remove white space characters from both side
	$str = trim($str);
	#remove double or more space repeats between words chunk
	$str = preg_replace('/\s+/', ' ', $str);
	#fill spaces with hyphens
	$str = preg_replace('/\s+/', '-', $str);
	return $str;
}

/**
 * Return DOMDocument loaded with html string using UTF-8 hack
 * @param string $html 
 * @param ref errors array of string
 */
function html_utf8_doc(&$html, &$errors)
{
    $doc = new DOMDocument();
    $encoding = 'UTF-8';
    $noErrors = True;
    $saveErrors = libxml_use_internal_errors(true);
    if (!$doc->loadHTML("<?xml encoding='" . $encoding . "'?>" . $html));
    {
        foreach (libxml_get_errors() as $error) {
            if(isset($errors))
                $errors[] = $error->message;
        }
        // should we continue?
        libxml_clear_errors();
        $noErrors = False;
    }
    foreach($doc->childNodes as $item)
    {
        if ($item->nodeType==XML_PI_NODE)
        {
            $doc->removeChild($item);
        }
    }
    libxml_use_internal_errors($saveErrors);
    $doc->encoding = $encoding;
    return $doc;
}

/*
 * PCAN php website model based on Phalcon and Vokura
 */

function visitNodes(DOMNode $p, &$textnodes)
{
    
    foreach($p->childNodes as $node)
    {
        if ($node->nodeType == XML_TEXT_NODE)
            $textnodes[] = $node;
        else
        if ($node->childNodes)
            visitNodes($node, $textnodes);
        
    }
}

/** Strip the html text, as a DOM, of all nodes after text limit breached */

function IntroText(&$htmlText, $limitSize) {
    $arrayErrors = [];
    $doc = html_utf8_doc($htmlText,$arrayErrors);
    $totallen = 0;
    $strip = false;
    $textnodes = array();
    $sizes = array();
    visitNodes($doc->documentElement, $textnodes);
    $result = " " . count($textnodes) . " ";
    foreach ($textnodes as $text) {
        if ($strip==false) {
            $v = $text->nodeValue;
            
            $xlen = strlen($v);
            $sizes[] = $xlen;
            $totallen = (int) $xlen + $totallen;
            if ($totallen > $limitSize) {
                $strip = true;
                
            }
        } else {
            // removing rest of tree and break
            $parent = $text->parentNode;
            $nextSibling = $text->nextSibling;
            $lastnode = $parent;
            while ($lastnode) {
                if (!$nextSibling) {
                    $nextSibling = $lastnode->nextSibling;
                    $parent = $lastnode->parentNode;
                    $lastnode = $parent;
                } else {
                    $sibling = $nextSibling;
                    $nextSibling = $sibling->nextSibling;
                    $parent->removeChild($sibling);
                }
            }
            $result = $result . print_r($sizes,true);
            break;
        }
    }
   
    return $doc->saveHTML();
}