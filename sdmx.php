<?php
	// один тег в XmlDataArray (см. ниже)
	class XmlTagInArray {
		// тот самый объект, оформленный в виде массива при xml_parse_into_struct()
		protected $values = array();

		// возвращает $arr[$name]
		function GetValue($name, $default = false) {
			if (isset($this->values[$name]))
				return $this->values[$name];
			else
				return $default;
		}

		// $arr['attributes'][$name]
		function GetAttrValue($name, $default = false) {
			if (isset($this->values['attributes']) && isset($this->values['attributes'][$name]))
				return $this->values['attributes'][$name];
			else
				return $default;
		}

		// печатает объект
		function PrintObject() {
			var_dump($this->values);
			echo "<br>\n";
		}

		function __construct($values) {
			$this->values = $values;
		}
	}

	// Класс, содержащий результат парсинга xml_parse_to_struct
	class XmlDataArray {
		// собственно, массив с данными
		protected $data = array();

		// текущий индекс
		protected $index = 0;

		// текущий элемент
		function Curr($attr = false, $default = false) {
			if ($attr === false)
				return $this->data[$this->index];
			else
				if ($this->data[$this->index] !== false && $this->data[$this->index]->GetValue($attr) !== false) 
					return $this->data[$this->index]->GetValue($attr);
				else
					return $default;
		}

		// следующий элемент (в т.ч. переместить указатель на него)
		function Next() {
			return $this->data[++$this->index];
		}

		// предыдущий (в т.ч. переместить на него указатель)
		function Prev() {
			return $this->data[--$this->index];
		}

		// первый элемент (указатель ставится на него)
		function Reset() {
			return $this->data[$this->index = 0];
		}

		// последний (указатель - устанавливается на него)
		function End() {
			return $this->data[$this->index = count($this->data) - 1];
		}

		// печатает объект
		function PrintObject() {
			foreach ($this->data as $datum) {
				$datum->PrintObject();
			}
			echo "<br>\n";
		}

		// пропустить все теги с type == 'cdata'
		function SkipCdata() {
			while ($this->Curr() !== false && $this->Curr('type') == 'cdata')
				$this->Next();
		}

		// пропустить всё до тега $name с типом $type (если type === false, то просто не учитывается)
		function SkipTo($name, $type = false) {
			while ($this->Curr() !== false && (($name !== false && $this->Curr('tag') != $name) || ($type !== false && $this->Curr('type') != $type)))
				$this->Next();
			return $this->Curr();
		}

		// пропустить всё до тега $name с типом $type + него самого (если кто-то из них false, то просто не учитывается)
		// возвращает следующий за ним элемент
		function Skip($name, $type = false) {
			$this->SkipTo($name, $type);
			return $this->Next();
		}

		// конструктор основан на функции xml_parse_into_struct (оттуда схожесть параметров)
		// первый параметр - парсер, второй - собственно, текст, корый надо распарсить
		function __construct ($parser, $data) {
			xml_parse_into_struct($parser, $data, $tmp);
			foreach ($tmp as $value) {
				$this->data[] = new XmlTagInArray($value);
			}
			reset($this->data);
		}
	}

	/*
	объект соответствует коду:

	<structure:Code value="NAME">
		<structure:Description>DESCRIPTION</structure:Description>
	</structure:Code> 
	*/
	class SdmxCodeListInstance {
		// имя константы (в xml-ке -- id)
		protected $name = '';
		function GetName() { return $this->name; }
		protected function SetName($name) {
			$this->name = $name;
			return $this;
		}

		// её описание (на человеческом языке)
		protected $description = '';
		function GetDescription() { return $this->description; }
		protected function SetDescription($description) {
			$this->description = $description;
			return $this;
		}

		// создаёт новую константу
		function __construct($name, $description) {
			$this->SetName($name)
			     ->SetDescription($description);
		}

		// парсит имеющийся массив, полученный из функции xml_parse_to_struct начиная с $index,
		// в т.ч. устанавливает $index на позицию после данных
		static function Parse(XmlDataArray $arr) {
			$name = $arr->SkipTo('STRUCTURE:CODE', 'open')->GetAttrValue('VALUE');
			$description = $arr->SkipTo('STRUCTURE:DESCRIPTION', 'complete')->GetValue('value');

			$arr->Skip('STRUCTURE:CODE', 'close');
			return new SdmxCodeListInstance($name, $description);
		}
	}

	/*
	соответствует коду:

	<structure:CodeList id="NAME">
		<structure:Name>VALUE</structure:Name>
		<structure:Code value="CONSTANT_NAME_0">
			<structure:Description>CONSTANT_DESCRIPTION_0</structure:Description>
		</structure:Code>
		<structure:Code value="CONSTANT_NAME_1">
			<structure:Description>CONSTANT_DESCRIPTION_1</structure:Description>
		</structure:Code>
	</structure:CodeList>
	*/
	class SdmxCodeList {
		// имя (CodeList::id)
		protected $name = '';
		function GetName() { return $this->name; }
		protected function SetName($name) {
			$this->name = $name;
			return $this;
		}

		// описание (название на человеческом языке)
		protected $description = '';
		function GetDescription() { return $this->description; }
		protected function SetDescription($description) {
			$this->description = $description;
			return $this;
		}

		// все константы этого codeList'а
		protected $instances = array();
		function GetInstance($name, $default = false) {
			if (isset($this->instances[$name]))
				return $this->instances[$name];
			else
				return $default;
		}
		protected function AddInstance(SdmxCodeListInstance $inst) {
			$this->instances[$inst->GetName()] = $inst;
			if ($this->defaultInstanceName === '')
				$this->defaultInstanceName = $inst->GetName();
			return $this;
		}
		// присваивает весь массив
		protected function SetInstanceArray($arr) {
			$this->instances = array();
			foreach ($arr as $key => $inst)
				$this->instances[$key] = $inst;
			return $this;
		}
		function GetInstancesIterator() {
			return new ArrayIterator($this->instances);
		}

		// Значение по умолчанию. Сделано для того, чтобы могли существовать предопределённые codelist'ы типа 'OKSM'
		// хранится _ключ_ instance'а по умолчанию
		protected $defaultInstanceName = '';
		function GetDefaultInstanceName() { return $this->defaultInstanceName; }
		// сам instance (в предыдущей вункции - лишь его имя)
		function GetDefaultInstance() { return $this->GetInstance($this->defaultInstanceName); }
		protected function SetDefaultInstanceName($defaultInstanceName) {
			$this->defaultInstanceName = $defaultInstanceName;
			return $this;
		}

		// создаёт новый объект
		// Если $defualtInstance равен null, по умолчанию будет выбран первая константа в массиве $instances
		function __construct($name, $description, $instances = array(), $defaultInstance = null) {
			$this->SetName($name)
			     ->SetDescription($description)
			     ->SetInstanceArray($instances);
			if ( ! is_null($defaultInstance))
				$this->SetDefaultInstanceName($defaultInstance);
			elseif (count($instances) > 0) {
				reset($instances);
				$this->SetDefaultInstanceName(key($instances));
			}
		}

		// возвращает объект SdmxCodeList из объекта XmlDataArray
		// в том числе изменяет указатель в $arr
		static function Parse(XmlDataArray $arr) {
			$name = $arr->SkipTo('STRUCTURE:CODELIST', 'open')->GetAttrValue('ID');
			$description = $arr->SkipTo('STRUCTURE:NAME')->GetValue('value');


			$ret = new SdmxCodeList($name, $description);

			$arr->SkipTo('STRUCTURE:CODE');
			while ($arr->Curr('tag') == 'STRUCTURE:CODE') {
				$ret->AddInstance(SdmxCodeListInstance::Parse($arr));
				$arr->SkipCdata();
			}

			$arr->Skip('STRUCTURE:CODELIST', 'close');
			return $ret;
		}
	}

	// некоторые codelists, которые предполагаются имеющимися (напр., OKSM)
	abstract class SdmxPredefinedCodelists {
		// собственно, массив с codelist'ами. пока только OKSM, да и тот только с одним значением
		static protected $codelists = array();
		static function GetCodelist($name, $default = false) {
			if ( ! isset(self::$codelists[$name])) {
				// по возможности создадим этот codelist
				if ($name == 'OKSM')
					self::$codelists['OKSM'] = new SdmxCodelist('OKSM',
			                                                    'Общероссийский классификатор стран мира',
		                                                        array('RUS' => 'Российская федерация'),
		                                                        'RUS');
			}
			if (isset(self::$codelists[$name]))
				return self::$codelists[$name];
			else
				return $default;
		}
		static function GetCodelistsIterator() { return new ArrayIterator(self::$codelists); }
	}

	// ВАЖНО! не находил и не понимаю, как может быть несколько periodicity, поэтому считаю, что он только один
	// Indicator->Periodicity + Indicator->DataRange + Indicator->LastUpdate
	class SdmxPeriodicity {
		// например, "месячная", см. periodicity::value
		protected $period = '';
		function GetPeriod() { return $this->period; }
		protected function SetPeriod($period) {
			$this->period = $period;
			return $this;
		}

		// когда обычно выходит или когда вышел этот файл, см. periodicity::releases
		protected $releases = '';
		function GetReleases() { return $this->releases; }
		protected function SetReleases($releases) {
			$this->releases = $releases;
			return $this;
		}

		// когда выйдет в следующий раз, см. periodicity::next-release
		protected $nextRelease = '';
		function GetNextRelease() { return $this->nextRelease; }
		protected function SetNextRelease($nextRelease) {
			$this->nextRelease = $nextRelease;
			return $this;
		}

		// с какого по какой год содержится тут инфа -- ['begin' => <начало>, 'end' => <конец>], см. DataRange
		protected $dataRange = array();
		function GetDataRange() { return $this->dataRange; }
		function GetDataRangeBegin() { return $this->dataRange['begin']; }
		function GetDataRangeEnd() { return $this->dataRange['end']; }
		protected function SetDataRange($begin, $end) {
			$this->dataRange = array('begin' => $begin, 'end' => $end);
			return $this;
		}
		protected function SetDataRangeBegin($begin) {
			$this->dataRange['begin'] = $begin;
			return $this;
		}
		protected function SetDataRangeEnd($end) {
			$this->dataRange['end'] = $end;
			return $this;
		}

		// точное дата/время последнего обновления. см. тег LastUpdate
		protected $lastUpdate = '';
		function GetLastUpdate() { return $this->lastUpdate; }
		protected function SetLastUpdate($lastUpdate) {
			$this->lastUpdate = $lastUpdate;
			return $this;
		}

		function __construct($period, $releases, $nextRelease, $dataRangeBegin, $dataRangeEnd, $lastUpdate) {
			$this->SetPeriod($period)
			     ->SetReleases($releases)
			     ->SetNextRelease($nextRelease)
			     ->SetDataRangeBegin($dataRandeBegin)
			     ->SetDataRangeEnd($dataRandeEnd)
			     ->SetLastUpdate($lastUpdate);
		}
	}

	// Dimension - ссылка на Codelist со своим именем (см. одноимённый тег)
	class SdmxDimension {
		// название на человеческом языке
		protected $description = '';
		function GetDescription() { return $this->description; }
		protected function SetDescription($description) {
			$this->description = $description;
			return $this;
		}

		// Ссылка на codelist или его название. для проверки надо использовать функцию HasCodelistLink()
		// Конструктор одинаково примет и строку и SdmxCodelist, в первом случае можно использовать функцию SetCodelistFromArray()
		protected $codelist;
		function HasCodelistLink() { return is_a($this->codelist, SdmxCodeList); }
		function GetCodelist() { 
			if ($this->HasCodelistLink())
				return $this->codelist;
			else 
				return false;
		}
		function GetCodelistId() {
			if ($this->HasCodelistLink())
				return $this->codelist->getName();
			else
				return $this->codelist;
		}
		// находит нужный codelist (если его id был правильо записан в $this->codelist) записывает его в $this->codelist
		// возвращает true в случае успеха, false при неудаче. НЕ ПЕРЕЗАПИСЫВАЕТ, ЕСЛИ УЖЕ УСТАНОВЛЕН
		function SetCodelistFromAray($codelists) {
			if ($this->HasCodelistLink())
				return true;
			elseif (isset($codelists[$this->codelist])) {
				$this->SetCodelist($codelists[$this->codelist]);
				return true;
			} elseif (SdmxPredefinedCodelists::GetCodelist($this->codelist, false) !== false) {
				$this->SetCodelist(SdmxPredefinedCodelists::GetCodelist($this->codelist));
				return true;
			} else
				return false;
			
		}
		protected function SetCodeList($codelist) {
			$this->codelist = $codelist;
			return $this;
		}

		// с description'ом всё понятно, а вот codelist может быть как строкой (именем codelist'а, тогда надо будет вызвать SetCodelistFromArray), так и 
		function __construct($codelist, $description) {
			$this->SetDescription($description)
			     ->SetCodelist($codelist);
		}

		static function Parse(XmlDataArray $arr, $codelists = null) {
			$id = $arr->SkipTo('DIMENSION', 'open')->GetAttrValue('VALUE');
			$desc = $arr->SkipTo('NAME')->GetValue('value');
			$arr->Skip('DIMENSION', 'close');

			$ret = new SdmxDimension($id, $desc);
			if ( ! is_null($codelists))
				$ret->SetCodelistFromAray($codelists);
			return $ret;
		}
	}
