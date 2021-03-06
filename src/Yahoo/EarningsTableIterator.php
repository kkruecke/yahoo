<?php declare(strict_types=1);
namespace Yahoo;

class EarningsTableIterator implements  \SeekableIterator {

  protected   $html_table;  // YahooTable
  protected   $current_row; // int
  private     $end;         // int

  /*
   * Parameters: range of columns to return from each row.
   */
  public function __construct(EarningsTable $htmltable) //, int $tbl_begin_column, int $tbl_end_column)
  {
     $this->html_table = $htmltable;
     
     $this->current_row = 0; 
     
     if ($this->html_table->row_count() == 0) {
         
         $this->end = 0;
         
     } else {         
      
        $this->end = $this->html_table->row_count() - 1; // Since we use zero-based indexing, we need to subtract one.
     }    
  }

  /*
   * Iterator methods
   */  
  // returns void
  public function rewind() 
  {
     $this->current_row = 0;
  }
  
  // returns bool
  public function valid() : bool
  {
     return $this->current_row != $this->end;
  }

  // returns \SplFixedArray
  public function current() : \SplFixedArray  
  {
    return $this->html_table->getRowData($this->current_row);	  
    /*
    $current_code = $this->getRowData($this->current_row);	  
    return $current_code;
     */ 
  }
  
  // returns int
  public function key() : int
  {
     return $this->current_row;
  }

  // returns void
  public function next() 
  {
     ++$this->current_row;
  }

  // returns void
  public function seek($pos) 
  {
	if ($pos < 0) {

             $this->current_row = 0;

	} else if ($pos < $this->end) {

	     $this->current_row = $pos;

	} else {

	     $this->current_row = $this->end % $pos;
	}

	return;
  }
} 
