<?php
	/**
	 * В файле содержится описание класса <var>SdmxData</var> -- основного класса для работы с Sdmx-файлами
	 *
	 * @author Илья Уваренков <truanduk@gmail.com>
	 * @version 0.3
	 * @package sdmx
	 */

	require_once('XmlParseUtils.php');
	require_once('SdmxCodelist.php');
	require_once('SdmxDimension.php');
	require_once('SdmxDataPoint.php');

	/**
	 * Sdmx-файл
	 *
	 * Объект, соответствующий распарсенному sdmx-файлу. 
	 * Надо отметить, что объект повсюду константен, т.е. нет возможности перезаписать файл или что-то изменить
	 * Состоит по сути из пяти частей: 
	 * <ol>
	 * <li> заголовки (список аттрибутов внутри тега &lt;header>)
	 * <li> списки значений размерностей (codelist'ы)
	 * <li> описания (как и заголовки, список аттрибутов)
	 * <li> размерности (dimension'ы)
	 * <li> данные (массив DataPoint'ов)
	 * </ol>
	 *
	 * @todo только один индикатор!
	 * @todo косяк с периодизацией и аллокацией
	 * @todo запросы
	 * 
	 * @package sdmx
	 * @version 0.3
	 */
	class SdmxData {
		/**
		 * Заголовки
		 *
		 * Массив заголовков файла в виде <var>['&lt;имя>' => '&lt;значение>']</var>. В случае тега <var>Sender</var> имя будет выглядеть как <var>'sender::name'</var>
		 * Все ключи приводятся к нижнему регистру.
		 *
		 * @var string[]
		 */
		protected $header = array();

		/**
		 * Получение аттрибута заголовка
		 *
		 * Возвращает значение аттрибута заголовка с именем <var>$attr</var>. В случае отсутствия вернётся <var>$default</var>
		 * Имя аттрибута приводится к нижнему регистру
		 *
		 * @param string $attr имя аттрибута. Приводится к нижнему регистру (т.е. 'key' и 'KEY' -- одинаковые ключи)
		 * @param mixed $default значение, которое вернётся в случае отсутствия аттрибута с именем <var>$attr</var>
		 * @return mixed значение аттрибута заголовка (т.е. <var>string</var>) или <var>$default</var> в случае отсутствия аттрибута заголовка
		 */
		function GetHeaderAttr($attr, $default = false) { 
			if (isset($this->header[strtolower($attr)]))
				return $this->header[strtolower($attr)]; 
			else
				return $default;
		}

		/**
		 * Установка аттрибута заголовка
		 *
		 * Устанавливает аттрибут заголовка <var>$attr</var>. Имя заголовка (<var>$attr</var>) не зависит от регистра (т.е. 'Key', 'KEY' и 'key' -- один и тот же заголовок)
		 *
		 * @param string $attr имя аттрибута (не зависит от регистра)
		 * @param string $val новое значение аттрибута
		 * @return SdmxData сам объект
		 */
		protected function SetHeaderAttr($attr, $val) {
			$this->header[strtolower($attr)] = $val;
			return $this;
		}

		/**
		 * Получение итератора на начало массива заголовков
		 *
		 * @return ArrayIterator итератор на начало массива заголовков
		 */
		function GetHeaderIterator() {
			return new ArrayIterator($this->header);
		}

		/**
		 * Анализ заголовков файла
		 *
		 * Парсит массив <var>XmlDataArray</var>. В том числе перемещает внутренний указатель на элемент сразу после окончания заголовков.
		 * 
		 * @param XmlDataArray $arr уже расперсенный sdmx-файл, внутренний указатель которого указывает на элемент перед началом заголовков (или на первом теге)
		 * @return SdmxData сам объект
		 */
		protected function ParseHeader(XmlDataArray $arr) {
			// найдём начало header'ов
			$arr->Skip('HEADER', 'open');
			$arr->SkipCdata();

			// пока мы не наткнулись на закрытие header'а
			while ($arr->Curr('tag') != 'HEADER' || $arr->Curr('type') != 'close') {
				// установим всех, кого знаем
				if ($arr->Curr('tag') == 'ID')
					$this->SetHeaderAttr('id', $arr->Curr('value'));
				elseif ($arr->Curr('tag') == 'TEST')
					$this->SetheaderAttr('test', $arr->Curr('value'));
				elseif ($arr->Curr('tag') == 'TRUNCATED')
					$this->SetHeaderAttr('truncated', $arr->Curr('value'));
				elseif ($arr->Curr('tag') == 'PREPARED')
					$this->SetHeaderAttr('prepared', $arr->Curr('value'));
				elseif ($arr->Curr('tag') == 'EXTRACTED')
					$this->SetHeaderAttr('extracted', $arr->Curr('value'));
				elseif ($arr->Curr('tag') == 'SENDER') {
					// сложный тег надо обработать отдельно
					$this->SetHeaderAttr('sender', $arr->Curr()->GetAttrValue('ID'));
					$arr->Next();
					$arr->SkipCdata();
					while ($arr->Curr('tag') != 'SENDER' || $arr->Curr('type') != 'close') {
						if ($arr->Curr('tag') == 'NAME')
							$this->SetHeaderAttr("sender::name::{$arr->Curr()->GetAttrValue('XML:LANG')}", $arr->Curr('value'));
						elseif ($arr->Curr('tag') == 'CONTACT') {
							$arr->Next();
							$arr->SkipCdata();

							while ($arr->Curr('tag') != 'CONTACT') {
								if ($arr->Curr('tag') == 'URI')
									$this->SetheaderAttr('sender::contact::URI', $arr->Curr('value'));
								else {
									echo 'UNKNOWN TAG! ';
									var_dump($arr->Curr());
									if ($this->Curr('type') == 'open')
										$this->SkipTo($arr->Curr('tag'), 'close');
								}
								$arr->Next();
								$arr->SkipCdata();
							}
						} else {
							echo 'UNKNOWN TAG! ';
							var_dump($arr->Curr());
							if ($this->Curr('type') == 'open')
								$this->SkipTo($arr->Curr('tag'), 'close');
						}

						$arr->Next();
						$arr->SkipCdata();
					}
					$arr->SkipTo('SENDER', 'close');
				} elseif ($arr->Curr('tag') == 'DATASETAGENCY')
					$this->SetHeaderAttr('dataSetAgency', $arr->Curr('value'));
				elseif ($arr->Curr('tag') == 'DATASETID')
					$this->SetHeaderAttr('dataSetId', $arr->Curr('value'));
				else {
					echo 'UNKNOWN TAG! ';
					var_dump($arr->Curr());
					echo "<br>\n";
					if ($arr->Curr('type') == 'open')
						$arr->SkipTo($arr->Curr('tag'), 'close');
				}
				$arr->Next();
				$arr->SkipCdata();

				return $this;
			}
		}

		/**
		 * Массив списков значений размерностей
		 *
		 * Массив <var>SdmxCodelist</var>'ов в форме <var>['&lt;имя списка>' => new SdmxCodelist()]</var>
		 * ключи в массиве хранятся в нижнем регистре (вне зависимости от того, какой у них там на самом деле регистр)
		 *
		 * @var SdmxCodelist[]
		 */
		protected $codelists = array();

		/**
		 * Получение списка значений рахмерностей
		 *
		 * @param string $name имя списка (не зависит от регистра)
		 * @param mixed $default значение, которое вернётся в случае, если нужный список не найдён
		 * @return mixed список (<var>SdmxCodelist</var>) или <var>$default</var> в случае неудачи
		 */
		function GetCodelist($name, $default = false) {
			if (isset($this->codelists[strtolower($name)]))
				return $this->codelists[strtolower($name)];
			else
				return $default;
		}

		/**
		 * Установка списка значений
		 *
		 * @param string $name имя списка (не зависит от регистра)
		 * @param SdmxCodelist $val добавляемый список
		 * @return SdmxData сам объект
		 */
		protected function SetCodelist($name, SdmxCodelist $val) {
			$this->codelists[strtolower($name)] = $val;
			return $this;
		}

		/**
		 * Добавление списка значений
		 *
		 * Имя списка в массиве не зависит от регистра!
		 *
		 * @param SdmxCodelist $codelist добавляемый список
		 * @return SdmxData сам объект
		 */
		protected function AddCodelist(SdmxCodelist $codelist) {
			$this->codelists[strtolower($codelist->GetName())] = $codelist;
			return $this;
		}

		/**
		 * Получение итератора на массив списков значений
		 *
		 * @return ArrayIterator итератор на начало массива списков значений
		 */
		function GetCodelistsIterator() {
			return new ArrayIterator($this->codelists);
		}

		/**
		 * Анализ списков значений файла
		 *
		 * Парсит массив <var>XmlDataArray</var>. В том числе перемещает внутренний указатель на элемент сразу после окончания списка значений.
		 * 
		 * @param XmlDataArray $arr уже расперсенный sdmx-файл, внутренний указатель которого указывает на элемент перед началом списков значений (или на первом теге)
		 * @return SdmxData сам объект
		 */
		protected function ParseCodelists(XmlDataArray $arr) {
			$arr->SkipTo('CODELISTS');

			// если codelists не пустой
			if ($arr->Curr('type') == 'open') {
				$arr->Next();

				// пока мы оказались на codelist'е, заставим его распарситься
				while ($arr->Curr('tag') == 'STRUCTURE:CODELIST') {
					$codelist = SdmxCodelist::Parse($arr);
					$this->AddCodelist($codelist);
					$arr->SkipCdata();
				}

				$arr->Skip('CODELISTS', 'close');
			} else {
				$arr->Next();
			}

			return $this;
		}
		/**
		 * Описание файла.
		 *
		 * По сути ещё один header. Массив хранится в виде <var>['&lt;имя описания>' => '&lt;значение>']</var>, причём имя регистронезависимо (всегда приводится к нижнему регистру)
		 * Вообще, в теге <var>description</var> может быть несколько тегов <var>indicator</var>, однако здесь считается, что он может быть только один.
		 * Вложенные описания выглядят как 
		 * @var string[]
		 */
		protected $description = array();

		/** 
		 * Получение аттрибута описания
		 *
		 * Возвращает какой-то конкретный аттрибут из описания. В случае несуществования аттрибута возвращает <var>$default</var>.
		 * Имя аттрибута не зависит от регистра
		 *
		 * @param string $attr имя аттрибута (не зависит от регистра)
		 * @param mixed $default значение, которое будет возвращено в случае отсутствия требуемого аттрибута.
		 * @return mixed значение аттрибута или <var>$default</var> в случае его отсутствия
		 */
		function GetDescriptionAttr($attr, $default = false) {
			if (isset($this->description[strtolower($attr)]))
				return $this->description[strtolower($attr)];
			else 
				return $default;
		}

		/**
		 * Получение итератора на начало массива описания
		 *
		 * Надо отметить, что все имена в массиве (ключи массива) хранятся в нижнем регистре
		 *
		 * @return ArrayIterator итератор на начало массива с описанием файла
		 */
		function GetDescriptionIterator() { return new ArrayIterator($this->description); }

		/**
		 * Установка аттрибута описания
		 * 
		 * Устанавливает начение аттрибута описания. Имя регистронезависимо и будет приведено к нижнем регистру
		 *
		 * @param string $attr имя аттрибута (регистронезависимо)
		 * @param mixed $val значение аттрибута
		 * @return SdmxData сам объект, из которого вызван метод.
		 */
		protected function SetDescriptionAttr($attr, $val) {
			$this->description[strtolower($attr)] = $val;
			return $this;
		}

		/**
		 * Массив размерностей таблицы
		 *
		 * Массив вида <var>[new SdmxDimension()]</var>
		 *
		 * @var SdmxDimension[]
		 */
		protected $dimensions = array();

		/**
		 * Получение размерности
		 *
		 * Возвращает размерность (<var>SdmxDimension</var>) по индексу или <var>$default</var> в случае его отсутствия
		 *
		 * @param int $ind индекс желаемой размерности
		 * @param mixed $default значение, коорое будет возвращено в случае отсутствия размерности индексом <var>$ind</var>
		 * @return SdmxDimension желаемая размерность
		 */
		function GetDimension($ind, $default = false) {
			if (isset($this->dimensions[$ind]))
				return $this->dimensions[$ind];
			else
				return $defalut;
		}

		/**
		 * Получение итератора на массив размерности
		 *
		 * Возвращает итератор на начало массива размерностей файла
		 *
		 * @return ArrayIterator Итератор на массив размерностей
		 */
		function GetDimensionsIterator() { return new ArrayIterator($this->dimensions); }

		/**
		 * Добавление размерности
		 *
		 * Добавляет размерность в объект.
		 *
		 * @param SdmxDimension
		 */
		protected function AddDimension(SdmxDimension $dim) {
			$this->dimensions[] = $dim;
			return $this;
		}

		/**
		 * Анализ описания файла
		 *
		 * Парсит массив <var>XmlDataArray</var>. В том числе перемещает внутренний указатель на элемент сразу после окончания описания.
		 * 
		 * @param XmlDataArray $arr уже расперсенный sdmx-файл, внутренний указатель которого указывает на элемент перед началом описания (или на первом теге)
		 * @return SdmxData сам объект
		 */
		protected function ParseDescription(XmlDataArray $arr) {
			$this->SetDescriptionAttr('id', $arr->SkipTo('DESCRIPTION', 'open')->GetAttrValue('ID'));
			$this->SetDescriptionAttr('indicatorId', $arr->SkipTo('INDICATOR', 'open')->GetAttrValue('ID'));
			$arr->Next();
			$arr->SkipCdata();
			while ($arr->Curr('tag') != 'INDICATOR') {
				if ($arr->Curr('tag') == 'UNITS') {
					$this->SetDescriptionAttr('unit', $arr->SkipTo('UNIT', 'complete')->GetAttrValue('VALUE'));
					$arr->Skip('UNITS', 'close');
				} else if ($arr->Curr('tag') == 'PERIODICITIES') {
					$this->SetDescriptionAttr('periodicity', $arr->SkipTo('PERIODICITY', 'complete')->GetAttrValue('VALUE'));
					$this->SetDescriptionAttr('periodicity::releases', $arr->Curr()->GetAttrValue('RELEASES'));
					$this->SetDescriptionAttr('periodicity::next-releases', $arr->Curr()->GetAttrValue('NEXT-RELEASES'));
					$arr->Skip('PERIODICITIES', 'close');
				} else if ($arr->Curr('tag') == 'DATARANGE') {
					$this->SetDescriptionAttr('datarange::start', $arr->Curr()->GetAttrValue('START'));
					$this->SetDescriptionAttr('datarange::end', $arr->Curr()->GetAttrValue('END'));
					$arr->Skip('DATARANGE');
				} else if ($arr->Curr('tag') == 'LASTUPDATE') {
					$this->SetDescriptionAttr('lastupdate', $arr->Curr()->GetAttrValue('VALUE'));
					$arr->Skip('LASTUPDATE');
				} else if ($arr->Curr('tag') == 'DIMENSIONS') {
					if ($arr->Curr('type') == 'open') {
						$arr->Next();
						$arr->SkipCdata();
						while ($arr->Curr('tag') == 'DIMENSION') {
							$this->AddDimension(SdmxDimension::Parse($arr, $this->codelists));
							$arr->SkipCdata();
						}
						$arr->Skip('DIMENSIONS', 'close');
					} else 
						$arr->Next();
				} else if ($arr->Curr('tag') == 'METHODOLOGY') {
					$this->SetDescriptionAttr('methodology', $arr->Curr()->GetAttrValue('VALUE'));
					$arr->Next();
				} else if ($arr->Curr('tag') == 'ORGANIZATION') {
					$this->SetDescriptionAttr('organization', $arr->Curr()->GetAttrValue('VALUE'));
					$arr->Next();
				} else if ($arr->Curr('tag') == 'DEPARTAMENT') {
					$this->SetDescriptionAttr('departament', $arr->Curr()->GetAttrValue('VALUE'));
				} else if ($arr->Curr('tag') == 'DEPARTMENT') {
					$this->SetDescriptionAttr('department', $arr->Curr()->GetAttrValue('VALUE'));
					$arr->Next();
				} else if ($arr->Curr('tag') == 'ALLOCATIONS') {
					// **********************************************************************************************************8 STUB
					$arr->Skip('ALLOCATIONS', 'close');
				} else if ($arr->Curr('tag') == 'RESPONSIBLE') {
					$arr->Next();
					$arr->SkipCdata();
					while ($arr->Curr('tag') != 'RESPONSIBLE') {
						if ($arr->Curr('tag') == 'NAME')
							$this->SetDescriptionAttr('responsible::name', $arr->Curr('value'));
						elseif ($arr->Curr('tag') == 'COMMENTS') 
							$this->SetDescriptionAttr('responsible::comments', $arr->Curr('value'));
						$arr->Next();
						$arr->SkipCdata();
					}
					$arr->Skip('RESPONSIBLE', 'close');
				} else {
					echo 'UNKNOWN TAG! ';
					var_dump($arr->Curr());
					echo "<br>\n";
					if ($arr->Curr('type') == 'complete')
						$arr->Next();
					else 
						$arr->Skip($arr->Curr('tag'), 'close');
				}
				$arr->SkipCdata();
			}

			$arr->Skip('DESCRIPTION', 'close');

			return $this;
		}

		/**
		 * Массив с данными
		 *
		 * Просто массив <var>SdmxDataPoint</var>
		 * @var SdmxDataPoint[]
		 */
		protected $data = array();

		/**
		 * Получение ячейки
		 *
		 * Функция возвращает ячейку с нужным индексом или <var>$default</var>, если его не было
		 *
		 * @param int $ind индекс желаемой точки
		 * @param mixed $default 
		 */
		function GetDataPoint($ind, $default = false) {
			if (isset($this->data[$ind]))
				return $this->data[$ind];
			else
				return $default;
		}

		/**
		 * Получение итератора на начало массива данных
		 *
		 * @return ArrayIterator итератор на начало массива данных
		 */
		function GetDataPointsIterator() { return new ArrayIterator($this->data); }

		/**
		 * добавление точки в массив
		 *
		 * @param SdmxDataPoint $point добавляемая точка
		 * @return SdmxData оъект, у которого был вызван метод
		 */
		protected function AddDataPoint(SdmxDataPoint $point) {
			$this->data[] = $point;
			return $this;
		}

		/**
		 * Анализ данных файла
		 *
		 * Парсит массив <var>XmlDataArray</var>. В том числе перемещает внутренний указатель на элемент сразу после окончания данных.
		 * 
		 * @param XmlDataArray $arr уже расперсенный sdmx-файл, внутренний указатель которого указывает на элемент перед началом данных (или на первом теге)
		 * @return void
		 */
		protected function ParseData(XmlDataArray $arr) {
			$arr->Skip('DATASET', 'open');
			$arr->SkipCdata();

			while ($arr->Curr('tag') == 'GENERIC:SERIES') {
				$point = SdmxDataPoint::Parse($arr, $this->dimensions);
				$this->AddDataPoint($point);
				$arr->SkipCdata();
			}

			$arr->Skip('DATASET', 'close');
		}

		/**
		 * Конструктор
		 *
		 * Создаёт и заполняет объект данными из файла <var>$filename</var>
		 *
		 * @param string $filename имя файла, который надо проанализировать
		 */
		function __construct($filename) {
			$text = file_get_contents($filename);
			$p = xml_parser_create();
			$arr = new XmlDataArray($p, $text);
			$this->ParseHeader($arr)
			     ->ParseCodelists($arr)
			     ->ParseDescription($arr)
			     ->ParseData($arr);
		}

		/**
		 * Дебаговывод заголовков
		 * @return SdmxData сам объект
		 */
		function _DebugPrintHeader() {
			echo '<br>HEADER:<br>';
			foreach ($this->header as $key => $value) {
				echo "{$key}: ";
				var_dump($value);
				echo "<br>\n";
			}
			return $this;
		}

		/**
		 * Дебаговывод списков значений
		 * @return SdmxData сам объект
		 */
		function _DebugPrintCodelists() {
			echo '<br>CODELISTS:<br>';
			foreach ($this->codelists as $codelist) {
				var_dump($codelist);
				echo "<br>\n";
			}
			return $this;
		}

		/**
		 * Дебаговывод описаний
		 * @return сам объект
		 */
		function _DebugPrintDescription() {
			echo '<br>DESCRIPTION:<br>';
			foreach ($this->description as $key => $value) {
				echo "{$key}: ";
				var_dump($value);
				echo "<br>\n";
			}
			return $this;
		}

		/**
		 * Дебаговывод размерностей
		 * @return сам объект
		 */
		function _DebugPrintDimensions() {
			echo '<br>DIMENSIONS:<br>';
			foreach ($this->dimensions as $name => $dim) {
				echo "{$name}: ";
				var_dump($dim);
				echo "<br>\n";
			}
			return $this;
		}

		/**
		 * Дебаговывод данных
		 * @return сам объект
		 */
		function _DebugPrintData() {
			echo '<br>DATASET:<br>';
			foreach ($this->data as $datum) {
				var_dump($datum);
				echo "<br>\n";
			}
			return $this;
		}

		/**
		 * Дебаговывод всего
		 * @return сам объект
		 */
		function _DebugPrintAll() {
			return $this->_DebugPrintHeader()
    			        ->_DebugPrintCodelists()
    			        ->_DebugPrintDescription()
    			        ->_DebugPrintDimensions()
    			        ->_DebugPrintData();
		}
	}
?>
