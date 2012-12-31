<?php
    require_once('ComplexComponent.php');
    
    // Box - комплексный компонент с возможностью добавления/удаления элементов. аналог <div> в html
    // :NOTE: Один компонент может несколько раз входить в один комплексный компонент, но только в один.
    interface IBox {
        // Вставляет данный компонент в Box в позицию $pos (если -1, то в конец), возвращает индекс
        function InsertComponent(CBaseComponent $comp, $pos = -1);
        
        // Удаляет компонент из Box ($count штук или меньше, если их всего меньше. если $count == -1 ли null, то удаляет все экземпляры)
        function EraseComponent(CBaseComponent $comp, $count = 1);
        
        // Возвращает индекс компонента (первое вхождение) или -1, если не найден
        function FindComponent(CBaseComponent $comp);
    }
    
    // Реализация IBox
    class CBoxComponent extends CBaseComponent implements IBox {
        // **************
        // CBaseComponent
        
        function GetComponentType() {
            return 'Box';
        }
        
        // Обновляет параметры Encoder'а
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
        
        // Добавляет компонент в заданную позицию, если $pos == -1, вставляет в конец
        function InsertComponent(CBaseComponent $comp, $pos = -1) {
            if ($pos == -1)
                $pos = count($this->components);
                
            for ($i = count($this->components); $i > $pos; --$i)
                $this->components[$i] = $this->components[$i - 1];
            
            $this->components[$pos] = $comp;
            $comp->SetParentComponent($this);
            
            return $pos;
        }
        
        // Удаляет компонент из Box ($count штук или меньше, если их всего меньше. если $count == -1 ли null, то удаляет все экземпляры)
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
        
        // Возвращает индекс компонента
        function FindComponent(CBaseComponent $comp) {
            foreach ($this->components as $key => &$value)
                if ($value === $comp)
                    return $key;
        }
        
        // ***********
        // конструктор
        function __construct() {
            parent::__construct();
        }
    }
?>
