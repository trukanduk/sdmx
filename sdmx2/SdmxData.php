<?php
	/**
	 * Описание класса <var>SdmxData</var>
	 * 
	 * Файл содержит описание класса <var>SdmxData</var> - класса по работе с sdmx-файлами
	 *
	 * @author Илья Уваренков <trukanduk@gmail.com>
	 * @package sdmx
	 * @version 1.1
	 */

	require_once('SdmxAxis.php');
	require_once('SdmxDataPoint.php');
	require_once('ISdmxDataSet.php');
	require_once('SdmxArrayDataSet.php');
	require_once('SdmxAxisLoader.php');

	/**
	 * Sdmx-объект
	 *
	 * Основной класс по работе с Sdmx-файлами.
	 *
	 * @version 1.1
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
			return $this->SetDescription($xml);
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
		 * @param string $savedAxesFile Путь до файла с сохранёнными осями (см. SdmxAxisLoader)
		 * @return SdmxData объект-хозяин метода
		 */
		protected function AddDimension(SimpleXMLElement $xml, SimpleXMLElement $dim, $savedAxesFile) {
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
				//echo "Codelist {$dim['value']} wasn't found!<br>\n";

				if ( ! SdmxAxisLoader::LoadAxisValues($axis, $savedAxesFile)) {
					echo "Размерность {$dim['value']} не найдена или не может быть проинициализирована!<br>\n";
				}
			} else {
				// добавим все значения
				foreach ($codelist->Code as $val) {
					$axis->AddValue(strval($val->attributes()->value), strval($val->Description));
				}
			}

			$this->AddAxis($axis);
			return $this;
		}

		/**
		 * Обработка всех размерностей
		 *
		 * Метод достаёт из данного файла все размерности (в т.ч. заполняет их списки значений)
		 *
		 * @param SimpleXMLElement $xml весь файл
		 * @param string $savedAxesFile Путь до файла с сохранёнными осями (см. SdmxAxisLoader)
		 * @return SdmxData объект-хозяин метода
		 */
		protected function ParseDimensions(SimpleXMLElement $xml, $savedAxesFile) {
			foreach ($xml->Description->Indicator->Dimensions->Dimension as $dim) {
				$this->AddDimension($xml, $dim, $savedAxesFile);
			}
			return $this;
		}

		/**
		 * Обработка всех аттрибутов
		 *
		 * Достаёт из точек их аттрибуты и загоняет их как ось
		 *
		 * @param SimpleXMLElement $xml весь файл
		 * @param string $savedAxesFile Файл с сохранёнными осями (см. SdmxAxisLoader)
		 * @return SdmxData объект-хозяин метода
		 */
		protected function ParseAttributes(SimpleXMLElement $xml, $savedAxesFile) {
			foreach ($xml->DataSet->children('generic', true)->Series as $cell) {
				$this->AddAttributeValue('Time', strval($cell->Obs->Time));
				if ( ! SdmxAxisLoader::LoadAxisName($this->GetAxis('Time'), $savedAxesFile))
					echo "Невозможно проинициализировать имя оси Time!<br>\n";

				foreach ($cell->Attributes->Value as $attr) {
					$this->AddAttributeValue(strval($attr->attributes()->concept), strval($attr->attributes()->value));
					if ( ! SdmxAxisLoader::LoadAxisName($this->GetAxis(strval($attr->attributes()->concept)), $savedAxesFile))
						echo "Невозможно проинициализировать имя оси {$attr->attributes()->concept}!<br>\n";
				}

			}
			return $this;
		}

		/**
		 * Множество точек
		 *
		 * @var ISdmxDataSet
		 */
		protected $dataSet;

		/**
		 * Получение множества точек
		 *
		 * @return ISdmxDataSet множество точек
		 */
		function GetDataSet() {
			return $this->dataSet;
		}

		/**
		 * Формирование DataSet'а
		 *
		 * Метод парсит файл и вынимает оттуда DataSet (оси должны быть проинициализированы)
		 *
		 * @param SimpleXMLElement $xml файл
		 * @param ISdmxDataSet $dataSet Пустое множество (ISdmxDataSet), которое будет использоваться в качестве множества в файле
		 * @return SdmxData объект-хозяин метода
		 */
		protected function InitDataSet(SimpleXMLElement $xml, ISdmxDataSet $dataSet, $filter) {
			// для начала перенесём dataSet
			$this->dataSet = $dataSet;
			$this->dataSet->Clear();

			// Установим фильтр
			if (is_a($filter, 'SdmxAxesSystemFilter'))
				$this->dataSet->SetFilter($filter);

			// заполним оси
			foreach ($this->GetAxesIterator() as $axis)
				$this->dataSet->AddAxis($axis);

			// Теперь точки
			foreach ($xml->DataSet->children('generic', true)->Series as $rawPoint)
				$this->ParseDataPoint($rawPoint);

			return $this;
		}

		/**
		 * Формирование ячейки таблицы
		 *
		 * Парсит данный кусок с табличкой и загоняет получившуюся точку в dataSet
		 * 
		 * @param SimpleXMLElement $rawPoint xml, соответствующий интересуемой ячейке
		 * @return SdmxData объект-хозяин метода
		 */
		protected function ParseDataPoint(SimpleXMLElement $rawPoint) {
			// создадаим точку, сразу заполним значение
			$point = new SdmxDataPoint(strval($rawPoint->Obs->ObsValue->attributes()->value));

			// найдём и загоним все оси
			foreach ($rawPoint->SeriesKey->Value as $dim) {
				$axis = $this->GetAxis(strval($dim->attributes()->concept));
				$point->AddCoordinate(new SdmxCoordinate($axis, strval($dim->attributes()->value)));
			}

			foreach ($rawPoint->Attributes->Value as $attr) {
				$axis = $this->GetAxis(strval($attr->attributes()->concept));
				$point->AddCoordinate(new SdmxCoordinate($axis, strval($attr->attributes()->value)));
			}

			$point->AddCoordinate(new SdmxCoordinate($this->GetAxis('Time'), strval($rawPoint->Obs->Time)));

			// Теперь те, которые не объявлены в точке (взять значения по умолчанию), например, OKSM
			foreach ($this->GetAxesIterator() as $axisId => $axis) {
				if ($point->GetCoordinate($axisId, false) === false) {
					$point->AddCoordinate(new SdmxCoordinate($axis, $axis->GetDefaultRawValue()));
				}
			}

			// добавим точку в множество
			$this->dataSet->AddPoint($point);

			return $this;
		}

		/**
		 * Конструктор
		 * 
		 * @param string $filename Имя файла
		 * @param string $savedAxesFile Путь к файлу с сохранёнными осями (см. SdmxAxisLoader)
		 * @param ISdmxDataSet 
		 */
		function __construct($filename, $savedAxesFile = '.saved_axes.xml', $filter = null) {
			// Обнаружим файл
			$xml = new SimpleXMLElement($filename, 0, true);
			
			if ( ! $xml)
				return false;

			$this->SetRawXml($xml);

			// выделим из него заголовки и описание
			$this->ParseHeaders($xml->Header)
			     ->ParseDescription($xml->Description);

			// Теперь - выделим все оси.
			$this->ParseDimensions($xml, $savedAxesFile)
			     ->ParseAttributes($xml, $savedAxesFile);

			// Собственно, массив с данными.
			$this->InitDataSet($xml, new SdmxArrayDataSet(), $filter);
		}

		function __DebugPrintAxes() {
			echo "Axes:<br>\n";
			foreach ($this->GetAxesIterator() as $axis) {
				$axis->__DebugPrint();
			}
			echo "<br>\n";
			return $this;
		}

		function __DebugPrintDataSet() {
			echo "DataSet: <br>\n";
			$this->dataSet->__DebugPrint(true);
		}

		function __DebugPrint() {
			$this->__DebugPrintAxes();
			echo "SDMX DATA: <br>\n";
			$this->dataSet->__DebugPrint(true);
		}
	}
	/*
	// Новый объект
	$sdmx = new SdmxData('files/sdmx.1.xml', '.saved_axes.xml', new SdmxArrayDataSet());
	$sdmx->__DebugPrintAxes();
	$coordinates = array('OKSM' => 'RUS', 'U.M.VID_UGLYA' => 'code_U.M.VID_UGLYA01', 'Time' => '2006', 'EI' => 'килотонна', 'PERIOD' => 0);
	var_dump($sdmx->GetDataSet()->GetPoint($coordinates));
	*/
?>
