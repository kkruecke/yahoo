<?php declare(strict_types=1);	
namespace Yahoo;

class YahooEarningsTable implements \IteratorAggregate, YahooTableInterface {

   private   $dom;	
   private   $trDOMNodeList;
   private   $row_count;
   private   $column_count;
   private   $url;

  public function __construct(\DateTime $date_time)
  {
   /*
    * The column of the table that the external iterator should return
    */ 	  
    $opts = array(
                'http'=>array(
                  'method'=>"GET",
                  'header'=>"Accept-language: en\r\n" .  "Cookie: foo=bar\r\n")
                 );

    $context = stream_context_create($opts);

    $friendly_date = $date_time->format("m-d-Y"); 

    $this->url = self::make_url($date_time);  

    $page = $this->get_html_file($this->url); 
       
    $this->loadHTML($page);

    $this->loadRowNodes();
  }

  function loadRowNodes()
  {
      $xpath = new \DOMXPath($this->dom);
            
      $nodeList = $xpath->query("(//table)[2]");

      $DOMElement = $nodeList->item(0);

      $nodeList = $xpath->query("tbody", $DOMElement);

      $this->trDOMNodeList = $this->getChildNodes($nodeList);

      $nodeList = $xpath->query("thead/tr", $DOMElement);

      $childNodes = $this->getChildNodes($nodeList); 

      $this->column_count = $childNodes->length; 

      // Prospective code to get column index or something like it.
      // $this->testLoadColumnInfo(array("Symbol", "Company", "EPS Estimate", "Earnings Call Time"), $xpath, $DOMElement);
  } 
  
  private function testLoadColumnInfo(array $column_names, \DOMXPath $xpath, \DOMElement $DOMElement) 
  {  
    // This code works, but doesn't really give the index of the column whose value we found.  
    foreach($column_names as $column_name) {
        
      $query = "thead/tr/th[starts-with(., '$column_name')]";
      
      $node_list = $xpath->query($query, $DOMElement);
      
      if ($node_list->length != 0) {
           $x = $node_list->item(0);    
           var_dump($x);
           echo "\n";
      }
    } 
     //...
  }
  
  function getChildNodes(\DOMNodeList $NodeList)  : \DOMNodeList // This might not be of use.
  {
      if ($NodeList->length != 1) { 
         
          throw new \Exception("DOMNodeList length is not one.\n");
      } 
      
      // Get DOMNode representing the table. 
      $DOMElement = $NodeList->item(0);
      
      if (!$DOMElement->hasChildNodes()) {
         
         throw new \Exception("hasChildNodes() failed.\n");
      } 
  
      // DOMNodelist for rows of the table
      return $DOMElement->childNodes;
  }

  /*
   * returns SplFixedArray of cell text for $rowid
   */ 
  public function getRowData(int $rowid) : \SplFixedArray
  {
     $row_data = new \SplFixedArray($this->column_count); 

     $tdNodelist = $this->getTdNodelist($rowid); 
     
     $i = 0;
     
     foreach($tdNodelist as $td) {

         $nodeValue = trim($td->nodeValue);        
         $row_data[$i++] = html_entity_decode($nodeValue);
     }

     return $row_data;
  }

  // get td node list for row 
  protected function getTdNodelist($row_id) : \DOMNodeList
  {
     // get DOMNode for row number $row_id
     $rowNode =  $this->trDOMNodeList->item($row_id);

     // get DOMNodeList of <td></td> elemnts in the row     
     return $rowNode->getElementsByTagName('td');
  }

  private function loadHTML(string $page) : bool
  {
      $this->dom = new \DOMDocument();
      
      // load the html into the object
      $this->dom->strictErrorChecking = false; // default is true.
      
      // discard redundant white space
      $this->dom->preserveWhiteSpace = false;
  
      @$boolRc = $this->dom->loadHTML($page);  // Turn off error reporting
      return $boolRc;
  } 


  static public function page_exists(\DateTime $date_time) : bool
  {
    return self::url_exists( self::make_url($date_time) );
  }

  static private function url_exists(string $url) : bool
  {
    $file_headers = @get_headers($url);

    if(strpos($file_headers[0], '404 Not Found') !== false) {

        return false;

    } else {

      return true;
    }
  } 

  static private function make_url(\DateTime $date_time) : string
  {
        return Registry::registry('url-path') . '?day=' . $date_time->format('Y-m-d');
  }

  private function get_html_file(string $url) : string
  {
      for ($i = 0; $i < 2; ++$i) {
          
         $page = @file_get_contents($url, false, $context);
                 
         if ($page !== false) {
             
            break;
         }   
         
         echo "Attempt to download data for $friendly_date on webpage $url failed. Retrying.\n";
      }
      
      if ($i == 2) {
          
         throw new \Exception("Could not download page $url after two attempts\n");
      }
      return $page;
  } 
    
  /*
  * Return external iterator, passing the range of columns requested.
  */ 
  public function getIterator() : \Yahoo\YahooEarningsTableIterator
  {
     return new YahooEarningsTableIterator($this);
  }

  public function row_count() : int
  {
     return $this->trDOMNodeList->length;
  } 

  public function column_count() : int
  {
     return $this->column_count;
  }


} 
