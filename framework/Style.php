<?php
    
    // интерфейс стиля. По сути -- ассоциативный массив [аттрибут => значение]
    interface IStyle {
        // Возвращает, устанавливает и удаляет значение аттрибута. всё просто :)
        function GetAttr($attr);
        function SetAttr($attr, $value);
        function UnsetAttr($attr);
        
        // Возвращает новый стиль, образованный объединением текущего и данного.
        // Объект-хозяин имеет больший приоритет
        function Merge(CStyle $other);
    }
    
    // простой класс стиля, реализующий IStyle
    class CStyle implements IStyle, IteratorAggregate {
        // значения аттрибутов. массив [аттрибут => значение]
        protected $values = array();
        
        // Возвращает значение аргумента. $attr - строка
        function GetAttr($attr) {
            return $this->values[$attr];
        }
        
        // если $value == null, то вызов функции будет аналогичен функции UnsetAttr()
        function SetAttr($attr, $value) {
            if (is_null($value))
                unset($this->values[$attr]);
            else
                $this->values[$attr] = $value;
        }
        
        // удаляет значение аттрибута
        function UnsetAttr($attr) {
            unset($this->values[$attr]);
        }
        
        // Получим итератор на начало массива
        function GetIterator() {
            return new ArrayIterator($this->values);
        }
        
        // Возвращает новый стиль, образованный объединением текущего и данного.
        // Объект-хозяин имеет больший приоритет
        function Merge(CStyle $other) {
            $ret = new CStyle($other);
            foreach ($this->values as $attr => $value)
                $ret->SetAttr($attr, $value);
            
            return $ret;
        }
        
        // ***********
        // Конструктор
        function __construct($other = null) {
            if (is_array($other) || $other instanceof CStyle)
                foreach ($other as $attr => $value)
                    $this->values[$attr] = $value;
        }
    }
    
    // Компонент, имеющий некий стиль (см. описание к CStyle) + он имеет id и класс
    interface IStyled {
        
        // Возращает объект IStyle для этого компонента
        function GetStyle();
        function SetStyle(IStyle $newStyle);
        
        // Возвращает объект стиля объекта с учётом наследования стилей
        function GetFinalStyle();
        
        // Возвращает значение отдельного аттрибута стиля, устанавливает его значение или удаляет аттрибут
        //     Только у этого компонента, т.е. при изменении аттрибута родительские значения не изменятся,
        //     при удалении аттрибута будет наследоваться родительское значение
        function GetStyleAttr($attr);
        function SetStyleAttr($attr, $value);
        function UnsetStyleAttr($attr);
        
        // Возвращает значение аттрибута с учётом наследования стилей
        function GetFinalStyleAttr($attr);
        
        /*
        // Класс компонента
        function GetClass();
        function SetClass($newClass);
        */
        
        // идентификатор
        function GetId();
        function SetId($newId);
    }
?>
