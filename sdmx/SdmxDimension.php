<?php
	/**
	 * <var>SdmxDimension</var> -- одна из размерностей sdmx-таблицы
	 *
	 * @author Илья Уваренков <trukanduk@gmail.com>
	 * @version 1.1
	 * @package sdmx
	 */

	require_once('XmlParseUtils.php');
	require_once('SdmxCodelist.php');

	/**
	 * Размерность таблицы
	 *
	 * По сути своей - ссылка на Codelist c описанием (имени нет)
	 * Соответствует коду:
	 * <code>
	 * &lt;Dimension value="OKSM">
	 *     &lt;Name xml:lang="ru">ОКСМ&lt;/Name>
	 * &lt;/Dimension>
	 * </code>
	 *
	 * @package sdmx
	 * @version 1.1
	 */
	class SdmxDimension {
		/**
		 * Описание
		 *
		 * Описание размерности
		 * @var string
		 */
		protected $description = '';

		/**
		 * Получение описания
		 *
		 * @return string описание размерности
		 */
		function GetDescription() { return $this->description; }

		/**
		 * Установка описания
		 *
		 * @param string $description новое описание
		 * @return SdmxDimension сам объект
		 */
		protected function SetDescription($description) {
			$this->description = $description;
			return $this;
		}

		/**
		 * список значений
		 *
		 * <var>Codelist</var> -- список значений этой размерности. Может быть ссылкой на <var>SdmxCodelist</var> или строкой - именем требуемого списка
		 *
		 * @var mixed
		 */
		protected $codelist;

		/**
		 * Состояние списка значений
		 *
		 * Проверяет, является ли <var>$this->codelist</var> самим объектом или строкой с его именем
		 *
		 * @return bool <var>true</var> в случае, когда список -- список, а не строка с именем
		 */
		function HasCodelistLink() { return is_a($this->codelist, SdmxCodelist); }

		/**
		 * Получение списка значений
		 *
		 * Возвращает список значений (<var>SdmxCodelist</var>) или <var>null</var>, если в <var>$this->codelist</var> -- строка-имя списка
		 *
		 * @return mixed список значений (<var>SdmxCodelist</var>) или <var>null</var>, если в <var>$this->codelist</var> -- строка-имя списка
		 */
		function GetCodelist() { 
			if ($this->HasCodelistLink())
				return $this->codelist;
			else 
				return null;
		}

		/**
		 * Получение имени списка значений
		 *
		 * @return string имя списка значений
		 */
		function GetCodelistName() {
			if ($this->HasCodelistLink())
				return $this->codelist->getName();
			else
				return $this->codelist;
		}

		/**
		 * Установка <i>нужного</i> списка значений
		 *
		 * Функция нужна для того, чтобы установить правильный список значений. Если в <var>$this->codelist</var> хранится имя списка,
		 * то он будет найден в массиве и установлен. Предполагается, что массив такого же вида содержится в <var>SdmxData</var>
		 * Также происходит поиск в стандартных списках (см. <var>SdmxPredefinedCodelists</var>)
		 * Если <var>$this->codelist</var> и так было списком, то ничего не изменится (возвращено будет <var>true</var>)
		 *
		 * @param SdmxCodelist[] $codelists массив списков в следующей форме: <var>['&lt;имя списка>' => SdmxCodelist()]</var>
		 * @return bool <var>true</var>, если в результате в <var>$this->codelist</var> оказался объект SdmxCodelist, <var>false</var> при неудаче
		 */
		protected function SetCodelistFromAray($codelists) {
			if ($this->HasCodelistLink()) {
				// Если уже установлен
				return true;
			} elseif (isset($codelists[strtolower($this->codelist)])) {
				// Если список найден в переданном массиве
				$this->SetCodelist($codelists[strtolower($this->codelist)]);
				return true;
			} elseif (SdmxPredefinedCodelists::GetCodelist($this->codelist, false) !== false) {
				// Если список был найден в стандартных списках
				$this->SetCodelist(SdmxPredefinedCodelists::GetCodelist($this->codelist));
				return true;
			} else {
				// неуспех :(
				return false;
			}
		}

		/**
		 * Установка списка значений
		 *
		 * @param mixed $codelist новый список значений (<var>SdmxCodelist</var>) или его имя (<var>string</var>)
		 * @return SdmxDimension сам объект
		 */
		protected function SetCodelist($codelist) {
			$this->codelist = $codelist;
			return $this;
		}

		/**
		 * Конструктор
		 *
		 * Codelist может быть объектом, может быть просто строкой (тогда надо будет вызвать <var>$this->SetCodelistFromArray()</var>)
		 *
		 * @param mixed $codelist список значений (<var>SdmxCodelist</var>) или имя списка (<var>string</var>)
		 * @param string $description описание размерности
		 */
		function __construct($codelist, $description) {
			$this->SetDescription($description)
			     ->SetCodelist($codelist);
		}

		/**
		 * Создание элемента из XmlDataArray
		 *
		 * "Достаёт" элемент из объекта <var>XmlDataArray</var> и перемещает его внутренний указатель.
		 *
		 * @param XmlDataArray $arr объект с обработанным xml-файлом и курсором перед тегом (или на самом теге) с началом объекта 
		 * @param SdmxCodelist[] $codelists Массив списков значений в виде <var>['&lt;имя списка>' => new SdmxCodelist()]</var>. Предполагается, что такой массив содержится в <var>SdmxData</var>. Гарантируется, что он не будет изменён
		 * @return SdmxDimension новый экземпляр класса
		 */
		static function Parse(XmlDataArray $arr, $codelists) {
			// найдём начало и выделим имя списка значений
			$id = $arr->SkipTo('DIMENSION', 'open')->GetAttrValue('VALUE');

			// выделим имя размерности
			$desc = $arr->SkipTo('NAME')->GetValue('value');

			// ... и переместим указатель на тег после закрытия размерности.
			$arr->Skip('DIMENSION', 'close');

			// Создадим новый объект и заполним его список значений
			$ret = new SdmxDimension($id, $desc);
			if ( ! is_null($codelists))
				$ret->SetCodelistFromAray($codelists);
			else {
				// В первых версиях параметр $codelists был необязательным, на всякий случай добавлю дебаговывод
				echo 'SdmxDimension::Parse $codelists == null: DEPRECATED!<br>';
			}

			return $ret;
		}
	}
?>