/*
	// Тег Indicator (Внутри Description)
	class SdmxIndicator {
		// очев.
		protected $id =  0;
		function GetId() { return $this->id; }
		protected function SetId($id) {
			$this->id = $id;
			return $this;
		}

		// имя индикатора == название таблицы
		protected $name = '';
		function GetName() { return $this->name; }
		protected function SetName($name) {
			$this->name = $name;
			return $this;
		}

		// Unit (единица измерения). полагаю, просто строка
		protected $unit = '';
		function GetUnit() { return $this->unit; }
		protected function SetUnit($unit) {
			$this->unit = $unit;
		}

		// Периодизация
		protected $period;
		function GetPeriod() { return $this->period; }
		protected function SetPeriod(SdmxPeriodicity $period) {
			$this->period = $period;
			return $this;
		}

		protected $dimensions = array();
		function GetDimension($ind) { return $this->dimensions[$ind]; }
		function GetDimersionsIterator() { return new ArrayIterator($this->dimensions); }
		function SetDimensionsCodelistLinks($coselists) {
			$ret = true;
			foreach ($this->dimensions as $dim)
				$ret &= $dim->SetCodelistFromAray($codelists);
			return $ret;
		}
		protected function AddDimension($dimension) {
			$this->dimensions[] = $dimension;
			return $this;
		}

		protected $methology = '';
		function GetMethology() { return $this->methology; }
		protected function SetMethology($methology) {
			$this->methology = $methology;
			return $this;
		}

		protected $organization = '';
		function GetOrganization() { return $this->organization; }
		protected function SetOrganization($org) {
			$this->organization = $org;
			return $this;
		}

		protected $departament = ''
	}*/

	class SdmxDataPoint {
		protected $dimensions = array();
		function GetDimensionsIterator() { return new ArrayIterator($this->dimensions); }
		function GetDimension($name, $default = false) {
			if (isset($this->dimensions[$name]))
				return $this->dimensions[$name];
			else
				return $default;
		}
		function FillDimensions($dimensions) {
			$ret = true;

			foreach ($dimensions as $dim) {
				if ( ! $dim->HasCodelistLink())
					$ret &= false;
				else {
					if (isset($this->dimensions[$dim->GetCodelist()->GetName()]))
						$this->dimensions[$dim->GetCodelist()->GetName()] = $dim->GetCodelist()->GetInstance($this->dimensions[$dim->GetCodelist()->GetName()]);
					else
						$this->dimensions[$dim->GetCodelist()->GetName()] = $dim->GetCodelist()->GetDefaultInstance();
				}
			}
			return ($this->dimsFilled = $ret);
		}
		protected $dimsFilled = false;
		function IsDimensionsFilled() {
			return $this->dimsFilled;
		}
		protected function SetDimension($name, $value) {
			$this->dimensions[$name] = $value;
			return $this;
		}

		protected $attributes = array();
		function GetAttributesIterator() { return new ArrayIterator($this->attributes); }
		function GetAttr($attr, $default) {
			if (isset($this->attributes[$attr]))
				return $this->attributes[$attr];
			else
				return $default;
		}
		protected function SetAttr($attr, $val) {
			$this->attributes[$attr] = $val;
			return $this;
		}

		protected $time = '';
		function GetTime() { return $this->time; }
		protected function SetTime($time) {
			$this->time = $time;
			return $this;
		}

		protected $value = '';
		function GetValue() { return $this->value; }
		protected function SetValue($value) {
			$this->value = $value;
			return $this;
		}

		// dimensions - только строки. fill'ть надо потом отдельно.
		function __construct($value, $time, $dimensions = array(), $attrs = array()) {
			$this->SetValue($value)
			     ->SetTime($time);
			foreach ($dimensions as $id => $dim)
				$this->SetDimension($id, $dim);
			foreach ($attrs as $id => $attr)
				$this->SetAttr($id, $attr);
		}

		function Parse(XmlDataArray $arr, $dimensions = null) {

			$ret = new SdmxDataPoint('', '', array(), array());

			$arr->SkipTo('GENERIC:SERIES');
			$arr->SkipTo('GENERIC:SERIESKEY');
			if ($arr->Curr('type') == 'open') {
				$arr->Next();
				$arr->SkipCdata();
				while ($arr->Curr('tag') == 'GENERIC:VALUE') {
					$ret->SetDimension($arr->Curr()->GetAttrValue('CONCEPT'), $arr->Curr()->GetAttrValue('VALUE'));
					//$dims[$arr->Curr()->GetAttrValue('CONCEPT')] = $arr->Curr()->GetAttrValue('VALUE');
					$arr->Next();
					$arr->SkipCdata();
				}
				$arr->Skip('GENERIC:SERIESKEY', 'close');
			} else
				$arr->Next();

			$arr->SkipTo('GENERIC:ATTRIBUTES');
			if ($arr->Curr('type') == 'open') {
				$arr->Next();
				$arr->SkipCData();
				while ($arr->Curr('tag') == 'GENERIC:VALUE') { 
					$ret->SetAttr($arr->Curr()->GetAttrValue('CONCEPT'), $arr->Curr()->GetAttrValue('VALUE'));
					//$attrs[$arr->Curr()->GetAttrValue('CONCEPT')] = $arr->Curr()->GetAttrValue('VALUE');
					$arr->Next();
					$arr->SkipCData();
				}
				$arr->Skip('GENERIC:ATTRIBUTES', 'close');
			} else
				$arr->Next();

			$arr->Skip('GENERIC:OBS', 'open');
			$arr->SkipCdata();
			while ($arr->Curr('tag') != 'GENERIC:OBS') {
				if ($arr->Curr('tag') == 'GENERIC:TIME') {
					$ret->SetTime($arr->Curr('value'));
					$arr->Next();
				} else if ($arr->Curr('tag') == 'GENERIC:OBSVALUE') {
					$ret->SetValue($arr->Curr()->GetAttrValue('VALUE'));
					$arr->Next();
				} else {
					echo "UNKNOWN TAG! ";
					var_dump($arr->Curr());

					if ($arr->Curr('type') == 'open')
						$arr->Skip($arr->Curr('tag'));
					else 
						$arr->Next();
				}
				$arr->SkipCdata();
			}

			if ( ! is_null($dimensions))
				$ret->FillDimensions($dimensions);

			$arr->Skip('GENERIC:SERIES', 'close');

			return $ret;
		}
	}

	// Собственно, класс, описывающий SDMX файл
	class SdmxData {
		// вся инфа из header'а
		protected $header = array();

		function GetHeaderAttr($attr, $default = false) { 
			if (isset($this->header[$attr]))
				return $this->header[$attr]; 
			else
				return $default;
		}
		protected function SetHeaderAttr($attr, $val) {
			$this->header[$attr] = $val;
			return $this;
		}

		function GetHeaderIterator() {
			return new ArrayIterator($this->header);
		}

		/*protected*/ function ParseHeader(XmlDataArray $arr) {
			$arr->Skip('HEADER', 'open');

			while ($arr->Curr('tag') != 'HEADER' || $arr->Curr('type') != 'close') {
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
					/*
						STUB
					*/
					$arr->SkipTo('SENDER', 'close');
				} elseif ($arr->Curr('tag') == 'DATASETAGENCY')
					$this->SetHeaderAttr('dataSetAgency', $arr->Curr('value'));
				elseif ($arr->Curr('tag') == 'DATASETID')
					$this->SetHeaderAttr('dataSetId', $arr->Curr('value'));
				else {
					echo 'UNKNOWN TAG! ';
					var_dump($arr->Curr());
					echo "<br>\n";
					if ($arr->Curr('type') == 'complete')
						$arr->Next();
					else 
						$arr->Skip($arr->Curr('tag'), 'close');
				}
				$arr->Next();
				$arr->SkipCdata();
			}
		}

		// массив codeList'ов
		protected $codeLists = array();
		function GetCodeList($name, $default = false) {
			if (isset($this->codeLists[$name]))
				return $this->codeLists[$name];
			else
				return $default;
		}
		function GetCodeListsIterator() {
			return new ArrayIterator($this->codeLists);
		}
		protected function SetCodeList($name, SdmxCodeList $val) {
			$this->codeLists[$name] = $val;
			return $this;
		}

		// парсит тег CodeLists
		/*protected*/ function ParseCodeLists(XmlDataArray $arr) {
			$arr->SkipTo('CODELISTS');

			// если codelists не пустой
			if ($arr->Curr('type') == 'open') {
				$arr->Next();
				while ($arr->Curr('tag') == 'STRUCTURE:CODELIST') {
					$codelist = SdmxCodeList::Parse($arr);
					$this->SetCodeList($codelist->GetName(), $codelist);
					$arr->SkipCdata();
				}

				$arr->Skip('CODELISTS', 'close');
			} else {
				$arr->Next();
			}

			return $this;
		}

		// я считаю, что может быть только один индикатор. Если может быть больше, то выше есть описание объекта SdmxIndicator
		// правда, он не дописан, но всё-таки его можно будет использовать. Ещё можно покопипастить код ниже
		protected $description = array();
		function GetDescriptionAttr($attr, $default = false) {
			if (isset($this->description[$attr]))
				return $this->description[$attr];
			else 
				return $default;
		}
		function GetDescriptionIterator() { return new ArrayIterator($this->description); }
		protected function SetDescriptionAttr($attr, $val) {
			$this->description[$attr] = $val;
			return $this;
		}

		protected $dimensions = array();
		function GetDimension($ind, $default = false) {
			if (isset($this->dimensions[$ind]))
				return $this->dimensions[$ind];
			else
				return $defalut;
		}
		function GetDimensionsIterator() { return new ArrayIterator($this->dimensions); }
		protected function AddDimension($dim) {
			$this->dimensions[] = $dim;
			return $this;
		}
		/*protected*/ function ParseDescription(XmlDataArray $arr) {
			$this->SetDescriptionAttr('id', $arr->SkipTo('DESCRIPTION', 'open')->GetAttrValue('ID'));
			$this->SetDescriptionAttr('indicatorId', $arr->SkipTo('INDICATOR', 'open')->GetAttrValue('ID'));
			$arr->Next();
			$arr->SkipCdata();
			while ($arr->Curr('tag') != 'INDICATOR') {
				if ($arr->Curr('tag') == 'UNITS') {
					$this->SetDescriptionAttr('unit', $arr->SkipTo('UNIT', 'complete')->GetAttrValue('VALUE'));
					$arr->Skip('UNITS', 'close');
				} else if ($arr->Curr('tag') == 'PERIODICITIES') {
					$periodicityValue = $arr->SkipTo('PERIODICITY', 'complete')->GetAttrValue('VALUE');
					$periodicityReleases = $arr->Curr()->GetAttrValue('RELEASES');
					$periodicityNextRelease = $arr->Curr()->GetAttrValue('NEXT-RELEASES');
					$arr->Skip('PERIODICITIES', 'close');
				} else if ($arr->Curr('tag') == 'DATARANGE') {
					$periodicityDataRangeBegin = $arr->Curr()->GetAttrValue('START');
					$periodicityDataRangeEnd = $arr->Curr()->GetAttrValue('END');
					$arr->Skip('DATARANGE');
				} else if ($arr->Curr('tag') == 'LASTUPDATE') {
					$periodicityLastUpdate = $arr->Curr()->GetAttrValue('VALUE');
					$arr->Skip('LASTUPDATE');
				} else if ($arr->Curr('tag') == 'DIMENSIONS') {
					if ($arr->Curr('type') == 'open') {
						$arr->Next();
						$arr->SkipCdata();
						while ($arr->Curr('tag') == 'DIMENSION') {
							$this->AddDimension(SdmxDimension::Parse($arr, $this->codeLists));
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
					// ************************************************************************************************************* STUB
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

			$this->SetDescriptionAttr('periodicity', new SdmxPeriodicity($periodicityValue,
				                                                         $periodicityReleases,
				                                                         $periodicityNextRelease,
				                                                         $periodicityDataRangeBegin,
				                                                         $periodicityDataRangeEnd,
				                                                         $periodicityLastUpdate));
			$arr->Skip('DESCRIPTION', 'close');
		}

		// собственно, массив с данными
		protected $data = array();
		function GetDataPoint($ind, $default) {
			if (isset($this->data[$ind]))
				return $this->data[$ind];
			else
				return $default;
		}
		function GetDataPointsIterator() { return new ArrayIterator($this->data); }
		protected function AddDataPoint(SdmxDataPoint $point) {
			$this->data[] = $point;
			return $this;
		}

		/*protected*/ function ParseData(XmlDataArray $arr) {
			$arr->Skip('DATASET', 'open');
			$arr->SkipCdata();

			while ($arr->Curr('tag') == 'GENERIC:SERIES') {
				$point = SdmxDataPoint::Parse($arr, $this->dimensions);
				$this->AddDataPoint($point);
				$arr->SkipCdata();
			}

			$arr->Skip('DATASET', 'close');
		}

		// Создаёт объект, заполняет его и проч.
		function __construct($filename) {
			$text = file_get_contents($filename);
			$p = xml_parser_create();
			$arr = new XmlDataArray($p, $text);
			$this->ParseHeader($arr);
			$this->ParseCodeLists($arr);
			$this->ParseDescription($arr);
			$this->ParseData($arr);
		}
	}

	$sdmx = new SdmxData('sdmx.xml');
	//$sdmx->ParseHeader($arr);
	//$sdmx->ParseCodeLists($arr);
	//$sdmx->ParseDescription($arr);
	//$sdmx->ParseData($arr);

	echo '<br>HEADER:<br>';
	for ($it = $sdmx->GetHeaderIterator(); $it->valid(); $it->next()) {
		echo "{$it->key()}: ";
		var_dump($it->current());
		echo "<br>\n";
	}
	echo '<br>CODELISTS:<br>';
	for ($it = $sdmx->GetCodeListsIterator(); $it->valid(); $it->next()) {
		var_dump($it->current());
		echo "<br>\n";
	}
	echo '<br>DESCRIPTION:<br>';
	for ($it = $sdmx->GetDescriptionIterator(); $it->valid(); $it->next()) {
		echo "{$it->key()}: ";
		var_dump($it->current());
		echo '<br>';
	}
	echo '<br>DIMENSIONS:<br>';
	for ($it = $sdmx->GetDimensionsIterator(); $it->valid(); $it->next()) {
		echo "{$it->key()}: ";
		var_dump($it->current());
		echo "<br>\n";
	}
	echo '<br>DATASET:<br>';
	for ($it = $sdmx->GetDataPointsIterator(); $it->valid(); $it->next()) {
		var_dump($it->current());
		echo "<br>\n";
	}

?>
