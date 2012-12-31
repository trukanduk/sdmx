<?php
    require_once('BaseComponent.php');
    require_once('TextComponent.php');
    require_once('ComplexComponent.php');
    
    // ������ �������. � �������� ����������� - �����
    interface ITableCell {
        
        // ����� ������
        function GetText();
        function SetText($text);
        
        // ��������� Colspan � Rowspan
        function GetColspan();
        function SetColspan($collspan);
        
        function GetRowspan();
        function SetRowspan($rowspan);
    }   
    
    class CTableCellComponent extends CBaseComponent implements ITableCell {
        // ************
        // ����� ������
        protected $text = '';
        
        function GetText() {
            return $this->text;
        }
        
        function SetText($text) {
            $this->text = $text;
        }
        
        // *****************
        // Colspan � Rowspan
        protected $rowspan = 1;
        
        function GetRowspan() {
            return $this->rowspan;
        }
        
        function SetRowspan($rowspan) {
            $this->rowspan = $rowspan;
        }
        
        protected $colspan = 1;
        
        function GetColspan() {
            return $this->colspan;
        }
        
        function SetColspan($colspan) {
            $this->colspan = $colspan;
        }
        
        // ***********************************
        // ��� ��� ���������� �� BaseComponent
        function GetComponentType() {
            return 'TableCell';
        }
        
        function AppendParams(CEncoderParams $params) {
            $ret = new CEncoderParams($params);
            
            $ret->SetParam('text', $this->GetText());
            $ret->SetParam('style', $this->GetFinalStyle());
            $ret->SetParam('id', $this->GetId());
            $ret->SetParam('class', ''); // **************************************************** STUB!
            $ret->Setparam('rowspan', $this->GetRowspan());
            $ret->SetParam('colspan', $this->GetColspan());
            
            return $ret;
        }
        
        // ***********
        // �����������
        function __construct($text = '', $rowspan = 1, $colspan = 1) {
            parent::__construct();
            
            $this->text = $text;
            $this->rowspan = $rowspan;
            $this->colspan = $colspan;
        }
    }
    
    // ��������� ��� ������ �������
    interface ITableRow {
        function PushCell(CTableCellComponent $cel);
        //function PopCell(); // � ����� �� ����� �������? �����, ���
        
        // ������ ���������� �����
        function GetCellsCount();
        
        // ����� colspan'�� ���� �����
        function GetRowWidth();
    }
    
    class CTableRowComponent extends CComplexComponent implements ITableRow {
        // ��������� ����� ������
        function PushCell(CTableCellComponent $cell) {
            //$newCell = new CTableCellComponent($text, $rowspan, $colspan);
            $this->components[] = $cell;
            $cell->SetParentComponent($this);
            
        }
        
        // ���������� �����
        function GetCellsCount() {
            return $this->GetComponentsCount();
        }
        
        // ������ ������
        function GetRowWidth() {
            $ret = 0;
            foreach ($this->components as $cell)
                $ret += $cell->GetColspan();
            return $ret;
        }
        
        // ��������� ���������
        protected function AppendParams(CEncoderParams $params) {
            $ret = new CEncoderParams($params);
            
            $ret->SetParam('cells', $this->components);
            $ret->SetParam('id', $this->GetId());
            $ret->SetParam('style', $this->GetFinalStyle());
            
            return $ret;
        }
        
        function GetComponentType() {
            return 'TableRow';
        }
        
        // �����������
        function __construct() {
            parent::__construct();
        }
    }
    
    // ��������� ��� ������
    interface ITable {
        // �������� ������ ������
        function PushEmptyRow();
        // �������� ������ � ��������� ������
        function PushCell(CTableCellComponent $cell);
        
        // ���������� �����
        function GetRowsCount();
    }
    
    // �������
    class CTableComponent extends CComplexComponent implements ITable {
        // ��� ����������
        function GetComponentType() {
            return 'Table';
        }
        
        // ��������� ��������� �����������
        function AppendParams(CEncoderParams $params) {
            $ret = new CEncoderParams($params);
            
            $ret->SetParam('rows', $this->components);
            $ret->SetParam('id', $this->GetId());
            $ret->Setparam('style', $this->GetFinalStyle());
            
            return $ret;
        }
        
        // ��������� ������ ������ � �����
        function PushEmptyRow() {
            $newRow = new CTableRowComponent();
            $newRow->SetParentComponent($this);
            
            $this->components[] = $newRow;
        }
        
        // ��������� � ����� ������ ������
        function PushCell(CTableCellComponent $cell) {
            if ( ! count($this->components))
                $this->PushEmptyRow();
            $this->components[count($this->components) - 1]->PushCell($cell);
        }
        
        // ���������� ���������� �����
        function GetRowsCount() {
            return $this->GetComponentsCount();
        }
        
        // �����������
        function __construct() {
            parent::__construct();
        }
    }
    
?>
