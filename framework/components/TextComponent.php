<?php
    // :TODO: классы компонента (функция AppendParams)
    
    require_once('BaseComponent.php');
    
    // интерфейс текста - очев.
    interface IText {
        // возвращает и устанавливает текст объекта
        function GetText();
        function SetText($text);
    }
    
    // Компонент "текст". Аналог Html'овского <span>
    class CTextComponent extends CBaseComponent {
        // *****************************
        // Текстовое значение компонента
        protected $text = '';
        
        function GetText() {
            return $this->text;
        }
        function SetText($text) {
            $this->text = $text;
        }
        
        // ***********************
        
        // Компонент должен знать, что он - Текст
        function GetComponentType() {
            return 'Text';
        }
        
        // Дополнение параметров для кодирования
        protected function AppendParams(CEncoderParams $params) {
            $ret = new CEncoderParams($params);
            
            $ret->SetParam('text', $this->GetText());
            $ret->SetParam('style', $this->GetFinalStyle());
            $ret->SetParam('id', $this->GetId());
            $ret->SetParam('class', ''); // ************************************************************************** STUB!!
            return $ret;
        }
        
        // ***********
        // Конструктор
        function __construct($text = '') {
            $this->text = $text;
        }
    }
?>
