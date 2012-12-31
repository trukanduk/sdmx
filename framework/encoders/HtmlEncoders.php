<?php
    
    require_once('Encoder.php');
    
    // Класс реализующий запихивание компонента в html-формат. Вернее, общий предок
    // Необходимо переопределить 
    abstract class CHtmlEncoder implements IEncoder {
        // Возвращает экземпляр объекта.
        static function Instance() { return null; }
        
        // Кодирует параметры в html
        function Encode(CEncoderParams $params) {}
        
        static function StyleToString(CStyle $style) {
            $ret = '';
            foreach ($style as $attr => $value)
                $ret .= "{$attr}:{$value};";
            
            return $ret;
        }
    }
    
    // Encoder для CTextComponent
    //     за описаниями функций см. класс CHtmlEncoder
    class CHtmlTextEncoder extends CHtmlEncoder {
        // Экземпляр объекта
        protected static $instance = null;
        
        function Encode(CEncoderParams $params) {
            $styleString =  self::StyleToString($params->GetParam('style', new CStyle()));
            return "<span style='{$styleString}' id='{$params->GetParam('id', '')}' class='{$params->GetParam('class', '')}'>{$params->GetParam('text', '')}</span>";
        }
        
        static function Instance() {
            if (is_null(self::$instance))
                self::$instance = new CHtmlTextEncoder();
            
            return self::$instance;
        }
    }
    
    // Кодирует ячейку таблицы
    class CHtmlTableCellEncoder extends CHtmlEncoder {
        // Экземпляр объекта
        protected static $instance = null;
        
        function Encode(CEncoderParams $params) {
            $styleString = self::StyleToString($params->GetParam('style', new CStyle()));
            return "<td id='{$params->GetParam('id', '')}' class='{$params->getParam('class', '')}' style='{$styleString}' rowspan='{$params->GetParam('rowspan', '1')}' colspan='{$params->GetParam('colspan', '1')}'>{$params->getParam('text', '')}</td>\n";
        }
        
        static function Instance() {
            if (is_null(self::$instance))
                self::$instance = new CHtmlTableCellEncoder();
            return self::$instance;
        }
    }
    
    // Строка таблицы
    class CHtmlTableRowEncoder extends CHtmlEncoder {
        // Экземпляр объекта
        protected static $instance = null;
        
        function Encode(CEncoderParams $params) {
            $styleString = self::StyleToString($params->GetParam('style', new CStyle()));
            $ret = "<tr id='{$params->GetParam('id', '')}' style='{$styleString}' class='{$params->GetParam('class', '')}'>";
            $cells = $params->GetParam('cells');
            $cellParams = new CEncoderParams();
            for ($i = 0; $i < count($cells); $i += $cells[$i]->GetColspan())
                $ret .= $cells[$i]->Encode('Html', $cellParams);
            $ret .= '</td>';
            return $ret;
        }
        
        static function Instance() {
            if (is_null(self::$instance))
                self::$instance = new CHtmlTableRowEncoder();
            return self::$instance;
        }
    }
    
    // Вся таблица
    class CHtmlTableEncoder extends CHtmlEncoder {
        // Экземпляр класса
        protected static $instance = null;
        
        function Encode(CEncoderParams $params) {
            $styleString = self::StyleToString($params->GetParam('style', new CStyle()));
            $ret = "<table id='{$params->GetParam('id', '')}' class='{$params->GetParam('class', '')}' style='{$styleString}'>";
            $rows = $params->GetParam('rows');
            $rowsParams = new CEncoderParams();
            for ($i = 0; $i < count($rows); $i++)
                $ret .= $rows[$i]->Encode('Html', $rowsParams);
                
            $ret .= '</table>';
            return $ret;
        }
        
        static function Instance() {
            if (is_null(self::$instance))
                self::$instance = new CHtmlTableEncoder();
            return self::$instance;
        }
    }
?>
