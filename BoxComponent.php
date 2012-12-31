<?php
    require_once('ComplexComponent.php');
    
    // Box - ����������� ��������� � ������������ ����������/�������� ���������. ������ <div> � html
    // :NOTE: ���� ��������� ����� ��������� ��� ������� � ���� ����������� ���������, �� ������ � ����.
    interface IBox {
        // ��������� ������ ��������� � Box � ������� $pos (���� -1, �� � �����), ���������� ������
        function InsertComponent(CBaseComponent $comp, $pos = -1);
        
        // ������� ��������� �� Box ($count ���� ��� ������, ���� �� ����� ������. ���� $count == -1 �� null, �� ������� ��� ����������)
        function EraseComponent(CBaseComponent $comp, $count = 1);
        
        // ���������� ������ ���������� (������ ���������) ��� -1, ���� �� ������
        function FindComponent(CBaseComponent $comp);
    }
    
    // ���������� IBox
    class CBoxComponent extends CBaseComponent implements IBox {
        // **************
        // CBaseComponent
        
        function GetComponentType() {
            return 'Box';
        }
        
        // ��������� ��������� Encoder'�
        function AppendParams(CEncoderParams $params) {
            $ret = new CEncoderParams($param);
            
            $ret->SetParam('style', $this->GetFinalStyle());
            $ret->SetParam('id', $this->GetId());
            $ret->SetParam('class', $this->GetClass());
            $ret->SetParam('components', $this->components);
            
            return $ret;
        }
        
        // ****************
        // IBox
        
        // ��������� ��������� � �������� �������, ���� $pos == -1, ��������� � �����
        function InsertComponent(CBaseComponent $comp, $pos = -1) {
            if ($pos == -1)
                $pos = count($this->components);
                
            for ($i = count($this->components); $i > $pos; --$i)
                $this->components[$i] = $this->components[$i - 1];
            
            $this->components[$pos] = $comp;
            $comp->SetParentComponent($this);
            
            return $pos;
        }
        
        // ������� ��������� �� Box ($count ���� ��� ������, ���� �� ����� ������. ���� $count == -1 �� null, �� ������� ��� ����������)
        function EraseComponent(CBaseComponent $comp, $count = 1) {
            $deletedCount = 0;
            for ($i = 0;$i < count($this->components) - $deletedCount; ++$i)
                if (($deletedCount < $count || is_null($count) || $count == -1) && $this->components[$i] === $comp)
                    ++$deletedCount;
                else
                    $this->components[$i - $deletedCount] = $this->components[$i];
            
            for ($i = 0; $i < $deletedCount; ++$i)
                unset($this->components[count($this->components) - 1]);
            
            return $deletedCount;
        }
        
        // ���������� ������ ����������
        function FindComponent(CBaseComponent $comp) {
            foreach ($this->components as $key => &$value)
                if ($value === $comp)
                    return $key;
        }
        
        // ***********
        // �����������
        function __construct() {
            parent::__construct();
        }
    }
?>
