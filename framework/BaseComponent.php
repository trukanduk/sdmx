<?php
    
    /*
        :TODO:
        ����� ���������� (�� ����� ���� ����� ��������)
    */
    
    require_once('Encoder.php');
    require_once('Style.php');
    //require_once('ComplexComponent.php');
    
    // ������� ��������� ��� ���� ����������� -- ����� id � ����� + ������������ ���������
    interface IBaseComponent {
        // ������������ ��������� (��. CComplexComponent)
        // :NOTE: ����� ������ ������������� ��������, �.�. �� ������ ���������� �� ��������
        function GetParentComponent();
        function SetParentComponent(IComplexComponent $newParent);
        // ������� ��������
        function RemoveParentComponent();
        
        // ���������� ��� ���������� (��������, ��� CTextComponent ��� 'Text')
        function GetComponentType();
    }
    
    // ������� ���������. ��� ������������ ������ �������� ���������� ����������:
    //     function GetComponentType()
    //     protected function AppendParams(IEncoderParams $params)
    // ��������, ��� ������: GetComponentType() { return 'Text'; }
    abstract class CBaseComponent implements IBaseComponent, IEncodable, IStyled {
        // ***********
        // ��� �������
        /* abstract */ function GetComponentType() {}
        
        // **********************
        // ������������ ���������
        protected $parentComponent = null;
        
        // ���������� ������������ ���������
        function GetParentComponent() {
            return $this->parentComponent;
        }
        
        // ������������� �������� ��� ������� ���������� (�������� �� � ��� ������������ �� �����!)
        // ��� ���������� ����� ���������� � ComplexComponent ���������� ������������ ������� �� ������������� ����������
        function SetParentComponent(IComplexComponent $newParent) {
            $this->parentComponent = $newParent;
        }
        
        function RemoveParentComponent() {
            $this->parent = null;
        }
        
        /*
        // *******************
        // ����� ������� (CSS)
        protected $classname = [];
        */
        
        // *********************
        // ������������� �������. �� ����������� ��������
        protected $id;
        
        // ���������� ������������� �������
        function GetId() {
            return $this->id;
        }
        
        // ������������� � �������� �������������� ������ ��������
        function SetId($newId) {
            $this->id = $newId;
        }
        
        // *********
        // Encoder'�
        // Encoder'� ������� ���� ����� ������� ������ ���� ����� (���� �� ������),
        //      � ��������� �� ����� � <EncoderTypename>, �������� ��� (��� ���������) - ����� Instance()
        
        // ��������� ��������� ������ $params ������ ����������� ��� ������ ����� (���� ������� null)
        protected function AppendParams(CEncoderParams $params) {
        }
        
        // �������� ���������� Encoder � �����������
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
        // ����� ���������� (� �������������)
        protected $style = null;
        
        // ���������� ������ style ����� ���������� (�.�. ��� ����� ������������ ������)
        function GetStyle() {
            return $this->style;
        }
        
        // ������������� ����� ����� �������. �� ������ �� ����� ��������� (���� � ����� ����� ���������� ���������)
        function SetStyle(IStyle $newStyle) {
            $this->style = $newStyle;
        }
        
        // ��������� ���� ������ IStyle, ����������� ����� ���������� (� ������ parent-����������� � �� ������)
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
        
        // ���������� �������� ��������� ����� ������� 
        function GetFinalStyleAttr($attr) {
            $this->GetFinalStyle->GetAttr($attr);
        }
        
        // ���������� �������� ���������� ��������� ����� (������ ����� ����������, �� ������������ � ����������)
        function GetStyleAttr($attr) {
            return $this->style->GetAttr($attr);
        }
        
        // ������������� �������� ����� (�� ������ �� ������������ �����)
        function SetStyleAttr($attr, $value) {
            $this->style->SetAttr($attr, $value);
        }
        
        // ������� �������� � ����� ������� (�.�. �������� ����� �������������)
        function UnsetStyleAttr($attr) {
            $this->style->UnsetAttr($attr);
        }
        
        function __construct() {
            $this->style = new CStyle();
        }
    }
?>
