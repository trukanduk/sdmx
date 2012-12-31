<?php
    require_once('./components/Components.php');
    
    $table = new CTableComponent();
	$table->PushEmptyRow();
	$table->SetStyleAttr('border-style', 'solid');
	$table->SetStyleAttr('border-width', '1');
	
	$table->PushCell(new CTableCellComponent('cell.0.0'));
	$table->PushCell(new CTableCellComponent('cell.0.1',1,  2)); // text, rowspan, colspan
	
	
	$table->PushEmptyRow();
	$table->PushCell(new CTableCellComponent('cell.1.0'));
	$cell2 = new CTableCellComponent('cell.1.1');
	$cell2->SetStyleAttr('border-style', 'none');
	$table->PushCell($cell2);
	$table->PushCell(new CTableCellComponent('cell.1.2'));
	
	echo $table->Encode('Html');
?>
