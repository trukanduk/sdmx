<?php
    
    // Объект Encoder - записывалка компонента в файл. Будет иметься несколько
    //     реализаций для разных типов файлов и для разных компонентов.
    interface IEncoder {
        // единственный метод - закодировать объект с данными параметрами.
        // Параметры являются так же возвращаемым значением
        function Encode(CEncoderParams $params);
        
        // Возвращает экземпляр класса
        static function Instance();
    }
    
    // Просто ассоциативный массив, оформленный в форме класса
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
    
    // Класс, состоящий из единственной статической функции, возвращающей экземпляр объекта Encoder
    //     правильного вида (с правильным префиксом и для правильного типа компонента)
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
    
    // Компонент, который умеет записывать себя в какой-то файл. (см. описание Encoder'ов)
    interface IEncodable {
        // Кодирует компонент. за подробностями - см. описание Encoder'ов
        function Encode($prefix, $params);
    }
    
?>
