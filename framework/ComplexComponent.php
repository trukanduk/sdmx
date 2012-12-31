<?php

    // :TODO: ���������� ������� GetStyleForComponent
    
    require_once('BaseComponent.php');
    
    // ����������� ��������� - ���������, ������ �������� ����� ����������� ������ ����������.
    // :NOTE: ���� ��������� ����� ��������� ��� ������� � ���� ����������� ���������, �� ������ � ����.
    interface IComplexComponent {
        // ���������� ���������� �����������
        function GetComponentsCount();
        
        // *****
        // �����
        //     ���� ��������� ����� ��������� � ���� ����� ��������� css. ��� ������� CStyle, ������� � ������� � ���������,
        //     ���������������� ���������� ���������������/������� (�� ������������ ����������� ���� 'table td')
        function GetNamedStyle($styleName);
        function SetNamedStyle($styleName, CStyle $style);
        
        // ���������� ������ ����� ����������, �������� �������
        function GetStyleForComponent(CBaseComponent $comp);
    }
    
    // ������� ����������� ���������� ������������ ����������
    abstract class CComplexComponent extends CBaseComponent implements IComplexComponent, IteratorAggregate {
        // **********
        // ����������
        protected $components = array();
        
        // ���������� ����������� ������ �������
        function GetComponentsCount() {
            return count($this->components);
        }
        
        // ���������� �������� �� ������ ������� � ������������
        function GetIterator() {
            return new ArrayIterator($this->components);
        }
        
        // *****
        // �����
        //     ���� ��������� ����� ��������� � ���� ����� ��������� css. ��� ������� CStyle, ������� � ������� � ���������,
        //     ���������������� ���������� ���������������/������� (�� ������������ ����������� ���� 'table td')
        protected $namedStyles = array();
        
        function GetNamedStyle($styleName) {
            return $this->namedStyles[$styleName];
        }
        function SetNamedStyle($styleName, CStyle $style) {
            $this->namedStyles[$styleName] = $style;
        }
        
        // *******************************************************************
        // ���������� ������ ����� ����������, �������� �������
        //                                                      ����������
        function GetStyleForComponent(CBaseComponent $comp) {
            if ( ! is_null($this->parent))
                $ret = new CStyle($this->parent->GetStyleForComponent($comp));
            else
                $ret = new CStyle();
            
            // ��� ����������
            if ( ! is_null($this->namedStyles[$comp->GetComponentType()]))
                $ret = $this->namedStyles[$comp->GetComponentType()]->Merge($ret);
            
            // id ����������
            if ( ! is_null($this->namedStyles[$comp->getId()]))
                $ret = $this->namedStyles[$comp->GetId()]->Merge($ret);
            
            // ��� ���������
            $ret = $this->GetFinalStyle()->Merge($ret);
            
            return $ret;
        }
        
        // �����������
        function __construct() {
            parent::__construct();
        }
    }
?>
