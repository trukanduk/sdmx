<?php
	/**
	 * В файле содержится описание класса SdmxDataPoint -- "ячейки таблицы".
	 *
	 * @author Илья Уваренков <trukanduk@gmail.com>
	 * @version 1.1
	 * @package sdmx
	 */

	require_once('XmlParseUtils.php');
	require_once('SdmxCodelist.php');
	require_once('SdmxDimension.php');

	/**
	 * Ячейка таблицы
	 * 
	 * Класс по семантике - "ячейка таблицы". Название <var>SdmxDataPoint</var> дано потому что эту ячейку можно предстаавить как точку со значением в N-мерном пространстве.
	 * Хранит в себе значения "координат" во всех размерностях, так же некие аттрибуты, год (всё-таки статистические данные) и, наконец, значение ячейки
	 *
	 * @package sdmx
	 * @version 1.1
	 */
	class SdmxDataPoint {
		/**
		 * Описние размерностей
		 *
		 * Массив имеет вид: <var>['&lt;имя размерности>' => new SdmxCodelistInstance()]</var>,
		 * однако есть второй временный вариант, <var>['&lt;имя размерности' => '&lt;имя значения>']</var>, который останется в случае ошибки при поиске нужной размерности,
		 * то есть если при заполнении объекта не будет найдена какая-то размерность, то соответствующей этой размерности элемент массива будет строкой с названием значения.
		 *
		 * @var mixed[]
		 */
		protected $dimensions = array();

		/**
		 * Итератор массива размерностей
		 *
		 * Возвращает итератор на начало массива размерностей точки
		 *
		 * @return ArrayIterator итератор на начало массива
		 */
		function GetDimensionsIterator() { return new ArrayIterator($this->dimensions); }

		/**
		 * Получение значения размерности
		 *
		 * Возвращает элемент массива я индексом <var>$name</var>, будь то строка или <var>SdmxCodelistInstance</var>
		 * Имя размерности не зависит от регистра
		 *
		 * @param string $name имя нужной размерности (не зависит от регистра)
		 * @param mixed $default значение, возвращаемое в случае ненахождения размерности
		 * @return mixed Объект <var>SdmxCodelistInstance</var>, строку-имя значения или <var>$default<var> в случае отсутствия размерности
		 */
		function GetDimension($name, $default = false) {
			if (isset($this->dimensions[strtolower($name)]))
				return $this->dimensions[strtolower($name)];
			else
				return $default;
		}

		/**
		 * Установка размерности
		 *
		 * @param string $name имя размерности (не зависит от регистра)
		 * @param mixed $dim имя значения (строка) или экземпляр <var>SdmxCodelistInstance</var>
		 * @return SdmxDataPoint сам объект
		 */
		protected function SetDimension($name, $dim) {
			$this->dimensions[strtolower($name)] = $dim;
			$this->dimsFilled &= is_a($dim, SdmxCodelistInstance);

			return $this;
		}

		/**
		 * Установка массива размерностей
		 *
		 * Гарантируется, что входной массив <var>$dimensions</var> не будет изменён.
		 *
		 * @param SdmxCodelistInstance[] $dimensions массив размерностей в виде <var>['&lt;имя размерности>' => new SdmxCodelistInstance()]</var>
		 * @return SdmxDataPoint объект-родитель
		 */
		protected function SetDimensionsArray($dimensions) {
			$this->dimensions = array();
			foreach ($dimensions as $key => $dim)
				$this->SetDimension($key, $dim);
			return $this;
		}

		/**
		 * Заполнение массива размерностей значениями
		 *
		 * Функция берёт принимает на вход массив <var>$dimensions</var> вида <var>['&lt;имя размерности>' => new SdmxDimension()]</var> (такой массив должен быть в <var>SdmxData</var>)
		 * Возвращает <var>true</var>, если всё прошло успешно, иначе остаются элементы со строками в качестве значений.
		 * Если какая-то размерность есть в массиве <var>$dimensions</var>, но нет в точке, то эта размерность будет присвоена объекту (будет взято значение размерности по умолчанию). 
		 * Если в объекте была размерность, которой нет в массиве <var>$dimensions</var>, элемент этой размерности в объекте не будет изменён и останется строкой.
		 * Если  объекте какая-то размерность была заполнена, она не будет перезаписана
		 * <b>Функция не изменяет массив<b>
		 *
		 * @param SdmxDimension[] $dimensions массив размерностей файла
		 * @return bool <var>true</var> в случае успеха, иначе <var>false</var>
		 */
		protected function FillDimensions($dimensions) {
			$ret = true;

			// посмотрим на все размерности из входного массива
			foreach ($dimensions as $dim) {
				// еслии у объекта уже установлена эта размерность, не будем её трогать
				if (is_a($this->dimensions[$dim->GetCodelist()->GetName()], SdmxCodelistInstance))
					continue;
				// если размерность "не полная" (сама не полностью заполнена), то это косяк
				if ( ! $dim->HasCodelistLink())
					$ret &= false;
				else {
					// если в объекте есть упоминание о размерности, найдём нужное значение в массиве размерностей, иначе возьмём значение размерности по умолчанию
					if (isset($this->dimensions[$dim->GetCodelist()->GetName()]))
						$this->SetDimension($dim->GetCodelist()->GetName(), $dim->GetCodelist()->GetInstance($this->dimensions[$dim->GetCodelist()->GetName()],
							                                                $dim->GetCodelist()->GetDefaultInstance()));
					else
						$this->SetDimension($dim->GetCodelist()->GetName(), $dim->GetCodelist()->GetDefaultInstance());
				}
			}

			// проверим, все ли размерности объекта заполнены
			foreach ($this->dimensions as $dim)
				$ret &= is_a($dim, SdmxCodelistInstance);

			// сейчас $ret -- это, по сути, новое значение $this->dimsFilled
			return ($this->dimsFilled = $ret);
		}


		/**
		 * Вид массива $dimensions
		 *
		 * Если <var>true</var>, то массив <var>$this->dimensions</var> имеет верную форму (<var>['&lt;имя размерности>' => new SdmxCodelistInstance()]</var>)
		 * @var bool
		 */
		protected $dimsFilled = true;

		/**
		 * Правильность заполнения размерностей
		 */
		function IsDimensionsFilled() {
			return $this->dimsFilled;
		}


		/**
		 * Аттрибуты объекта
		 *
		 * Просто ассоциативный массив с аттрибутами точки
		 * @var string[]
		 */
		protected $attributes = array();

		/**
		 * Получение итератора аттрибутов
		 *
		 * Возвращает итератор на начало массива аттрибутов
		 * @return ArrayIterator итератор на начало массива аттрибутов
		 */
		function GetAttributesIterator() { return new ArrayIterator($this->attributes); }

		/**
		 * Получение конкретного аттрибута
		 *
		 * Возвращает аттрибут по его имени, если такой аттрибут не был найден, вернётся <var>$default</var>
		 * Имя аттрибута не зависит от регистра (т.е. <var>'attr'</var> и <var>'ATTR'</var> относятся к  одному аттрибуту)
		 *
		 * @param string $attr имя аттрибута (не зависит от регистра)
		 * @param mixed $default значение, возвращаемое при отсутствии аттрибута
		 * @return mixed значение аттрибута (<var>string</var>) или <var>$default</var>
		 */
		function GetAttr($attr, $default = false) {
			if (isset($this->attributes[strtolower($attr)]))
				return $this->attributes[strtolower($attr)];
			else
				return $default;
		}

		/**
		 * Установка аттрибута
		 *
		 * Устанавливает аттрибут <var>$name</var>. Имя аттрибута не зависит от регистра (т.е. имена <var>'attr'</var>, <var>'Attr'</var> и <var>'ATTR'</var> отвечают за один аттрибут)
		 *
		 * @param string $attr имя изменяемого аттрибута (не зависит от регистра)
		 * @param string $val новое значение аттрибута
		 * @return SdmxDataPoint сам объект
		 */
		protected function SetAttr($attr, $val) {
			$this->attributes[strtolower($attr)] = $val;
			return $this;
		}

		/**
		 * Установка массива аттрибутов
		 *
		 * Гарантируется, что входной массив <var>$attrs</var> не изменится
		 *
		 * @param string[] $attrs Массив аттрибутов в форме <var>['&lt;имя аттрибута>' => '&lt;значение аттрибута>']</var>
		 * @return SdmxDataPoint объект-родитель
		 */
		protected function SetAttrsArray($attrs) {
			$this->attrs = array();
			foreach ($attrs as $key => $val)
				$this->SetAttr($key, $val);
			return $this;
		}

		/**
		 * Время
		 *
		 * Видимо, речь о годе. Хотя в принципе это всё равно строка -- может быть чем угодно
		 * @var string
		 */
		protected $time = '';

		/**
		 * Получение времени
		 *
		 * @return string время, к которому относится данный объект
		 */ 
		function GetTime() { return $this->time; }

		/**
		 * Установка времени
		 *
		 * @param string $time новое время, относящееся к объекту
		 */
		protected function SetTime($time) {
			$this->time = $time;
			return $this;
		}

		/**
		 * Значение в ячейке
		 *
		 * собственно, значение, хранящееся в ячейке таблицы
		 * @var string
		 */
		protected $value = '';

		/**
		 * Получение значения
		 *
		 * @return string значение объекта (ячейки, которой соотвествуюет объект)
		 */
		function GetValue() { return $this->value; }

		/**
		 * Устаноска значения
		 *
		 * @param string @value новое значение
		 * @return SdmxDataPoint сам объект
		 */
		protected function SetValue($value) {
			$this->value = $value;
			return $this;
		}

		/**
		 * Получение свойства
		 *
		 * Возвращает свойство ячейки. Свойства (в порядке уменьшения приоритета): значение ('value'), время ('time'), размерность (имя размерности) или аттрибут (имя аттрибута)
		 * имя свойства не зависит от регистра
		 *
		 * @param string $property имя свойства (не зависит от регистра)
		 * @param mixed $default значение, возвращаемое в случае отсутствия свойства
		 * @return mixed значение свойства
		 */
		function GetProperty($property, $default = false) {
			if (strtolower($property) === 'value') 
				return $this->GetValue();
			elseif (strtolower($property) === 'time')
				return $this->GetTime();
			elseif (($ret = $this->GetDimension($property, $default)) !== $default)
				return $ret;
			elseif (($ret = $this->GetAttrValue($property, $default)) !== $default)
				return $ret;
			else
				return $default;
		}

		/**
		 * Конструктор
		 *
		 * После создания объекта нет никаких способов его изменить извне.
		 * Гарантируется, что входный массивы <var>$dimensions</var> и <var>$attrs</var> не будут изменены
		 *
		 * @param string $value значение ячейки
		 * @param string $time время ячейки (некий параметр)
		 * @param SdmxCodelistInstance[] $dimensions массив размерностей в виде <var>['&lt;имя размерности>' => new SdmxCodelistInstance()]</var>
		 * @param string[] $attrs массив аттрибутов в виде <var>['&lt;имя аттрибута' => '&lt;значение аттрибута>']</var>
		 */
		function __construct($value, $time, $dimensions, $attrs) {
			$this->SetValue($value)
			     ->SetTime($time)
			     ->SetDimensionsArray($dimensions)
			     ->SetAttrsArray($attrs);
		}

		/**
		 * Создание элемента из XmlDataArray
		 *
		 * "Достаёт" элемент из объекта <var>XmlDataArray</var> и перемещает его внутренний указатель.
		 *
		 * @param XmlDataArray $arr объект с обработанным xml-файлом и курсором перед тегом (или на самом теге) с началом объекта 
		 * @param SdmxDimension[] $dimensions массив разверностей в форме <var>['&lt;имя размерности>' => new SdmxDimension()]</var> Гарантируется, что массив не будет изменён
		 * @return SdmxDataPoint новый экземпляр класса
		 */
		static function Parse(XmlDataArray $arr, $dimensions) {

			$ret = new SdmxDataPoint('', '', array(), array());

			$arr->SkipTo('GENERIC:SERIES');
			$arr->SkipTo('GENERIC:SERIESKEY');
			if ($arr->Curr('type') == 'open') {
				$arr->Next();
				$arr->SkipCdata();
				while ($arr->Curr('tag') == 'GENERIC:VALUE') {
					$ret->SetDimension($arr->Curr()->GetAttrValue('CONCEPT'), $arr->Curr()->GetAttrValue('VALUE'));
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
?>
