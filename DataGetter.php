<?php
    require_once('BaseComponent.php');
    
    // ��������� ��� DataGetter'��
    class CDataGetterParams implements IteratorAggregate{
        // ����������, ��������� ($param => $value)
        protected $params = [];
        
        // �����������, �������� $other, ���� ��� ������ ��� ������ CDataGetterParams
        function __construct($other = null) {
            if ( ! is_null($other) && ($other instanceof CDataGetterParams || is_array($other)))
                foreach ($other as $key => $value)
                    $this->params[$key] = $value;
        }
        
        // ���������� ����������� �������� ��� $default, ���� �� �� ������
        function GetParam($param, $default = null) {
            if (isset($this->params[$param]))
                return $this->params[$param];
            else
                return $default;
        }
        
        // ������������� �������� ���������
        function SetParam($param, $value) {
            $this->params[$param] = $value;
        }
        
        // ���������� �������� �� ������ ������� ���������� (��� foreach)
        function GetIterator() {
            return new ArrayIterator($this->params);
        }
    }
    
    // ��������� ��� IDataGetter. � ��������, �� ����� �� Encoder: ������������ �������������� �����
    //     GetData(), ����������� Encode(). ������ ���������� ����� ������ �� �������� - ��� ����� ��������� ��������� ����� ����������� ������
    interface IDataGetter {
        // ���������� ���������, ���������� � �������������� DataGetter'��.
        function GetData(CDataGetterParams $params);
    }
?>
