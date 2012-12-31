<?php
    
    // ������ Encoder - ����������� ���������� � ����. ����� ������� ���������
    //     ���������� ��� ������ ����� ������ � ��� ������ �����������.
    interface IEncoder {
        // ������������ ����� - ������������ ������ � ������� �����������.
        // ��������� �������� ��� �� ������������ ���������
        function Encode(CEncoderParams $params);
        
        // ���������� ��������� ������
        static function Instance();
    }
    
    // ������ ������������� ������, ����������� � ����� ������
    class CEncoderParams implements IteratorAggregate {
        protected $params = array();
        
        function GetParam($param, $default=null) {
            if (isset($this->params[$param]))
                return $this->params[$param];
            else
                return $default;
        }
        
        function SetParam($param, $value) {
            $this->params[$param] = $value;
        }
        
        function GetIterator() {
            return new ArrayIterator($this->params);
        }
        
        function __construct($other = null) {
            if (is_array($other) || $other instanceof CEncoderParams) {
                foreach ($other as $key => $value) {
                    $this->params[$key] = $value;
                }
            }
        }
    }
    
    // �����, ��������� �� ������������ ����������� �������, ������������ ��������� ������� Encoder
    //     ����������� ���� (� ���������� ��������� � ��� ����������� ���� ����������)
    abstract class CEncoderFinder {
        static function Instance($prefix, $typename) {
            if ( ! is_string($prefix) || ! is_string($typename))
                throw new Exception('Arguments must be strings!');
            
            $encoderName = "{$prefix}{$typename}Encoder";
            $classname = "C{$encoderName}";
            if ( ! class_exists($classname)) {
                $filename = "./encoders/{$prefix}Encoders.php";
                if (file_exists($filename))
                    require_once($filename);
            }
            
            if ( ! class_exists($classname))
                throw new Exception("Cannot find {$classname}!");
            else
                return $classname::Instance();
        }
    }
    
    // ���������, ������� ����� ���������� ���� � �����-�� ����. (��. �������� Encoder'��)
    interface IEncodable {
        // �������� ���������. �� ������������� - ��. �������� Encoder'��
        function Encode($prefix, $params);
    }
    
?>
