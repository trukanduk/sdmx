<?php
    require_once('BaseComponent.php');
    
    // Параметры для DataGetter'ов
    class CDataGetterParams implements IteratorAggregate{
        // собственно, параметры ($param => $value)
        protected $params = [];
        
        // Конструктор, копирует $other, если это массив или другой CDataGetterParams
        function __construct($other = null) {
            if ( ! is_null($other) && ($other instanceof CDataGetterParams || is_array($other)))
                foreach ($other as $key => $value)
                    $this->params[$key] = $value;
        }
        
        // Возвращает необходимый параметр или $default, если он не найден
        function GetParam($param, $default = null) {
            if (isset($this->params[$param]))
                return $this->params[$param];
            else
                return $default;
        }
        
        // Устанавливает значение параметра
        function SetParam($param, $value) {
            $this->params[$param] = $value;
        }
        
        // Возвращает итератор на начало массива параметров (для foreach)
        function GetIterator() {
            return new ArrayIterator($this->params);
        }
    }
    
    // Интерфейс для IDataGetter. В принципе, он похож на Encoder: единственный конструктивный метод
    //     GetData(), аналогичный Encode(). Однако экземпляры этого класса не статичны - они умеют сохранять параметры после предыдущего вызова
    interface IDataGetter {
        // Возвращает компонент, полученный и сформированный DataGetter'ом.
        function GetData(CDataGetterParams $params);
    }
?>
