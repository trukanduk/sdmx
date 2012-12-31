<?php
    
    require_once('Encoder.php');
    
    // ����� ����������� ����������� ���������� � html-������. ������, ����� ������
    // ���������� �������������� 
    abstract class CHtmlEncoder implements IEncoder {
        // ���������� ��������� �������.
        static function Instance() { return null; }
        
        // �������� ��������� � html
        function Encode(CEncoderParams $params) {}
        
        static function StyleToString(CStyle $style) {
            $ret = '';
            foreach ($style as $attr => $value)
                $ret .= "{$attr}:{$value};";
            
            return $ret;
        }
    }
    
    // Encoder ��� CTextComponent
    //     �� ���������� ������� ��. ����� CHtmlEncoder
    class CHtmlTextEncoder extends CHtmlEncoder {
        // ��������� �������
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
    
    // �������� ������ �������
    class CHtmlTableCellEncoder extends CHtmlEncoder {
        // ��������� �������
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
    
    // ������ �������
    class CHtmlTableRowEncoder extends CHtmlEncoder {
        // ��������� �������
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
    
    // ��� �������
    class CHtmlTableEncoder extends CHtmlEncoder {
        // ��������� ������
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
