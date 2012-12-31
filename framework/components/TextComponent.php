<?php
    // :TODO: ������ ���������� (������� AppendParams)
    
    require_once('BaseComponent.php');
    
    // ��������� ������ - ����.
    interface IText {
        // ���������� � ������������� ����� �������
        function GetText();
        function SetText($text);
    }
    
    // ��������� "�����". ������ Html'������� <span>
    class CTextComponent extends CBaseComponent {
        // *****************************
        // ��������� �������� ����������
        protected $text = '';
        
        function GetText() {
            return $this->text;
        }
        function SetText($text) {
            $this->text = $text;
        }
        
        // ***********************
        
        // ��������� ������ �����, ��� �� - �����
        function GetComponentType() {
            return 'Text';
        }
        
        // ���������� ���������� ��� �����������
        protected function AppendParams(CEncoderParams $params) {
            $ret = new CEncoderParams($params);
            
            $ret->SetParam('text', $this->GetText());
            $ret->SetParam('style', $this->GetFinalStyle());
            $ret->SetParam('id', $this->GetId());
            $ret->SetParam('class', ''); // ************************************************************************** STUB!!
            return $ret;
        }
        
        // ***********
        // �����������
        function __construct($text = '') {
            $this->text = $text;
        }
    }
?>
