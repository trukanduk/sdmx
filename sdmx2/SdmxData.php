<?php
	/**
	 * Описание класса <var>SdmxData</var>
	 * 
	 * Файл содержит описание класса <var>SdmxData</var> - класса по работе с sdmx-файлами
	 *
	 * @author Илья Уваренков <trukanduk@gmail.com>
	 * @package sdmx
	 * @version 0.2
	 */

	require_once('SdmxAxis.php');
	/**
	 * Sdmx-объект
	 *
	 * Основной класс по работе с Sdmx-файлами.
	 *
	 * @todo размерности
	 * @todo dataSet
	 *
	 * @version 0.2
	 * @package sdmx
	 */
	class SdmxData {
		/**
		 * Объект <var>SimpleXMLElement</var> данного файла
		 *
		 * @var SimpleXMLElement
		 */
		protected $rawXml;

		/**
		 * Получение исходного xml
		 *
		 * Возвращает исходный xml файла в виде объекта <var>SimpleXMLElement</var>
		 *
		 * @return SimpleXMLElement исходный xml файла
		 */
		function GetRawXml() {
			return $this->rawXml;
		}

		/**
		 * Установка исходного xml
		 *
		 * @param SimpleXMLElement $xml новый объект
		 * @return SdmxData объект-хозяин метода
		 */
		protected function SetRawXml(SimpleXMLElement $xml) {
			$this->rawXml = $xml;
			return $this;
		}

		/**
		 * Заголовки файла
		 *
		 * @var SimpleXMLElement Объект, соответствующий тегу &lt;header>
		 */
		protected $headers;

		/**
		 * Получение заголовков
		 *
		 * @return SimpleXMLElement Объект с заголовками файла
		 */
		function GetHeaders() {
			return $this->headers;
		}

		/**
		 * Установка заголовков
		 *
		 * @param SimpleXMLElement $headers новый объект с заголовками
		 * @return SdmxData объект-хозяин метода
		 */
		function SetHeaders(SimpleXMLElement $headers) {
			$this->headers = $headers;
			return $this;
		}

		/**
		 * Получение заголовка
		 *
		 * Парсит данный объект и вынимает из него заголовки
		 *
		 * @param SimpleXMLElement $xml исходный объект
		 * @return SdmxData объект-хозяин метода
		 */
		protected function ParseHeaders(SimpleXMLElement $xml) {
			return $this->SetHeaders($xml);
		}

		/**
		 * Описание файла
		 *
		 * @var SimpleXMLElement Объект, соответствующий тегу &lt;Description>
		 */
		protected $description;

		/**
		 * Получение описания
		 *
		 * @return SimpleXMLElement Объект с описанием файла
		 */
		function GetDescription() {
			return $this->description;
		}

		/**
		 * Установка описания
		 *
		 * @param SimpleXMLElement $description новый объект с описанием
		 * @return SdmxData объект-хозяин метода
		 */
		function SetDescription(SimpleXMLElement $description) {
			$this->description = $description;
			return $this;
		}

		/**
		 * Получение описания
		 *
		 * Достаёт описание из данного объекта
		 * @param SimpleXMLElement $xml объект с описанием
		 * @return SdmxData объект-хозяин метода
		 */
		protected function ParseDescription(SimpleXMLElement $xml) {
			return $this->GetDescription($xml);
		}

		/**
		 * Массив осей
		 *
		 * В массиве хранятся все оси файла (т.е. аттрибуты и размерности)
		 * Массив имеет формат <var>['&lt;id оси>' => &lt;SdmxAxis>]</var>
		 * @var SdmxAxis[]
		 */
		protected $axes = array();

		/**
		 * Получение итератора на начало списка осей
		 *
		 * @return ArrayIterator итератор на начало массива осей
		 */
		function GetAxesIterator() {
			return new ArrayIterator($this->axes);
		}

		/**
		 * Добавление оси
		 *
		 * @param SdmxAxis $axis обавляемая ось
		 * @return SdmxData объект-хозяин метода
		 */
		function AddAxis(SdmxAxis $axis) {
			$this->axes[$axis->GetId()] = $axis;
			return $this;
		}

		/**
		 * Получение оси
		 *
		 * @param string $id идентификатор оси
		 * @param mixed $default возвращается в случае отсутствия оси
		 * @return mixed искомая ось (т.е. <var>SdmxAxis</var>) или <var>$default</var> в случае её отсутствия
		 */
		function GetAxis($id, $default = false) {
			if (isset($this->axes[$id]))
				return $this->axes[$id];
			else
				return $default;
		}

		/**
		 * Добавление значения аттрибута
		 *
		 * Метод добавляет к оси <strong>аттрибута</strong> значение + создаёт её, если таковой не было
		 *
		 * @param string $axisId идентификатор оси
		 * @param string $value значение
		 * @return SdmxData объект-хозяин метода
		 */
		protected function AddAttributeValue($axisId, $value) {
			if ($this->GetAxis($axisId, false) === false)
				$this->AddAxis(SdmxAxis::CreateAttributeAxis($axisId));
			$this->GetAxis($axisId)->AddValue($value, $value);
			return $this;
		}

		/**
		 * Добавление размерности
		 *
		 * Метод добавляет новый dimension и обрабатывает его codelist (т.е. список его значений)
		 * @param SimpleXMLElement $xml объект со всем файлом
		 * @param SimpleXMLElement $dim объект, соответствующий dimension'у
		 * @return SdmxData объект-хозяин метода
		 */
		protected function AddDimension(SimpleXMLElement $xml, SimpleXMLElement $dim) {
			// создадим новый axis, если его не было (в случае dimension'ов это трудно)
			$axis = SdmxAxis::CreateDimensionAxis(strval($dim['value']), strval($dim->Name));

			// найдём нужный codelist
			foreach ($xml->CodeLists->children('structure', true)->CodeList as $child) {
				if ($child->GetName() != 'CodeList')
					continue;

				if (strval($child->attributes()->id) == $dim['value']) {
					$codelist = $child;
					break;
				}
			}

			// если не нашли, то вообще говоря нужно лезть в стандартные, но это потом.
			// ************************************************************************************ STUB
			if ( ! isset($codelist)) {
				echo "Codelist {$dim['value']} wasn't found!\n";
				return $this;
			}

			// добавим все значения
			foreach ($codelist->Code as $val) {
				$axis->AddValue(strval($val->attributes()->value), strval($val->Description));
			}

			$this->AddAxis($axis);
			return $this;
		}

		/**
		 * Обработка всех размерностей
		 *
		 * Метод достаёт из данного файла все размерности (в т.ч. заполняет их списки значений)
		 *
		 * @param SimpleXMLElemet $xml весь файл
		 * @return SdmxData объект-хозяин метода
		 */
		protected function ParseDimensions(SimpleXMLElement $xml) {
			foreach ($xml->Description->Indicator->Dimensions->Dimension as $dim) {
				$this->AddDimension($xml, $dim);
			}
			return $this;
		}

		protected function ParseAttributes(SimpleXMLElement $xml) {
			foreach ($xml->DataSet->children('generic', true)->Series as $cell) {
				$this->AddAttributeValue('time', strval($cell->Obs->Time));
				foreach ($cell->Attributes->Value as $attr)
					$this->AddAttributeValue(strval($attr->attributes()->concept), strval($attr->attributes()->value));
			}
		}

		/**
		 * Конструктор
		 * 
		 * @param string $filename Имя файла
		 */
		function __construct($filename) {
			// Обнаружим файл
			$xml = new SimpleXMLElement($filename, 0, true);
			$this->SetRawXml($xml);

			// выделим из него заголовки и описание
			$this->ParseHeaders($xml->Header)
			     ->ParseDescription($xml->Description);

			// Теперь - выделим все оси.
			$this->ParseDimensions($xml)
			     ->ParseAttributes($xml);
		}

		function __DebugPrintAxes() {
			echo "Axes:<br>\n";
			foreach ($this->GetAxesIterator() as $axis) {
				$axis->__DebugPrint();
			}
			echo "<br>\n";
		}
	}

	$sdmx = new SdmxData('sdmx.1.xml');
	$sdmx->__DebugPrintAxes();
?>
