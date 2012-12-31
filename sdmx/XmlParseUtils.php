<?php
	/**
	 * Файл содержит описания классов для парсинга xml-файлов (любых, не только sdmx).
	 *
	 * @author Илья Уваренков <trukanduk@gmail.com>
	 * @version 1.2
	 * @package sdmx\parseUtils
	 */

	/**
	 * Класс, описывающий один тег в xml-файле.
	 * 
	 * По сути - обин объект из выходного массива функции xml_parse_into_struct(..).
	 * Его нельзя изменить вне себя, только в конструкторе. Поэтому нет смысла копировать этот объект, чтобы куда-либо передать -- он всегда read-only
	 *
	 * @package sdmx\parseUtils
	 * @version 1.1
	 */
	class XmlTagInArray {
		/**
		 * объект (массив) из массива, возвращаемого функцией xml_struct_into_struct()
		 *
		 * Объект, оформленный в виде массива из возвращаемого массива xml_parse_into_struct(),
		 * имеет как правило структуру: <var>['tag'=>'&lt;ИМЯ ТЕГА>', 'value'=>'&lt;содержимое>', 'type'=>'open|close|complete|cdata' 'attributes'=>['&lt;АТТРИБУТ>'=>'&lt;значение>']]</var>.
		 *
		 * @var mixed[]
		 */
		protected $values = array();

		/**
		 * Свойство тега.
		 *
		 * Возвращает <var>$this->values[$name]</var> или <var>$default</var> в случае отсутствия
		 *
		 * @param string $name название свойства
		 * @param mixed $default значение по умолчанию, которое возвращается в случае отсутствия свойства. По умолчанию <var>false</var>.
		 * @return mixed значение свойства (<var>$this->values[$name]</var>) или <var>$default</var> в случае его отсутствия.
		 */
		function GetValue($name, $default = false) {
			if (isset($this->values[$name]))
				return $this->values[$name];
			else
				return $default;
		}

		/**
		 * Аттрибут тега
		 *
		 * Возвращает <var>$this->values['attributes'][$name]</var> или <var>$default</var> в случае отсутствия
		 *
		 * @param string $name название аттрибута
		 * @param mixed $default значение по умолчанию, которое возвращается в случае отсутствия аттрибута. По умолчанию false
		 * @return mixed значение аттрибута (<var>$this->values[$name]</var>) или <var>$default</var> в случае его отсутствия.
		 */
		function GetAttrValue($name, $default = false) {
			if (isset($this->values['attributes']) && isset($this->values['attributes'][$name]))
				return $this->values['attributes'][$name];
			else
				return $default;
		}

		/**
		 * Печатает объект.
		 *
		 * @return void
		 */
		function PrintObject() {
			var_dump($this->values);
			echo "<br>\n";
		}

		/**
		 * Конструктор
		 *
		 * Создаёт объект по данному массиву (пойдёт напрямую в <var>$this->values</var>)
		 *
		 * @param mixed[] $values собственно, значения тега без обработки
		 */
		function __construct($values) {
			$this->values = $values;
		}
	}

	/**
	 * Обработанный xml-файл
	 * 
	 * По сути своей этот объект - обертка для массива, возвращаемого функцией <var>xml_parse_into_struct()</var>
	 * Имеет свой внутренний указатель на текущий тег.
	 *
	 * @package sdmx\parseUtils
	 * @version 1.2
	 */
	class XmlDataArray {
		/**
		 * Массив с данными
		 * 
		 * @var XmlTagInArray[] массив с данными
		 */
		protected $data = array();

		/**
		 * Индекс текущего элемента
		 *
		 * @var int
		 */
		protected $index = 0;

		/**
		 * Текущий элемент
		 *
		 * Возвращает ссылку на текущий элемент. Если параметр <var>$attr</var> - строка, то возвращается значение свойства $attr текущего элемента
		 * 
		 * @param mixed $attr если <var>false</var> (по умолчанию), то возвращается весь текущий элемент, если строка, то свойство текущего элемента (или <var>$default</var>)
		 * @param mixed $default возвращается в случае отсутствия элемента или отсутствия свойства
		 * @return mixed Текущий элемент массива, значение свойства или <var>$default</var>, если его нет (свойства или самого объекта)
		 */
		function Curr($attr = false, $default = false) {
			if ( ! isset($this->data[$this->index]))
				return $default;
			elseif ($attr === false) 
				return $this->data[$this->index];
			else
				return $this->data[$this->index]->GetValue($attr, $default);
		}

		/**
		 * Перемещение указателя на следующий элемент
		 *
		 * перемещает указатель на следующий элемент (даже если его не существует) и возвращает его значение (или <var>$default</var>)
		 *
		 * @param mixed $default значение, возвращаемое в случае отсутствия элемента
		 * @return mixed Новый текущий элемент (<var>XmlTagInArray</var>) или <var>$default</var>, если следующего нет
		 */
		function Next($default = false) {
			$this->index++;
			return $this->Curr(false, $default);
		}

		/**
		 * Перемещение указателя на предыдущий элемент
		 *
		 * Перемещает указатель на предыдущий элемент (даже если его не существует) и возвращает его занчение (или </var>$default<var>)
		 *
		 * @param mixed $default значение, возвращаемое в случае отсутствия элемента
		 * @return mixed Новый текущий элемент (<var>XmlTagInArray</var>) или <var>$default</var>, если предыдущего нет
		 */
		function Prev($default = false) {
			$this->index--;
			return $this->Curr(false, $default);
		}

		/**
		 * Возврат указателя в начало
		 *
		 * @param mixed $default значение в случае пустого массива
		 * @return mixed Первый элемент массива (<var>XmlTagInArray</var>) или <var>$default</var>, если массив пустой
		 */
		function Reset($default = false) {
			$this->index = 0;
			return $this->Curr(false, $default);
		}

		/**
		 * Установка указателя на последний элемент
		 *
		 * @param mixed $default возвращаемое значение в случае пустого массива
		 * @return mixed Последний элемент (<var>XmlTagInArray</var>) или <var>$default</var>, если массив пустой
		 */
		function End($default) {
			$this->index = count($this->data) - 1;
			return $this->Curr(false, $default);
		}

		/**
		 * Печать объекта
		 *
		 * @return void
		 */
		function PrintObject() {
			foreach ($this->data as $datum) {
				$datum->PrintObject();
			}
			echo "<br>\n";
		}

		/**
		 * Пропуск всех cdata-тегов
		 *
		 * Функция пропускает все теги в массиве с типом cdata
		 *
		 * @param mixed $default значение, возращаемое в случае пропуска всего массива
		 * @return mixed Новый текущий элемент (<var>XmlTagInArray</var>) или <var>$default</var>, если пропущен весь массив
		 */
		function SkipCdata($default = false) {
			while ($this->Curr('type', $default) !== $default && $this->Curr('type') == 'cdata')
				$this->Next();
			return $this->Curr(false, $default);
		}

		/**
		 * Пропуск тегов до нужного
		 *
		 * Пропускает все теги до тега с именем <var>$name</var> и типом <var>$type</var> (если <var>$type</var> === <var>false</var>, то тип не учитывается),
		 * возвращает <var>$default</var>, если пропущен весь массив
		 *
		 * @param string $name имя тега, до которого надо всё пропустить
		 * @param mixed $type тип искомого тега, при <var>false</var> типы тегов игнорируются
		 * @param mixed $default возвращается в случае пропуска всего массива
		 * @return mixed искомый тег (</var>XmlTagInArray<var>) или <var>$default</var> в случае пропуска всего массива
		 */
		function SkipTo($name, $type = false, $default = false) {
			while ($this->Curr(false, $default) !== $default && ($this->Curr('tag') !== $name || ($type !== false && $this->Curr('type') != $type)))
				$this->Next();
			return $this->Curr(false, $default);
		}

		/**
		 * Пропуск тегов
		 *
		 * Пропускает все теги, включая тег с именем <var>$name</var> и типом <var>$type</var> (если <var>$type</var> установлен как <var>false</var>, он игнорируется)
		 * Если пропущен весь массив, вернётся <var>$default</var>
		 *
		 * @param string $name имя тега, который надо пропустить
		 * @param mixed $type тип тега, если установлен в <var>false</var>, игнорируется
		 * @param mixed $default значение, которое возвращается в случае, если пропущен весь массив
		 * @return mixed тег после тега с именем <var>$name</var> и типом <var>$type</var> (<var>XmlTagInArray</var>) или <var>$default</var>, если пропущен весь массив
		 */
		function Skip($name, $type = false, $default = false) {
			if ($this->SkipTo($name, $type, $default) !== $default)
				return $this->Next($default);
			else
				return $default;
		}

		/**
		 * Конструктор
		 *
		 * конструктор основан на функции <var>xml_parse_into_struct()</var> (оттуда схожесть параметров)
		 * 
		 * @param resourse $parser xml-парсер
		 * @param string $data текст, который надо обработать
		 */
		function __construct ($parser, $data) {
			xml_parse_into_struct($parser, $data, $tmp);
			foreach ($tmp as $value) {
				$this->data[] = new XmlTagInArray($value);
			}
			reset($this->data);
		}
	}
?>
