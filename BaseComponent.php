<?php
    
    /*
        :TODO:
        Класс компонента (их может быть много баблабла)
    */
    
    require_once('Encoder.php');
    require_once('Style.php');
    //require_once('ComplexComponent.php');
    
    // Базовый интерфейс для всех компонентов -- имеет id и класс + родительский компонент
    interface IBaseComponent {
        // Родительский компонент (см. CComplexComponent)
        // :NOTE: метод только устанавливает значение, т.е. он должен вызываться из родителя
        function GetParentComponent();
        function SetParentComponent(IComplexComponent $newParent);
        // Удаляет родителя
        function RemoveParentComponent();
        
        // Возвращает тип компонента (например, для CTextComponent это 'Text')
        function GetComponentType();
    }
    
    // базовый компонент. Для наследования других объектов необходимо определить:
    //     function GetComponentType()
    //     protected function AppendParams(IEncoderParams $params)
    // Например, для текста: GetComponentType() { return 'Text'; }
    abstract class CBaseComponent implements IBaseComponent, IEncodable, IStyled {
        // ***********
        // тип объекта
        /* abstract */ function GetComponentType() {}
        
        // **********************
        // Родительский компонент
        protected $parentComponent = null;
        
        // возвращает родительский компонент
        function GetParentComponent() {
            return $this->parentComponent;
        }
        
        // устанавливает родителя для данного компонента (родитель ни о чём догадываться не будет!)
        // Для добавления этого компонента в ComplexComponent необходимо использовать функции из родительского компонента
        function SetParentComponent(IComplexComponent $newParent) {
            $this->parentComponent = $newParent;
        }
        
        function RemoveParentComponent() {
            $this->parent = null;
        }
        
        /*
        // *******************
        // класс объекта (CSS)
        protected $classname = [];
        */
        
        // *********************
        // Идентификатор объекта. Не обязательно уникален
        protected $id;
        
        // Возвращает идентификатор объекта
        function GetId() {
            return $this->id;
        }
        
        // Устанавливает в качестве идентификатора данное значение
        function SetId($newId) {
            $this->id = $newId;
        }
        
        // *********
        // Encoder'ы
        // Encoder'а каждого типа будет иметься только одна штука (либо ни одного),
        //      и храниться он будет в <EncoderTypename>, получать его (или создавать) - метод Instance()
        
        // Дополняет имеющийся объект $params своими параметрами или создаёт новый (если передан null)
        protected function AppendParams(CEncoderParams $params) {
        }
        
        // Вызывает правильный Encoder с параметрами
        function Encode($prefix, $params = null) {
            if (is_null($params))
                $params = new CEncoderParams();
            elseif (is_array($params))
                $params = new CEncoderParams($params);
            elseif ( ! $params instanceof CEncoderParams)
                throw new Exception('Argument must be array, CEncoderParams or null!');
            
            return CEncoderFinder::Instance($prefix, $this->GetComponentType())->Encode($this->AppendParams($params));
        }
        
        // **********************************
        // Стиль компонента (и сопутствующее)
        protected $style = null;
        
        // Возвращает объект style этого компонента (т.е. без учёта родительских стилей)
        function GetStyle() {
            return $this->style;
        }
        
        // Устанавливает стиль этого объекта. не влияет на стили родителей (хотя у этого стиля наибольший приоритет)
        function SetStyle(IStyle $newStyle) {
            $this->style = $newStyle;
        }
        
        // Возращает весь объект IStyle, описывающий стиль компонента (с учётом parent-компонентов и их стилей)
        function GetFinalStyle() {
            if (is_null($this->parentComponent) && is_null($this->style))
                return new CStyle();
            elseif ( ! is_null($this->parentComponent) && is_null($this->style))
                return $this->parentComponent->GetStyleForComponent($this);
            elseif (is_null($this->parentComponent) &&  ! is_null($this->style))
                return $this->style;
            else
                return $this->style->Merge($this->parentComponent->GetStyleForComponent($this));
        }
        
        // Возвращает значение аттрибута стиля объекта 
        function GetFinalStyleAttr($attr) {
            $this->GetFinalStyle->GetAttr($attr);
        }
        
        // Возвращает значение отдельного аттрибута стиля (именно этого компонента, не объединённого с родителями)
        function GetStyleAttr($attr) {
            return $this->style->GetAttr($attr);
        }
        
        // Устанавливает аттрибут стиля (не влияет на родительские стили)
        function SetStyleAttr($attr, $value) {
            $this->style->SetAttr($attr, $value);
        }
        
        // Удаляет аттрибут у стиля объекта (т.е. аттрибут будет наследоваться)
        function UnsetStyleAttr($attr) {
            $this->style->UnsetAttr($attr);
        }
        
        function __construct() {
            $this->style = new CStyle();
        }
    }
?>
