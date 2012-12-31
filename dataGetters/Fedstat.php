<?php
    // :TODO: исправить кос€к
    
    require_once('DataGetter.php');
    require_once('excel_reader2.php');
    
    // DataGetter дл€ получени€ и анализа данных с Fedstat'а (http://fedstat.ru)
    class CFedstatDataGetter implements IDataGetter {
        // ‘ункци€ получени€ дерева с таблицами.
        function GetTree() {
            /*
                 ќ—я  :(
            */
            return '';
        }
        
        static function ParseExelFile($filename) {
            if (file_exists($filename) == 0) {
                return null;
            } else {
                $ret_table = new CTableComponent();
                $reader = new Spreadsheet_Excel_Reader($filename, false, 'UTF-8');
                $mergedCells = [];
                for ($i = 3; $i <= $reader->rowcount(); ++$i) {
                    $ret_table->PushEmptyRow();
                    for ($j = 1; $j <= $reader->colcount() + 1; ++$j) {
                        if ( ! isset($mergedCells[$i][$j])) {
                            $cell = new CTableCellComponent($reader->val($i, $j), $reader->rowspan($i, $j), $reader->colspan($i, $j));
                            $ret_table->PushCell($cell);
                            for ($ispan = 0; $ispan < $reader->rowspan($i, $j); ++$ispan)
                                for ($jspan = 0; $jspan < $reader->colspan($i, j); ++$jspan)
                                    $mergedCells[$i + $ispan][$j + $jspan] = true;
                        }
                        unset($mergedCells[$i][$j]);
                    }   
                }
                
                self::FreeTmpFile($filename);
                
                return $ret_table;
            }
        }
        
        function SaveExelFile(&$file_content) {
            $fileId = 0;
            while (file_exists(".tmp/{$fileId}.xls"))
                ++$fileId;
            if ( ! file_exists('.tmp'))
                mkdir('.tmp');
            
            $file = fopen("{$fileId}.xls", 'w');
            fwrite($file, $file_content);
            fclose($file);
        }
        
        static function FreeTmpFile($filename) {
            if (file_exists($filename))
                unlink($filename);
            if (count(get_files_list('.tmp')) == 0) {
                rmdir('.tmp');
            }
        }
        
        
        function GetData(CDataGetterParams $params) {}
    }
?>
