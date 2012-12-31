<?php
    require_once('BaseComponent.php');
    require_once('TextComponent.php');
    require_once('ComplexComponent.php');
    
    // Ячейка таблицы. В качестве содержимого - текст
    interface ITableCell {
        
        // Текст ячейки
        function GetText();
        function SetText($text);
        
        // Параматры Colspan и Rowspan
        function GetColspan();
        function SetColspan($collspan);
        
        function GetRowspan();
        function SetRowspan($rowspan);
    }   
    
    class CTableCellComponent extends CBaseComponent implements ITableCell {
        // ************
        // Текст ячейки
        protected $text = '';
        
        function GetText() {
            return $this->text;
        }
        
        function SetText($text) {
            $this->text = $text;
        }
        
        // *****************
        // Colspan и Rowspan
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
        // Это уже реализации от BaseComponent
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
        // Конструктор
        function __construct($text = '', $rowspan = 1, $colspan = 1) {
            parent::__construct();
            
            $this->text = $text;
            $this->rowspan = $rowspan;
            $this->colspan = $colspan;
        }
    }
    
    // Интерфейс для строки таблицы
    interface ITableRow {
        function PushCell(CTableCellComponent $cel);
        //function PopCell(); // А нужна ли такая функция? думаю, нет
        
        // только количество ячеек
        function GetCellsCount();
        
        // сумма colspan'ов всех ячеек
        function GetRowWidth();
    }
    
    class CTableRowComponent extends CComplexComponent implements ITableRow {
        // Добавляет новую ячейку
        function PushCell(CTableCellComponent $cell) {
            //$newCell = new CTableCellComponent($text, $rowspan, $colspan);
            $this->components[] = $cell;
            $cell->SetParentComponent($this);
            
        }
        
        // Количество ячеек
        function GetCellsCount() {
            return $this->GetComponentsCount();
        }
        
        // Ширина строки
        function GetRowWidth() {
            $ret = 0;
            foreach ($this->components as $cell)
                $ret += $cell->GetColspan();
            return $ret;
        }
        
        // Обновляет параметры
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
        
        // Конструктор
        function __construct() {
            parent::__construct();
        }
    }
    
    // интерфейс для таблиц
    interface ITable {
        // добавить пустую строку
        function PushEmptyRow();
        // добавить ячейку в последнюю строку
        function PushCell(CTableCellComponent $cell);
        
        // количество строк
        function GetRowsCount();
    }
    
    // Таблица
    class CTableComponent extends CComplexComponent implements ITable {
        // Тип компонента
        function GetComponentType() {
            return 'Table';
        }
        
        // Обновляет параметры кодирования
        function AppendParams(CEncoderParams $params) {
            $ret = new CEncoderParams($params);
            
            $ret->SetParam('rows', $this->components);
            $ret->SetParam('id', $this->GetId());
            $ret->Setparam('style', $this->GetFinalStyle());
            
            return $ret;
        }
        
        // Добавляет пустую строку в конец
        function PushEmptyRow() {
            $newRow = new CTableRowComponent();
            $newRow->SetParentComponent($this);
            
            $this->components[] = $newRow;
        }
        
        // Добавляет в конец строки ячейку
        function PushCell(CTableCellComponent $cell) {
            if ( ! count($this->components))
                $this->PushEmptyRow();
            $this->components[count($this->components) - 1]->PushCell($cell);
        }
        
        // Возвращает количество строк
        function GetRowsCount() {
            return $this->GetComponentsCount();
        }
        
        // Конструктор
        function __construct() {
            parent::__construct();
        }
    }
    
?>
