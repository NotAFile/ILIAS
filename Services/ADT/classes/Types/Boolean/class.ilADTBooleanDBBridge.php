<?php

require_once "Services/ADT/classes/Bridges/class.ilADTDBBridge.php";

class ilADTBooleanDBBridge extends ilADTDBBridge
{	
	protected function isValidADT(ilADT $a_adt) 
	{
		return ($a_adt instanceof ilADTBoolean);
	}
	
	
	// CRUD
	
	public function readRecord(array $a_row)
	{
		$this->getADT()->setStatus($a_row[$this->getElementId()]);
	}	
	
	public function prepareInsert(array &$a_fields)
	{
		$a_fields[$this->getElementId()] = array("integer", $this->getADT()->getStatus());
	}	
}

?>