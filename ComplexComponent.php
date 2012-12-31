<?php

    // :TODO: Переписать функцию GetStyleForComponent
    
    require_once('BaseComponent.php');
    
    // Комплексный компонент - компонент, внутри которого могут содержаться другие компоненты.
    // :NOTE: Один компонент может несколько раз входить в один комплексный компонент, но только в один.
    interface IComplexComponent {
        // Возвращает количество компонентов
        function GetComponentsCount();
        
        // *****
        // Стили
        //     Этот компонент может содержать в себе стили наподобие css. Это объекты CStyle, лежащие в массиве с индексами,
        //     соответствующими названиями идентификаторов/классов (не поддерживает конструкции вида 'table td')
        function GetNamedStyle($styleName);
        function SetNamedStyle($styleName, CStyle $style);
        
        // возвращает полный стиль компонента, учитывая предков
        function GetStyleForComponent(CBaseComponent $comp);
    }
    
    // Простая абстрактная реализация комплексного компонента
    abstract class CComplexComponent extends CBaseComponent implements IComplexComponent, IteratorAggregate {
        // **********
        // Компоненты
        protected $components = array();
        
        // Количество компонентов внутри данного
        function GetComponentsCount() {
            return count($this->components);
        }
        
        // Возвращает итератор на начало массива с компонентами
        function GetIterator() {
            return new ArrayIterator($this->components);
        }
        
        // *****
        // Стили
        //     Этот компонент может содержать в себе стили наподобие css. Это объекты CStyle, лежащие в массиве с индексами,
        //     соответствующими названиями идентификаторов/классов (не поддерживает конструкции вида 'table td')
        protected $namedStyles = array();
        
        function GetNamedStyle($styleName) {
            return $this->namedStyles[$styleName];
        }
        function SetNamedStyle($styleName, CStyle $style) {
            $this->namedStyles[$styleName] = $style;
        }
        
        // *******************************************************************
        // возвращает полный стиль компонента, учитывая предков
        //                                                      ПЕРЕПИСАТЬ
        function GetStyleForComponent(CBaseComponent $comp) {
            if ( ! is_null($this->parent))
                $ret = new CStyle($this->parent->GetStyleForComponent($comp));
            else
                $ret = new CStyle();
            
            // тип компонента
            if ( ! is_null($this->namedStyles[$comp->GetComponentType()]))
                $ret = $this->namedStyles[$comp->GetComponentType()]->Merge($ret);
            
            // id компонента
            if ( ! is_null($this->namedStyles[$comp->getId()]))
                $ret = $this->namedStyles[$comp->GetId()]->Merge($ret);
            
            // сам компонент
            $ret = $this->GetFinalStyle()->Merge($ret);
            
            return $ret;
        }
        
        // Конструктор
        function __construct() {
            parent::__construct();
        }
    }
?>
