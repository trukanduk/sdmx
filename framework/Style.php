<?php
    
    // ��������� �����. �� ���� -- ������������� ������ [�������� => ��������]
    interface IStyle {
        // ����������, ������������� � ������� �������� ���������. �� ������ :)
        function GetAttr($attr);
        function SetAttr($attr, $value);
        function UnsetAttr($attr);
        
        // ���������� ����� �����, ������������ ������������ �������� � �������.
        // ������-������ ����� ������� ���������
        function Merge(CStyle $other);
    }
    
    // ������� ����� �����, ����������� IStyle
    class CStyle implements IStyle, IteratorAggregate {
        // �������� ����������. ������ [�������� => ��������]
        protected $values = array();
        
        // ���������� �������� ���������. $attr - ������
        function GetAttr($attr) {
            return $this->values[$attr];
        }
        
        // ���� $value == null, �� ����� ������� ����� ���������� ������� UnsetAttr()
        function SetAttr($attr, $value) {
            if (is_null($value))
                unset($this->values[$attr]);
            else
                $this->values[$attr] = $value;
        }
        
        // ������� �������� ���������
        function UnsetAttr($attr) {
            unset($this->values[$attr]);
        }
        
        // ������� �������� �� ������ �������
        function GetIterator() {
            return new ArrayIterator($this->values);
        }
        
        // ���������� ����� �����, ������������ ������������ �������� � �������.
        // ������-������ ����� ������� ���������
        function Merge(CStyle $other) {
            $ret = new CStyle($other);
            foreach ($this->values as $attr => $value)
                $ret->SetAttr($attr, $value);
            
            return $ret;
        }
        
        // ***********
        // �����������
        function __construct($other = null) {
            if (is_array($other) || $other instanceof CStyle)
                foreach ($other as $attr => $value)
                    $this->values[$attr] = $value;
        }
    }
    
    // ���������, ������� ����� ����� (��. �������� � CStyle) + �� ����� id � �����
    interface IStyled {
        
        // ��������� ������ IStyle ��� ����� ����������
        function GetStyle();
        function SetStyle(IStyle $newStyle);
        
        // ���������� ������ ����� ������� � ������ ������������ ������
        function GetFinalStyle();
        
        // ���������� �������� ���������� ��������� �����, ������������� ��� �������� ��� ������� ��������
        //     ������ � ����� ����������, �.�. ��� ��������� ��������� ������������ �������� �� ���������,
        //     ��� �������� ��������� ����� ������������� ������������ ��������
        function GetStyleAttr($attr);
        function SetStyleAttr($attr, $value);
        function UnsetStyleAttr($attr);
        
        // ���������� �������� ��������� � ������ ������������ ������
        function GetFinalStyleAttr($attr);
        
        /*
        // ����� ����������
        function GetClass();
        function SetClass($newClass);
        */
        
        // �������������
        function GetId();
        function SetId($newId);
    }
?>
